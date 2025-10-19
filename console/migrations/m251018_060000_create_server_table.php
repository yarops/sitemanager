<?php

use yii\db\Migration;

class m251018_060000_create_server_table extends Migration
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

        $this->createTable('{{%server}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'link' => $this->string(255)->notNull(),
            'ip' => $this->string(255)->null(),
            'credentials' => $this->text()->notNull(),
            'password' => $this->string(255)->null(),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%server}}');
    }
}
