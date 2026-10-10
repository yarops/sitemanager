<?php

use yii\db\Migration;
use yii\db\Query;

class m261010_130000_add_demo_item_status extends Migration
{
    public function up()
    {
        $this->alterColumn('{{%item}}', 'publish_status', "ENUM('draft', 'publish', 'demo') NOT NULL DEFAULT 'draft'");
    }

    public function down()
    {
        if ((new Query())->from('{{%item}}')->where(['publish_status' => 'demo'])->exists()) {
            echo "Cannot remove demo status while demo sites exist.\n";
            return false;
        }
        $this->alterColumn('{{%item}}', 'publish_status', "ENUM('draft', 'publish') NOT NULL DEFAULT 'draft'");
    }
}
