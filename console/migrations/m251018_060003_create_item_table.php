<?php

use common\models\User;
use yii\db\Migration;

class m251018_060003_create_item_table extends Migration
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

        $this->createTable('{{%item}}', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->null(),
            'domain' => $this->string(255)->notNull(),
            'alias' => $this->string(255)->null(),
            'protocol' => $this->string(8)->notNull()->defaultValue('https'),
            'admin_link' => $this->string(512)->notNull(),
            'content' => $this->text()->notNull(),
            'server_id' => $this->integer()->null(),
            'server_user_id' => $this->integer()->null(),
            'template_id' => $this->integer()->null(),
            'check_enabled' => $this->smallInteger()->notNull()->defaultValue(0),
            'author_id' => $this->integer()->null(),
            'publish_status' => "ENUM('draft', 'publish') NOT NULL DEFAULT 'draft'",
            'publish_date' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // Создаем индексы.
        $this->createIndex('FK_post_author', '{{%item}}', 'author_id');
        $this->createIndex('FK_post_category', '{{%item}}', 'server_id');
        $this->createIndex('FK_item_payment', '{{%item}}', 'template_id');
        $this->createIndex('FK_item_parent', '{{%item}}', 'parent_id');

        // Создаем внешние ключи.
        $this->addForeignKey(
            'FK_item_author',
            '{{%item}}',
            'author_id',
            User::tableName(),
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'FK_item_morgue',
            '{{%item}}',
            'server_id',
            '{{%server}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'FK_item_parent',
            '{{%item}}',
            'parent_id',
            '{{%item}}',
            'id'
        );

        $this->addForeignKey(
            'FK_item_payment',
            '{{%item}}',
            'template_id',
            '{{%template}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('FK_item_author', '{{%item}}');
        $this->dropForeignKey('FK_item_morgue', '{{%item}}');
        $this->dropForeignKey('FK_item_parent', '{{%item}}');
        $this->dropForeignKey('FK_item_payment', '{{%item}}');
        $this->dropTable('{{%item}}');
    }
}
