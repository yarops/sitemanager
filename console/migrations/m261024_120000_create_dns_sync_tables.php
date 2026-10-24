<?php

use yii\db\Migration;

/** DNS sync ISPmanager → Cloudflare: one sync per server pair, one row per zone with all loaded data. */
class m261024_120000_create_dns_sync_tables extends Migration
{
    public function safeUp(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB';
        }
        // Record snapshots and batch results can exceed 64 KiB of TEXT.
        $json = $this->db->driverName === 'mysql' ? 'LONGTEXT NULL' : $this->text()->null();

        $this->createTable('{{%dns_sync}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'source_server_id' => $this->integer()->notNull(),
            'isp_server_id' => $this->integer()->notNull(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%dns_sync_zone}}', [
            'id' => $this->primaryKey(),
            'sync_id' => $this->integer()->notNull(),
            'item_id' => $this->integer()->null(),
            'domain' => $this->string(255)->notNull(),
            'selected' => $this->boolean()->notNull()->defaultValue(0),
            'isp_records' => $json,
            'isp_loaded_at' => $this->dateTime()->null(),
            'cf_state' => $this->string(16)->null(),
            'cf_zone_id' => $this->string(64)->null(),
            'cf_account' => $this->string(255)->null(),
            'cf_zone_status' => $this->string(16)->null(),
            'cf_name_servers' => $this->text()->null(),
            'cf_before' => $json,
            'cf_before_loaded_at' => $this->dateTime()->null(),
            'cf_after' => $json,
            'cf_after_loaded_at' => $this->dateTime()->null(),
            'batch_result' => $json,
            'status' => $this->string(16)->notNull()->defaultValue('new'),
            'started_at' => $this->dateTime()->null(),
            'finished_at' => $this->dateTime()->null(),
            'messages' => $json,
        ], $tableOptions);

        $this->createIndex('ux_dns_sync_zone_sync_domain', '{{%dns_sync_zone}}', ['sync_id', 'domain'], true);
        $this->createIndex('ix_dns_sync_zone_sync', '{{%dns_sync_zone}}', 'sync_id');
        $this->addForeignKey('fk_dns_sync_zone_sync', '{{%dns_sync_zone}}', 'sync_id', '{{%dns_sync}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_dns_sync_zone_sync', '{{%dns_sync_zone}}');
        $this->dropTable('{{%dns_sync_zone}}');
        $this->dropTable('{{%dns_sync}}');
    }
}
