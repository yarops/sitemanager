<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * One zone of a DNS sync: ISP records, Cloudflare before/after, run state and messages.
 * JSON columns hold arrays; cf_before NULL means "not loaded", [] is a saved empty snapshot.
 *
 * @property int $id
 * @property int $sync_id
 * @property int|null $item_id
 * @property string $domain
 * @property int $selected
 * @property string|null $isp_records
 * @property string|null $isp_loaded_at
 * @property string|null $cf_state
 * @property string|null $cf_zone_id
 * @property string|null $cf_account
 * @property string|null $cf_zone_status
 * @property string|null $cf_name_servers
 * @property string|null $cf_before
 * @property string|null $cf_before_loaded_at
 * @property string|null $cf_after
 * @property string|null $cf_after_loaded_at
 * @property string|null $batch_result
 * @property string $status
 * @property string|null $started_at
 * @property string|null $finished_at
 * @property string|null $messages
 *
 * @property DnsSync $sync
 * @property Item|null $item
 */
class DnsSyncZone extends ActiveRecord
{
    public const CF_FOUND = 'found';
    public const CF_MISSING = 'missing';
    public const CF_AMBIGUOUS = 'ambiguous';
    public const CF_ERROR = 'error';

    public const STATUS_NEW = 'new';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_ERROR = 'error';

    /** A run older than this is considered dead (PHP aborted) and may be restarted. */
    public const LOCK_TIMEOUT = 600;
    public const MAX_MESSAGES = 100;

    public static function tableName(): string
    {
        return '{{%dns_sync_zone}}';
    }

    public function rules(): array
    {
        return [
            [['sync_id', 'domain'], 'required'],
            [['sync_id', 'item_id'], 'integer'],
            [['selected'], 'boolean'],
            [['domain'], 'string', 'max' => 255],
        ];
    }

    public function getSync(): ActiveQuery
    {
        return $this->hasOne(DnsSync::class, ['id' => 'sync_id']);
    }

    public function getItem(): ActiveQuery
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findInSync(int $syncId, int $id): DnsSyncZone
    {
        if (($model = DnsSyncZone::findOne(['id' => $id, 'sync_id' => $syncId])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested zone does not exist.');
    }

    public function getJson(string $attribute): ?array
    {
        $value = $this->getAttribute($attribute);
        if ($value === null || $value === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function setJson(string $attribute, ?array $value): void
    {
        $this->setAttribute($attribute, $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** Appends to the "Статус / инфо" log in memory; the caller saves the row. */
    public function addMessage(string $level, string $text): void
    {
        $messages = $this->getJson('messages') ?? [];
        $messages[] = ['at' => date('Y-m-d H:i:s'), 'level' => $level, 'text' => $text];
        $this->setJson('messages', array_slice($messages, -self::MAX_MESSAGES));
    }

    /** Atomically marks the zone as running unless another live run holds it. */
    public function tryLock(): bool
    {
        $now = date('Y-m-d H:i:s');
        $locked = static::updateAll(
            ['status' => self::STATUS_RUNNING, 'started_at' => $now, 'finished_at' => null],
            ['and', ['id' => $this->id], ['or',
                ['!=', 'status', self::STATUS_RUNNING],
                ['started_at' => null],
                ['<', 'started_at', date('Y-m-d H:i:s', time() - self::LOCK_TIMEOUT)],
            ]]
        ) === 1;
        if ($locked) {
            $this->assignSaved(['status' => self::STATUS_RUNNING, 'started_at' => $now, 'finished_at' => null]);
        }
        return $locked;
    }

    public function unlock(string $status): void
    {
        $values = ['status' => $status, 'finished_at' => date('Y-m-d H:i:s')];
        static::updateAll($values, ['id' => $this->id]);
        $this->assignSaved($values);
    }

    /**
     * Saves the Cloudflare snapshot before the first change, once per zone row.
     * Binds the row to the Cloudflare zone the snapshot belongs to.
     */
    public function saveBeforeOnce(array $records, string $account, string $zoneId): bool
    {
        $values = [
            'cf_before' => json_encode($records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'cf_before_loaded_at' => date('Y-m-d H:i:s'),
            'cf_account' => $account,
            'cf_zone_id' => $zoneId,
        ];
        if (static::updateAll($values, ['id' => $this->id, 'cf_before' => null]) !== 1) {
            $this->assignSaved(static::find()->select(array_keys($values))->where(['id' => $this->id])->asArray()->one() ?: []);
            return false;
        }
        $this->assignSaved($values);
        return true;
    }

    /** Updates attributes already written by updateAll so they are not saved again as dirty. */
    private function assignSaved(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->setAttribute($name, $value);
            $this->setOldAttribute($name, $value);
        }
    }
}
