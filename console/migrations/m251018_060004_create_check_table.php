<?php

use yii\db\Migration;

class m251018_060004_create_check_table extends Migration
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

        $this->createTable('{{%check}}', [
            'id' => $this->primaryKey(),
            'job_id' => $this->string(255)->null(),
            'item_id' => $this->integer()->notNull(),
            'check_status' => $this->string(255)->null(),
            'check_date' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'response_time' => $this->integer()->null()->comment('Response time in milliseconds'),
            'error_message' => $this->string(255)->null()->comment('Error message if check failed'),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%check}}');
    }
}
