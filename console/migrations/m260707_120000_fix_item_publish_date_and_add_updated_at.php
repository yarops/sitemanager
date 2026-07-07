<?php

use yii\db\Migration;

class m260707_120000_fix_item_publish_date_and_add_updated_at extends Migration
{
    public function safeUp()
    {
        $this->alterColumn(
            '{{%item}}',
            'publish_date',
            $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
        );

        $this->addColumn(
            '{{%item}}',
            'updated_at',
            $this->dateTime()->null()->after('publish_date')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%item}}', 'updated_at');

        $this->alterColumn(
            '{{%item}}',
            'publish_date',
            $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
        );
    }
}
