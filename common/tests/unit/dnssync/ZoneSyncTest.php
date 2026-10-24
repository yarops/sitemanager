<?php

namespace common\tests\unit\dnssync;

use common\components\cloudflare\DnsRecords;
use common\components\dnssync\ZoneSync;
use common\components\ispmanager\DnsZone;
use common\components\ispmanager\ZoneNotFound;
use common\models\DnsSync;
use common\models\DnsSyncZone;
use common\models\Server;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\db\Connection;
use yii\mutex\Mutex;

class FakeIsp extends DnsZone
{
    /** @var array|\Throwable */
    public $records = [];

    public function records(Server $server, string $zone): array
    {
        if ($this->records instanceof \Throwable) {
            throw $this->records;
        }
        return $this->records;
    }
}

class FakeCloudflare extends DnsRecords
{
    public array $found = ['state' => 'found', 'zone_id' => 'z1', 'account' => 'a', 'status' => 'active', 'name_servers' => ['n1', 'n2']];
    /** @var array[] successive list() results; the last one repeats */
    public array $lists = [[]];
    /** @var array|\Throwable|null */
    public $batchResult = null;
    public array $batches = [];
    public int $listCalls = 0;

    public function __construct()
    {
        parent::__construct([]);
    }

    public function findZone(string $domain): array
    {
        return $this->found;
    }

    public function list(string $account, string $zoneId): array
    {
        $this->listCalls++;
        $list = count($this->lists) > 1 ? array_shift($this->lists) : $this->lists[0];
        if ($list instanceof \Throwable) {
            throw $list;
        }
        return $list;
    }

    public function batch(string $account, string $zoneId, array $deletes, array $posts): array
    {
        $this->batches[] = [$account, $zoneId, $deletes, $posts];
        if ($this->batchResult instanceof \Throwable) {
            throw $this->batchResult;
        }
        return $this->batchResult ?? ['deletes' => array_map(static fn($id) => ['id' => $id], $deletes), 'posts' => $posts];
    }
}

class FakeMutex extends Mutex
{
    public static array $held = [];

    protected function acquireLock($name, $timeout = 0)
    {
        if (isset(self::$held[$name])) {
            return false;
        }
        return self::$held[$name] = true;
    }

    protected function releaseLock($name)
    {
        unset(self::$held[$name]);
        return true;
    }
}

class ZoneSyncTest extends TestCase
{
    private const CONFIG = [
        'types' => ['A', 'AAAA', 'CNAME', 'MX', 'TXT'],
        'proxied' => ['A' => true, 'AAAA' => true, 'CNAME' => true],
        'proxiedExclude' => ['mail', 'smtp', 'pop', 'imap', 'ftp'],
        'ttl' => 1,
        'maxBatchOperations' => 200,
    ];

    private $previousApplication;
    private FakeIsp $isp;
    private FakeCloudflare $cloudflare;

    protected function setUp(): void
    {
        $this->previousApplication = \Yii::$app;
        new Application([
            'id' => 'dns-sync-test',
            'basePath' => dirname(__DIR__, 4),
            'components' => ['db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:']],
        ]);
        \Yii::$app->db->createCommand('CREATE TABLE server (id INTEGER PRIMARY KEY, title TEXT, ip TEXT, password TEXT)')->execute();
        \Yii::$app->db->createCommand()->insert('server', ['id' => 1, 'title' => 's', 'ip' => '127.0.0.1', 'password' => 'x'])->execute();
        require_once dirname(__DIR__, 4) . '/console/migrations/m261024_120000_create_dns_sync_tables.php';
        $migration = new class extends \m261024_120000_create_dns_sync_tables {
            public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
            {
                // SQLite cannot add foreign keys to existing tables.
            }
        };
        ob_start();
        try {
            $migration->safeUp();
        } finally {
            ob_end_clean();
        }
        \Yii::$app->db->createCommand()->insert('dns_sync', [
            'id' => 1, 'title' => 't', 'source_server_id' => 1, 'isp_server_id' => 1, 'created_at' => '2026-10-24 00:00:00',
        ])->execute();

        FakeMutex::$held = [];
        $this->isp = new FakeIsp();
        $this->isp->records = $this->ispRecords('a.com');
        $this->cloudflare = new FakeCloudflare();
    }

    protected function tearDown(): void
    {
        \Yii::$app->db->close();
        \Yii::$app = $this->previousApplication;
    }

    private function ispRecords(string $zone): array
    {
        $body = str_replace('dear-passenger.com', $zone, file_get_contents(__DIR__ . '/fixtures/isp-domain.record-dear-passenger.com.json'));
        return DnsZone::parse($body, $zone);
    }

    private function sync(array $config = self::CONFIG): ZoneSync
    {
        return new ZoneSync($this->isp, $this->cloudflare, $config, new FakeMutex());
    }

    private function zone(string $domain = 'a.com', array $attributes = []): DnsSyncZone
    {
        $zone = new DnsSyncZone(['sync_id' => 1, 'domain' => $domain] + $attributes);
        $zone->save(false);
        return DnsSyncZone::findOne($zone->id);
    }

    private function cfRecord(string $id, string $type = 'A', string $name = 'a.com', string $content = '206.54.191.13'): array
    {
        return ['id' => $id, 'type' => $type, 'name' => $name, 'content' => $content, 'priority' => null, 'proxied' => true, 'ttl' => 1];
    }

    private function texts(DnsSyncZone $zone): array
    {
        return array_column(DnsSyncZone::findOne($zone->id)->getJson('messages') ?? [], 'text');
    }

    private function foundZone(array $before = null): DnsSyncZone
    {
        $zone = $this->zone();
        $this->cloudflare->lists = [$before ?? [$this->cfRecord('old1'), $this->cfRecord('old2', 'CNAME', 'www.a.com', 'a.com')]];
        $this->sync()->loadBefore($zone);
        return DnsSyncZone::findOne($zone->id);
    }

    public function testLoadIsp(): void
    {
        $zone = $this->zone();
        $records = $this->sync()->loadIsp($zone);
        self::assertCount(9, $records);
        $zone = DnsSyncZone::findOne($zone->id);
        self::assertCount(9, $zone->getJson('isp_records'));
        self::assertNotNull($zone->isp_loaded_at);
        self::assertSame(['ISP: 9 записей'], $this->texts($zone));

        $this->isp->records = new ZoneNotFound('missing');
        self::assertNull($this->sync()->loadIsp($zone));
        self::assertNull(DnsSyncZone::findOne($zone->id)->isp_records);
        self::assertSame('Нет зоны в ISP', array_slice($this->texts($zone), -1)[0]);
    }

    public function testLoadBeforeAndBinding(): void
    {
        $zone = $this->foundZone();
        self::assertSame('found', $zone->cf_state);
        self::assertSame(['a', 'z1', 'active'], [$zone->cf_account, $zone->cf_zone_id, $zone->cf_zone_status]);
        self::assertSame(['n1', 'n2'], $zone->getJson('cf_name_servers'));
        self::assertCount(2, $zone->getJson('cf_before'));

        // Reload does not overwrite before.
        $this->cloudflare->lists = [[]];
        $this->sync()->loadBefore($zone);
        self::assertCount(2, DnsSyncZone::findOne($zone->id)->getJson('cf_before'));
        self::assertFalse($zone->saveBeforeOnce([], 'a', 'z1'));
        self::assertCount(2, $zone->getJson('cf_before'));

        // Another Cloudflare zone with the same name: binding stays, state is error.
        $this->cloudflare->found['zone_id'] = 'z2';
        $this->sync()->loadBefore($zone);
        $zone = DnsSyncZone::findOne($zone->id);
        self::assertSame(['error', 'z1'], [$zone->cf_state, $zone->cf_zone_id]);
        self::assertSame('Зона в Cloudflare сменилась после сохранения before', array_slice($this->texts($zone), -1)[0]);
    }

    public function testMissingAndSearchErrors(): void
    {
        $zone = $this->zone();
        $this->cloudflare->found = ['state' => 'missing'];
        $this->sync()->loadBefore($zone);
        self::assertSame('missing', DnsSyncZone::findOne($zone->id)->cf_state);
        $this->cloudflare->found = ['state' => 'error', 'message' => 'Поиск неполный: b: HTTP 500'];
        $this->sync()->loadBefore($zone);
        $zone = DnsSyncZone::findOne($zone->id);
        self::assertSame('error', $zone->cf_state);
        self::assertNull($zone->cf_before);
        self::assertSame(['Нет в Cloudflare', 'Cloudflare: Поиск неполный: b: HTTP 500'], $this->texts($zone));
    }

    public function testRunReplacesAllRecords(): void
    {
        $zone = $this->foundZone();
        $after = array_map(fn(array $post): array => ['id' => 'n' . $post['name']] + $post + ['priority' => null, 'proxied' => false],
            \common\components\dnssync\RecordMapper::toCloudflare($this->isp->records, 'a.com', self::CONFIG));
        $this->cloudflare->lists = [[$this->cfRecord('old1'), $this->cfRecord('old2'), $this->cfRecord('old3')], $after];
        $this->sync()->run($zone);

        $zone = DnsSyncZone::findOne($zone->id);
        self::assertSame('done', $zone->status);
        self::assertNotNull($zone->finished_at);
        [$account, $zoneId, $deletes, $posts] = $this->cloudflare->batches[0];
        self::assertSame(['a', 'z1', ['old1', 'old2', 'old3']], [$account, $zoneId, $deletes]);
        self::assertCount(9, $posts);
        self::assertCount(2, $zone->getJson('cf_before'), 'before is the snapshot from loadBefore');
        self::assertCount(3, $zone->getJson('batch_result')['deletes']);
        self::assertCount(9, $zone->getJson('cf_after'));
        self::assertContains('Удалено 3, создано 9', $this->texts($zone));
        self::assertContains('Cloudflare after: 9 записей, совпадает с ISP', $this->texts($zone));
        self::assertSame([], FakeMutex::$held);
    }

    public function testRunSavesBeforeWhenMissingAndKeepsEmptySnapshot(): void
    {
        $zone = $this->zone('a.com', ['cf_state' => 'found', 'cf_account' => 'a', 'cf_zone_id' => 'z1']);
        $this->cloudflare->lists = [[$this->cfRecord('old1')], []];
        $this->sync()->run($zone);
        self::assertCount(1, DnsSyncZone::findOne($zone->id)->getJson('cf_before'));

        // A saved empty snapshot ([]) is not replaced on restart.
        $empty = $this->zone('b.com', ['cf_state' => 'found', 'cf_account' => 'a', 'cf_zone_id' => 'z2', 'cf_before' => '[]']);
        $this->isp->records = $this->ispRecords('b.com');
        $this->cloudflare->lists = [[$this->cfRecord('x', 'A', 'b.com')], []];
        $this->sync()->run($empty);
        self::assertSame([], DnsSyncZone::findOne($empty->id)->getJson('cf_before'));
    }

    public function testBatchErrorStillReadsAfter(): void
    {
        $zone = $this->foundZone();
        $this->cloudflare->batchResult = new \RuntimeException('Cloudflare 9005: bad record');
        $listsBefore = $this->cloudflare->listCalls;
        $this->sync()->run($zone);
        $zone = DnsSyncZone::findOne($zone->id);
        self::assertSame('error', $zone->status);
        self::assertSame(2, $this->cloudflare->listCalls - $listsBefore, 'current list + after');
        self::assertNotNull($zone->cf_after_loaded_at);
        self::assertNull($zone->batch_result);
        self::assertContains('Batch: Cloudflare 9005: bad record', $this->texts($zone));
    }

    public function testRunRefusals(): void
    {
        // No ISP records.
        $zone = $this->foundZone();
        $this->isp->records = [];
        $this->sync()->run($zone);
        self::assertSame([], $this->cloudflare->batches);
        self::assertSame('error', DnsSyncZone::findOne($zone->id)->status);
        self::assertContains('Нет записей ISP для синка, Cloudflare не изменён', $this->texts($zone));

        // Legacy cPanel zone.
        $this->isp->records = DnsZone::parse(file_get_contents(__DIR__ . '/fixtures/isp-domain.record-franbowgame.com.json'), 'franbowgame.com');
        $legacy = $this->zone('franbowgame.com', ['cf_state' => 'found', 'cf_account' => 'a', 'cf_zone_id' => 'z9']);
        $this->sync()->run($legacy);
        self::assertSame([], $this->cloudflare->batches);
        self::assertContains('Легаси cPanel-зона, синк не поддерживается', $this->texts($legacy));

        // Not in Cloudflare.
        $this->isp->records = $this->ispRecords('c.com');
        $missing = $this->zone('c.com', ['cf_state' => 'missing']);
        $this->sync()->run($missing);
        self::assertSame([], $this->cloudflare->batches);
        self::assertContains('Зона не найдена в Cloudflare, синк не выполнен', $this->texts($missing));
    }

    public function testBatchLimit(): void
    {
        $zone = $this->foundZone();
        $this->sync(['maxBatchOperations' => 10] + self::CONFIG)->run($zone);
        self::assertSame([], $this->cloudflare->batches);
        self::assertContains('Batch: 11 операций, лимит 10', $this->texts($zone));
    }

    public function testParallelRunsAreRejected(): void
    {
        $zone = $this->foundZone();
        self::assertTrue($zone->tryLock());
        $other = DnsSyncZone::findOne($zone->id);
        $this->sync()->run($other);
        self::assertSame([], $this->cloudflare->batches);
        self::assertContains('Зона уже выполняется', $this->texts($zone));
        self::assertSame('running', DnsSyncZone::findOne($zone->id)->status);

        // A run abandoned more than 10 minutes ago can be restarted.
        DnsSyncZone::updateAll(['started_at' => date('Y-m-d H:i:s', time() - 601)], ['id' => $zone->id]);
        $this->sync()->run(DnsSyncZone::findOne($zone->id));
        self::assertCount(1, $this->cloudflare->batches);
    }

    public function testSameCloudflareZoneInAnotherSync(): void
    {
        $zone = $this->foundZone();
        FakeMutex::$held['dns-sync:zone:a:z1'] = true;
        $this->sync()->run($zone);
        $zone = DnsSyncZone::findOne($zone->id);
        self::assertSame([], $this->cloudflare->batches);
        self::assertSame('error', $zone->status);
        self::assertContains('Зона выполняется в другом синке', $this->texts($zone));
    }

    public function testLargeJsonAndMessageLimit(): void
    {
        $zone = $this->zone();
        $records = array_fill(0, 2000, $this->cfRecord(str_repeat('f', 32), 'TXT', 'a.com', str_repeat('x', 40)));
        $zone->setJson('batch_result', ['deletes' => $records, 'posts' => []]);
        for ($i = 0; $i < 105; $i++) {
            $zone->addMessage('info', "m$i");
        }
        $zone->save(false);
        $zone = DnsSyncZone::findOne($zone->id);
        self::assertGreaterThan(65536, strlen($zone->batch_result));
        self::assertCount(2000, $zone->getJson('batch_result')['deletes']);
        $messages = $zone->getJson('messages');
        self::assertCount(100, $messages);
        self::assertSame('m5', $messages[0]['text']);
    }

    public function testSyncRelations(): void
    {
        $zone = $this->zone();
        self::assertSame('1', (string)$zone->sync->ispServer->id);
        self::assertCount(1, DnsSync::findOne(1)->zones);
    }
}
