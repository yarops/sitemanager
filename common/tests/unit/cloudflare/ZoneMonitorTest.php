<?php

namespace common\tests\unit\cloudflare;

use common\components\cloudflare\ReportGroups;
use common\components\cloudflare\ZoneMonitor;
use PHPUnit\Framework\TestCase;
use yii\caching\ArrayCache;

class FakeZoneMonitor extends ZoneMonitor
{
    public array $responses = [];
    public array $calls = [];

    protected function requestZones(string $id, string $token, int $page): array
    {
        $this->calls[] = [$id, $page];
        $response = $this->responses[$id][$page] ?? null;
        if (!is_array($response)) {
            throw new \RuntimeException('Simulated API failure');
        }
        return $response;
    }
}

class ZoneMonitorTest extends TestCase
{
    private function zone(string $name, string $account = 'a', string $status = 'active'): array
    {
        return ['id' => $name, 'name' => $name, 'status' => $status, 'account' => ['id' => $account]];
    }

    private function response(array $zones, int $page = 1, int $pages = 1, ?int $count = null): array
    {
        return ['success' => true, 'result' => $zones, 'result_info' => [
            'page' => $page, 'total_pages' => $pages, 'total_count' => $count ?? count($zones),
        ]];
    }

    private function monitor(?ArrayCache $cache = null, array $ids = ['a']): FakeZoneMonitor
    {
        $accounts = [];
        foreach ($ids as $id) {
            $accounts[$id] = ['id' => $id, 'apikey' => 'test-credential'];
        }
        return new FakeZoneMonitor($accounts, $cache ?? new ArrayCache());
    }

    public function testMatchingUsesDnsBoundariesAndLongestZone(): void
    {
        $monitor = $this->monitor();
        $monitor->responses['a'][1] = $this->response([
            $this->zone('example.co.uk'), $this->zone('shop.example.co.uk'),
            $this->zone('xn--e1afmkfd.xn--p1ai'),
        ]);
        $snapshot = $monitor->synchronize();
        $result = ZoneMonitor::match('HTTPS://WWW.SHOP.EXAMPLE.CO.UK.:443/path', $snapshot);
        self::assertSame('shop.example.co.uk', $result['zones'][0]['name']);
        self::assertSame('missing', ZoneMonitor::match('badexample.co.uk', $snapshot)['state']);
        self::assertSame('found', ZoneMonitor::match('example.co.uk', $snapshot)['state']);
        self::assertSame('found', ZoneMonitor::match('https://пример.рф', $snapshot)['state']);
    }

    public function testPaginationAndMultipleAccounts(): void
    {
        $monitor = $this->monitor(null, ['a', 'b']);
        $zones = [];
        for ($i = 0; $i < 50; $i++) {
            $zones[] = $this->zone("site$i.example");
        }
        $monitor->responses['a'][1] = $this->response($zones, 1, 2, 51);
        $monitor->responses['a'][2] = $this->response([$this->zone('last.example')], 2, 2, 51);
        $monitor->responses['b'][1] = $this->response([$this->zone('last.example', 'b')]);
        $snapshot = $monitor->synchronize();
        self::assertTrue($snapshot['complete']);
        self::assertSame([['a', 1], ['a', 2], ['b', 1]], $monitor->calls);
        self::assertCount(2, ZoneMonitor::match('last.example', $snapshot)['zones']);
    }

    public function testPartialFailurePreservesLastCompleteList(): void
    {
        $cache = new ArrayCache();
        $monitor = $this->monitor($cache, ['a', 'b']);
        $monitor->responses['a'][1] = $this->response([$this->zone('old.example')]);
        $monitor->responses['b'][1] = $this->response([]);
        $monitor->synchronize();
        $monitor->responses['a'][1] = $this->response([$this->zone('partial.example')], 1, 2, 2);
        $snapshot = $monitor->synchronize();
        self::assertFalse($snapshot['complete']);
        $old = ZoneMonitor::match('old.example', $snapshot);
        self::assertSame('found', $old['state']);
        self::assertTrue($old['stale']);
        self::assertSame('unknown', ZoneMonitor::match('partial.example', $snapshot)['state']);
        self::assertSame('unknown', ZoneMonitor::match('absent.example', $snapshot)['state']);
    }

    public function testFailureWithoutCacheAndMissingConfigurationAreUnknown(): void
    {
        $monitor = $this->monitor(null, ['a', 'b']);
        $monitor->responses['a'][1] = $this->response([]);
        self::assertSame('unknown', ZoneMonitor::match('absent.example', $monitor->synchronize())['state']);
        $empty = new ZoneMonitor([], new ArrayCache());
        self::assertSame('unknown', ZoneMonitor::match('absent.example', $empty->synchronize())['state']);
    }

    public function testTruncatedAndWrongAccountResponsesAreRejected(): void
    {
        $monitor = $this->monitor();
        $monitor->responses['a'][1] = $this->response([], 1, 1, 1);
        self::assertFalse($monitor->synchronize()['complete']);
        $monitor->responses['a'][1] = $this->response([$this->zone('example.com', 'other')]);
        self::assertFalse($monitor->synchronize()['complete']);
    }

    public function testGroupsColorsFilteringAndAliases(): void
    {
        $found = ['state' => 'found', 'stale' => false, 'zones' => [['status' => 'active']]];
        $missing = ['state' => 'missing', 'stale' => false, 'zones' => []];
        $report = [
            'https://success.example' => ['status' => 200, 'alias_status' => 0, 'cloudflare' => $found],
            'https://missing.example' => ['status' => 200, 'cloudflare' => $missing],
            'https://broken.example' => ['status' => 0, 'cloudflare' => $missing],
            'https://redirect.example' => ['status' => 301, 'cloudflare' => $found],
            'https://legacy.example' => 200,
            'https://pending.example' => ['status' => 200, 'cloudflare' => array_replace($found, ['zones' => [['status' => 'pending']]])],
            'https://stale.example' => ['status' => 200, 'cloudflare' => array_replace($found, ['stale' => true])],
        ];
        $groups = ReportGroups::partition($report);
        self::assertSame(['https://broken.example', 'https://redirect.example'], array_keys($groups['http']));
        self::assertCount(1, $groups['missing']);
        self::assertCount(3, $groups['attention']);
        self::assertCount(1, $groups['success']);
        self::assertSame('table-info', ReportGroups::rowClass($report['https://missing.example']));
        self::assertSame('table-danger', ReportGroups::rowClass($report['https://broken.example']));
        self::assertSame('table-success', ReportGroups::rowClass($report['https://success.example']));
        self::assertSame('table-warning', ReportGroups::rowClass($report['https://pending.example']));
        self::assertSame('', ReportGroups::rowClass($report['https://stale.example']));
        $filtered = ReportGroups::partition($report, true);
        self::assertCount(1, $filtered['http']);
        self::assertCount(1, $filtered['missing']);
        self::assertEmpty($filtered['attention']);
        self::assertEmpty($filtered['success']);
    }

    public function testReportViewSupportsLegacyAndGroupedResults(): void
    {
        $previousApp = \Yii::$app;
        $app = new \yii\web\Application([
            'id' => 'cloudflare-view-test',
            'basePath' => \Yii::getAlias('@frontend'),
            'components' => [
                'i18n' => ['translations' => ['frontend' => [
                    'class' => 'yii\i18n\PhpMessageSource', 'basePath' => '@common/messages',
                ]]],
                'request' => [
                    'cookieValidationKey' => 'test', 'scriptUrl' => '/index.php', 'scriptFile' => __FILE__,
                    'hostInfo' => 'https://test.example', 'url' => '/index.php',
                ],
                'assetManager' => [
                    'basePath' => sys_get_temp_dir(), 'baseUrl' => '/assets',
                    'bundles' => ['yii\web\JqueryAsset' => false, 'yii\web\YiiAsset' => false],
                ],
            ],
        ]);
        try {
            $app->controller = new \frontend\controllers\ServerCheckController('server-check', $app);
            $model = new \stdClass();
            $model->id = 1;
            $model->title = 'Test';
            $model->report = json_encode([
                'https://old.example' => 200,
                'https://absent.example' => ['status' => 200, 'cloudflare' => [
                    'state' => 'missing', 'stale' => false, 'zones' => [], 'accounts' => [],
                ]],
            ]);
            $html = $app->view->renderFile(\Yii::getAlias('@frontend/views/server-check/view.php'), [
                'model' => $model, 'itemsByUrl' => [],
            ]);
            self::assertStringContainsString('table-info', $html);
            self::assertStringContainsString('Не проверено', $html);
            self::assertStringContainsString('Не найден', $html);
            self::assertSame(2, substr_count($html, '<tr id="site-row-'));
            $app->request->setQueryParams(['cloudflare' => 'missing']);
            $filtered = $app->view->renderFile(\Yii::getAlias('@frontend/views/server-check/view.php'), [
                'model' => $model, 'itemsByUrl' => [],
            ]);
            self::assertSame(1, substr_count($filtered, '<tr id="site-row-'));
            self::assertStringNotContainsString('https://old.example', $filtered);
        } finally {
            \Yii::$app = $previousApp;
        }
    }
}
