<?php

namespace common\tests\unit\models;

use common\models\ServerUser;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;
use yii\db\Connection;

class ServerUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('Yii', false)) {
            require dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';
        }
        new Application([
            'id' => 'server-user-test',
            'basePath' => dirname(__DIR__),
            'aliases' => ['@common' => dirname(__DIR__, 3)],
            'components' => ['db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:']],
        ]);
        $db = Yii::$app->db;
        $db->createCommand()->createTable('server_user', [
            'id' => 'pk', 'server_id' => 'integer', 'is_archived' => 'integer',
        ])->execute();
        $db->createCommand()->createTable('item', [
            'id' => 'pk', 'server_user_id' => 'integer', 'is_archived' => 'integer',
        ])->execute();
        $db->createCommand()->batchInsert('server_user', ['id', 'server_id', 'is_archived'], [
            [1, 13, 0], [2, 13, 0], [3, 13, 0], [4, 13, 1], [5, 14, 0],
        ])->execute();
        $db->createCommand()->batchInsert('item', ['server_user_id', 'is_archived'], [
            [1, 0], [2, 1], [null, 0],
        ])->execute();
    }

    protected function tearDown(): void
    {
        Yii::$app->db->close();
        Yii::$app = null;
        parent::tearDown();
    }

    public function testIndividualDeletionPreservesUsersWithAnySites(): void
    {
        $this->assertSame(0, ServerUser::deleteWithoutSites(['id' => 1]));
        $this->assertSame(0, ServerUser::deleteWithoutSites(['id' => 2]));
        $this->assertSame(1, ServerUser::deleteWithoutSites(['id' => 3]));
        $this->assertSame(2, (int)Yii::$app->db->createCommand('SELECT COUNT(*) FROM item WHERE server_user_id IS NOT NULL')->queryScalar());
    }

    public function testCleanupDeletesOnlyEmptyUsersOnSelectedServer(): void
    {
        $this->assertSame(2, ServerUser::deleteWithoutSites(['server_id' => 13]));
        $remainingIds = Yii::$app->db->createCommand('SELECT id FROM server_user ORDER BY id')->queryColumn();
        $this->assertSame([1, 2, 5], array_map('intval', $remainingIds));
        $this->assertSame(3, (int)Yii::$app->db->createCommand('SELECT COUNT(*) FROM item')->queryScalar());
        $this->assertSame(0, ServerUser::deleteWithoutSites(['server_id' => 13]));
    }
}
