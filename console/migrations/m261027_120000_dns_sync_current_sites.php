<?php

use yii\db\Migration;
use yii\db\Query;

/** Replace historical reports with one current DNS state per site. Deploy with DNS actions stopped. */
class m261027_120000_dns_sync_current_sites extends Migration
{
    public function safeUp(): void
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB' : null;
        $json = $this->db->driverName === 'mysql' ? 'LONGTEXT NULL' : $this->text()->null();
        $this->createTable('{{%dns_sync_zone_current}}', [
            'id' => $this->primaryKey(),
            'item_id' => $this->integer()->notNull(),
            'domain' => $this->string(255)->notNull(),
            'selected' => $this->boolean()->notNull()->defaultValue(0),
            'isp_records' => $json,
            'isp_loaded_at' => $this->dateTime()->null(),
            'cf_state' => $this->string(16)->null(),
            'cf_zone_id' => $this->string(64)->null(),
            'cf_account' => $this->string(255)->null(),
            'cf_zone_status' => $this->string(16)->null(),
            'cf_name_servers' => $this->text()->null(),
            'cf_before' => $json,
            'cf_before_loaded_at' => $this->dateTime()->null(),
            'cf_after' => $json,
            'cf_after_loaded_at' => $this->dateTime()->null(),
            'batch_result' => $json,
            'status' => $this->string(16)->notNull()->defaultValue('new'),
            'started_at' => $this->dateTime()->null(),
            'finished_at' => $this->dateTime()->null(),
            'messages' => $json,
            'ns_current' => $this->text()->null(),
            'ns_loaded_at' => $this->dateTime()->null(),
            'FOREIGN KEY ([[item_id]]) REFERENCES {{%item}} ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
        ], $options);
        $this->createIndex('ux_dns_sync_zone_item', '{{%dns_sync_zone_current}}', 'item_id', true);

        $rows = (new Query())->select(['z.*', 'item_domain' => 'i.domain'])
            ->from(['z' => '{{%dns_sync_zone}}'])
            ->innerJoin(['s' => '{{%dns_sync}}'], '[[s.id]] = [[z.sync_id]]')
            ->innerJoin(['i' => '{{%item}}'], '[[i.id]] = [[z.item_id]]')
            ->innerJoin(['server' => '{{%server}}'], '[[server.id]] = [[i.server_id]]')
            ->where(['i.is_archived' => 0])
            ->andWhere('[[s.source_server_id]] = [[i.server_id]] AND [[s.isp_server_id]] = [[i.server_id]]')
            ->orderBy(['s.id' => SORT_DESC, 'z.id' => SORT_DESC]);
        $seen = [];
        foreach ($rows->each(100, $this->db) as $row) {
            $domain = $this->normalize($row['item_domain']);
            if ($domain === '' || $this->normalize($row['domain']) !== $domain || isset($seen[$row['item_id']])) {
                continue;
            }
            $seen[$row['item_id']] = true;
            unset($row['sync_id'], $row['item_domain']);
            $row['domain'] = $domain;
            $row['selected'] = 0;
            if ($row['status'] === 'running') {
                $row['status'] = 'new';
                $row['started_at'] = $row['finished_at'] = null;
            }
            $this->insert('{{%dns_sync_zone_current}}', $row);
        }
        $this->dropTable('{{%dns_sync_zone}}');
        $this->dropTable('{{%dns_sync}}');
        $this->renameTable('{{%dns_sync_zone_current}}', '{{%dns_sync_zone}}');
    }

    private function normalize(string $domain): string
    {
        // Keep migration independent of application models; matches RecordMapper::normalizeName().
        $labels = explode('.', strtolower(rtrim(trim($domain), '.')));
        foreach ($labels as &$label) {
            if ($label !== '' && preg_match('/[^\x20-\x7e]/', $label) && function_exists('idn_to_ascii')) {
                $ascii = idn_to_ascii($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                if ($ascii !== false) {
                    $label = strtolower($ascii);
                }
            }
        }
        unset($label);
        return implode('.', $labels);
    }

    /** Restore the former empty schema; historical data is intentionally not recoverable. */
    public function safeDown(): void
    {
        $this->dropTable('{{%dns_sync_zone}}');
        require_once __DIR__ . '/m261024_120000_create_dns_sync_tables.php';
        require_once __DIR__ . '/m261025_120000_add_ns_to_dns_sync_zone.php';
        (new m261024_120000_create_dns_sync_tables(['db' => $this->db]))->safeUp();
        (new m261025_120000_add_ns_to_dns_sync_zone(['db' => $this->db]))->safeUp();
    }
}
