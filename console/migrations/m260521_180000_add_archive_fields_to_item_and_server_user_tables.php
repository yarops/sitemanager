<?php

use yii\db\Migration;

class m260521_180000_add_archive_fields_to_item_and_server_user_tables extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%item}}', 'is_archived', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn('{{%item}}', 'archived_at', $this->dateTime()->null());
        $this->addColumn('{{%item}}', 'archived_by', $this->integer()->null());

        $this->addColumn('{{%server_user}}', 'is_archived', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn('{{%server_user}}', 'archived_at', $this->dateTime()->null());
        $this->addColumn('{{%server_user}}', 'archived_by', $this->integer()->null());

        $this->createIndex('idx_item_is_archived', '{{%item}}', 'is_archived');
        $this->createIndex('idx_item_server_archive', '{{%item}}', ['server_id', 'is_archived']);
        $this->createIndex('idx_item_server_user_archive', '{{%item}}', ['server_user_id', 'is_archived']);
        $this->createIndex('idx_server_user_is_archived', '{{%server_user}}', 'is_archived');
        $this->createIndex('idx_server_user_server_archive', '{{%server_user}}', ['server_id', 'is_archived']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_server_user_server_archive', '{{%server_user}}');
        $this->dropIndex('idx_server_user_is_archived', '{{%server_user}}');
        $this->dropIndex('idx_item_server_user_archive', '{{%item}}');
        $this->dropIndex('idx_item_server_archive', '{{%item}}');
        $this->dropIndex('idx_item_is_archived', '{{%item}}');

        $this->dropColumn('{{%server_user}}', 'archived_by');
        $this->dropColumn('{{%server_user}}', 'archived_at');
        $this->dropColumn('{{%server_user}}', 'is_archived');

        $this->dropColumn('{{%item}}', 'archived_by');
        $this->dropColumn('{{%item}}', 'archived_at');
        $this->dropColumn('{{%item}}', 'is_archived');
    }
}
