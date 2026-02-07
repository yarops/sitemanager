<?php

use yii\db\Migration;

class m260207_090632_add_monitoring_fields_to_item_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%item}}', 'check_interval', $this->integer()->defaultValue(1440)->comment('Интервал проверки в минутах'));
        $this->addColumn('{{%item}}', 'notify_strategy', $this->string(32)->defaultValue('summary')->comment('Стратегия уведомлений'));
        $this->addColumn('{{%item}}', 'next_check_at', $this->dateTime()->comment('Время следующей проверки'));

        // Инициализируем next_check_at для существующих записей с включенным мониторингом
        $this->execute("UPDATE {{%item}} SET next_check_at = NOW() WHERE check_enabled = 1");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%item}}', 'next_check_at');
        $this->dropColumn('{{%item}}', 'notify_strategy');
        $this->dropColumn('{{%item}}', 'check_interval');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260207_090632_add_monitoring_fields_to_item_table cannot be reverted.\n";

        return false;
    }
    */
}
