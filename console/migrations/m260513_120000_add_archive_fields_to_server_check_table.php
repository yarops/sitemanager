<?php

use yii\db\Migration;

class m260513_120000_add_archive_fields_to_server_check_table extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%server_check}}', 'is_archived', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn('{{%server_check}}', 'archived_at', $this->dateTime()->null());
        $this->addColumn('{{%server_check}}', 'archived_by', $this->integer()->null());
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%server_check}}', 'archived_by');
        $this->dropColumn('{{%server_check}}', 'archived_at');
        $this->dropColumn('{{%server_check}}', 'is_archived');
    }
}
