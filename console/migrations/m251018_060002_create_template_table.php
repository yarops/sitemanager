<?php

use yii\db\Migration;

class m251018_060002_create_template_table extends Migration
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

        $this->createTable('{{%template}}', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->null(),
            'title' => $this->string(255)->notNull(),
            'link' => $this->string(512)->notNull(),
            'image_link' => $this->string(512)->notNull(),
            'content' => $this->text()->null(),
            'publish_date' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex('FK_template_parent', '{{%template}}', 'parent_id');
        $this->addForeignKey(
            'FK_template_parent',
            '{{%template}}',
            'parent_id',
            '{{%template}}',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('FK_template_parent', '{{%template}}');
        $this->dropTable('{{%template}}');
    }
}
