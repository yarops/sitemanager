<?php

namespace common\tests\unit\models;

use common\components\check\WorkerCheck;
use common\components\SiteNotification;
use common\models\Check;
use common\models\Item;
use console\controllers\SiteMonitorController;
use frontend\controllers\ItemController;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\db\Connection;
use yii\db\Query;

class DemoMonitoringTest extends TestCase
{
    private $previousApplication;

    protected function setUp(): void
    {
        $this->previousApplication = \Yii::$app;
        new Application([
            'id' => 'demo-monitoring-test',
            'basePath' => dirname(__DIR__, 4),
            'components' => ['db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:']],
        ]);
        \Yii::$app->db->createCommand('CREATE TABLE item (
            id INTEGER PRIMARY KEY, domain TEXT, protocol TEXT, publish_status TEXT,
            check_enabled INTEGER, is_archived INTEGER, notify_strategy TEXT
        )')->execute();
        foreach ([['demo', 1], ['demo', 0], ['publish', 1], ['publish', 0]] as $index => $site) {
            \Yii::$app->db->createCommand()->insert('item', [
                'id' => $index + 1, 'domain' => 'example.invalid', 'protocol' => 'https',
                'publish_status' => $site[0], 'check_enabled' => $site[1],
                'is_archived' => 0, 'notify_strategy' => Item::NOTIFY_IMMEDIATE,
            ])->execute();
        }
    }

    protected function tearDown(): void
    {
        \Yii::$app->db->close();
        Item::getDb()->getSchema()->refresh();
        \Yii::$app = $this->previousApplication;
    }

    public function testQueuedDemoIsSkippedBeforeNetworkAndPersistence(): void
    {
        // No check table or queue exists: touching either would fail this test.
        (new WorkerCheck(['item_id' => 1, 'url' => 'https://example.invalid']))->execute(null);
        $this->assertSame(4, (int)(new Query())->from('item')->count());
    }

    public function testResultIsDiscardedAfterStatusChanges(): void
    {
        $item = Item::findOne(3);
        \Yii::$app->db->createCommand()->update('item', ['publish_status' => Item::STATUS_DEMO], ['id' => 3])->execute();
        $controller = new SiteMonitorController('site-monitor', \Yii::$app);
        $method = new \ReflectionMethod($controller, 'saveCheckResult');
        $method->setAccessible(true);
        $method->invoke($controller, $item, ['status' => '500']);
        $this->assertTrue($item->isDemo());
    }

    public function testNotificationsReloadCurrentStatus(): void
    {
        $item = Item::findOne(3);
        \Yii::$app->db->createCommand()->update('item', ['publish_status' => Item::STATUS_DEMO], ['id' => 3])->execute();
        $notification = new SiteNotification();
        $this->assertFalse($notification->sendDownAlert($item, new Check()));
        $this->assertFalse($notification->sendUpAlert($item, new Check()));
        $this->assertFalse($notification->sendTelegramNotification($item, new Check()));
    }

    public function testMigrationExtendsEnumAndGuardsRollback(): void
    {
        require_once dirname(__DIR__, 4) . '/console/migrations/m261010_130000_add_demo_item_status.php';
        $migration = new class extends \m261010_130000_add_demo_item_status {
            public array $definitions = [];

            public function alterColumn($table, $column, $type)
            {
                $this->definitions[] = [$table, $column, $type];
            }
        };
        $migration->up();
        $this->assertSame("ENUM('draft', 'publish', 'demo') NOT NULL DEFAULT 'draft'", $migration->definitions[0][2]);
        ob_start();
        try {
            $this->assertFalse($migration->down());
        } finally {
            ob_end_clean();
        }
        $this->assertCount(1, $migration->definitions);
        \Yii::$app->db->createCommand()->update('item', ['publish_status' => Item::STATUS_DRAFT], ['publish_status' => Item::STATUS_DEMO])->execute();
        $migration->down();
        $this->assertSame("ENUM('draft', 'publish') NOT NULL DEFAULT 'draft'", $migration->definitions[1][2]);
    }

    public function testMonitoringFiltersRespectDemoStatus(): void
    {
        $controller = new ItemController('item', \Yii::$app);
        $method = new \ReflectionMethod($controller, 'applyStatusFilter');
        $method->setAccessible(true);
        $enabled = $method->invoke($controller, Item::find(), 'enabled');
        $disabled = $method->invoke($controller, Item::find(), 'disabled');
        $this->assertSame([3], array_map('intval', $enabled->select('id')->column()));
        $this->assertSame([1, 2, 4], array_map('intval', $disabled->select('id')->orderBy('id')->column()));
        $this->assertSame([1, 2], array_map('intval', Item::find()->where(['publish_status' => Item::STATUS_DEMO])->select('id')->column()));
        \Yii::$app->db->createCommand('CREATE TABLE `check` (item_id INTEGER, check_status TEXT, check_date TEXT)')->execute();
        foreach (['200', '500'] as $status) {
            \Yii::$app->db->createCommand()->delete('check')->execute();
            foreach ([1, 2, 3] as $id) {
                \Yii::$app->db->createCommand()->insert('check', ['item_id' => $id, 'check_status' => $status, 'check_date' => '2026-10-10'])->execute();
            }
            $query = $method->invoke($controller, Item::find(), $status === '200' ? 'up' : 'down');
            $this->assertSame([3], array_map('intval', $query->select('item.id')->column()));
        }
        \Yii::$app->db->createCommand()->delete('check')->execute();
        $query = $method->invoke($controller, Item::find(), 'never_checked');
        $this->assertSame([3], array_map('intval', $query->select('item.id')->column()));
    }
}
