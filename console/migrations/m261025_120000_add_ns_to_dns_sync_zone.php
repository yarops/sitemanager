<?php

use yii\db\Migration;

/** Snapshot of the domain delegation (NS) taken before the sync, with the Cloudflare load. */
class m261025_120000_add_ns_to_dns_sync_zone extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%dns_sync_zone}}', 'ns_current', $this->text()->null());
        $this->addColumn('{{%dns_sync_zone}}', 'ns_loaded_at', $this->dateTime()->null());
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%dns_sync_zone}}', 'ns_loaded_at');
        $this->dropColumn('{{%dns_sync_zone}}', 'ns_current');
    }
}
