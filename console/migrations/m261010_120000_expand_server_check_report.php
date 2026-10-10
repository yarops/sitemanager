<?php

use yii\db\Migration;

/** Allow reports containing HTTP, Cloudflare and ISPmanager snapshots to exceed 64 KiB. */
class m261010_120000_expand_server_check_report extends Migration
{
    public function up()
    {
        $type = $this->db->driverName === 'mysql' ? 'LONGTEXT' : $this->text();
        $this->alterColumn('{{%server_check}}', 'report', $type);
    }

    public function down()
    {
        echo "Cannot shrink report storage safely: existing reports may exceed the previous limit.\n";
        return false;
    }
}
