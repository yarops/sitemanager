<?php

use yii\db\Migration;

class m251018_060001_create_server_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%server_user}}', [
            'id' => $this->primaryKey(),
            'server_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'user_login' => $this->string(128)->notNull(),
            'user_pass' => $this->string(128)->notNull(),
            'ftp_login' => $this->string(128)->null(),
            'ftp_pass' => $this->string(128)->null(),
            'editor_ftp_login' => $this->string(128)->null(),
            'editor_ftp_pass' => $this->string(128)->null(),
        ], $tableOptions);

        $this->createIndex('fk_server_user_server_id', '{{%server_user}}', 'server_id');
        $this->addForeignKey(
            'fk_server_user_server_id',
            '{{%server_user}}',
            'server_id',
            '{{%server}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_server_user_server_id', '{{%server_user}}');
        $this->dropTable('{{%server_user}}');
    }
}
