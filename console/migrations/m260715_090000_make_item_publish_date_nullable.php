<?php

use yii\db\Migration;

class m260715_090000_make_item_publish_date_nullable extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%item}}', 'publish_date', $this->timestamp()->null()->defaultValue(null));
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{%item}}',
            'publish_date',
            $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
        );
    }
}
