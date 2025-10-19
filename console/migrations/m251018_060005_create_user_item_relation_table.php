<?php

use yii\db\Migration;

class m251018_060005_create_user_item_relation_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user_item_relation}}', [
            'id' => $this->primaryKey(),
            'relation_user_id' => $this->integer()->notNull(),
            'relation_item_id' => $this->integer()->notNull(),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user_item_relation}}');
    }
}
