<?php

namespace common\tests\unit\ispmanager;

use common\components\ispmanager\UserTraffic;
use common\models\Server;
use PHPUnit\Framework\TestCase;
use Yii;

class FakeUserTraffic extends UserTraffic
{
    public ?string $body = null;
    public int $calls = 0;

    protected function request(Server $server): string
    {
        $this->calls++;
        if ($this->body === null) {
            throw new \RuntimeException('Simulated transport failure');
        }
        return $this->body;
    }
}

class UserTrafficTest extends TestCase
{
    private $previousApp;

    protected function setUp(): void
    {
        $this->previousApp = Yii::$app;
        new \yii\console\Application([
            'id' => 'isp-traffic-test', 'basePath' => dirname(__DIR__), 'timeZone' => 'Asia/Yekaterinburg',
        ]);
    }

    protected function tearDown(): void
    {
        Yii::$app = $this->previousApp;
    }

    public function testRealPanelFieldsReadUsageAndIgnoreLimits(): void
    {
        $users = UserTraffic::parse('<doc><elem><name>site</name><traff_used>304100</traff_used>'
            . '<traff_total>0</traff_total><limit_traff>off</limit_traff></elem>'
            . '<elem><name>zero</name><traff_used>0</traff_used><traff_total>10000</traff_total></elem></doc>');
        self::assertSame(304100.0, $users['site']);
        self::assertSame(0.0, $users['zero']);
    }

    public function testFallbackFormatsAndUnavailableValues(): void
    {
        $users = UserTraffic::parse('<doc>'
            . '<elem><name>decimal</name><traff>12.5 / 900</traff></elem>'
            . '<elem><name>plain</name><traff>15</traff></elem>'
            . '<elem><name>absent</name></elem>'
            . '<elem><name>invalid</name><traff_used>unknown</traff_used></elem></doc>');
        self::assertSame(12.5, $users['decimal']);
        self::assertSame(15.0, $users['plain']);
        self::assertNull($users['absent']);
        self::assertNull($users['invalid']);
    }

    /** @dataProvider invalidResponses */
    public function testErrorsAndMalformedResponsesAreRejected(string $body): void
    {
        $this->expectException(\RuntimeException::class);
        UserTraffic::parse($body);
    }

    public static function invalidResponses(): array
    {
        return [
            ['<doc><error type="auth"/></doc>'], ['not xml'], ['<html/>'],
            ['<doc><elem><name>duplicate</name></elem><elem><name>duplicate</name></elem></doc>'],
            ['<!DOCTYPE doc [<!ENTITY example "100">]><doc/>'],
        ];
    }

    public function testSnapshotExactLoginZeroMissingAndSharedUsers(): void
    {
        $monitor = new FakeUserTraffic();
        $monitor->body = '<doc><elem><name>Site</name><traff_used>0</traff_used></elem></doc>';
        $snapshot = $monitor->fetch(new Server());
        $firstSite = UserTraffic::forLogin('Site', $snapshot);
        $secondSite = UserTraffic::forLogin('Site', $snapshot);
        self::assertSame(1, $monitor->calls);
        self::assertSame('available', $firstSite['state']);
        self::assertSame(0.0, $firstSite['used_mib']);
        self::assertSame($firstSite, $secondSite);
        self::assertSame('unavailable', UserTraffic::forLogin('site', $snapshot)['state']);
        self::assertSame('unavailable', UserTraffic::forLogin(null, $snapshot)['state']);
        self::assertSame(date('Y-m-01'), $firstSite['period_start']);
        self::assertSame(date('Y-m-d'), $firstSite['period_end']);
        self::assertNotEmpty($firstSite['checked_at']);
        $restored = json_decode(json_encode($firstSite), true);
        self::assertSame(0, $restored['used_mib']);
    }

    public function testTransportFailureIsUnavailableRatherThanZero(): void
    {
        $monitor = new FakeUserTraffic();
        $snapshot = $monitor->fetch(new Server());
        self::assertSame('unavailable', $snapshot['state']);
        $result = UserTraffic::forLogin('site', $snapshot);
        self::assertSame('unavailable', $result['state']);
        self::assertNull($result['used_mib']);
    }
}
