<?php

namespace common\models;

use common\components\dnssync\RecordMapper;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * Current DNS state of a site: ISP records, Cloudflare before/after, run state and messages.
 * JSON columns hold arrays; cf_before NULL means "not loaded", [] is a saved empty snapshot.
 *
 * @property int $id
 * @property int $item_id
 * @property string $domain
 * @property int $selected
 * @property string|null $isp_records
 * @property string|null $isp_loaded_at
 * @property string|null $ns_current
 * @property string|null $ns_loaded_at
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
            [['item_id', 'domain'], 'required'],
            [['item_id'], 'integer'],
            [['item_id'], 'unique'],
            [['item_id'], 'exist', 'targetClass' => Item::class, 'targetAttribute' => 'id'],
            [['selected'], 'boolean'],
            [['domain'], 'string', 'max' => 255],
        ];
    }

    public function getItem(): ActiveQuery
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    /** Reject requests from tabs whose site has moved, changed domain or been archived. */
    public static function findForServer(int $serverId, int $id, string $domain): self
    {
        $model = static::find()->with('item.server')->where(['id' => $id])->one();
        $item = $model ? $model->item : null;
        if ($item === null || $item->server === null || $item->is_archived || (int)$item->server_id !== $serverId
            || $model->domain !== $domain || $domain !== RecordMapper::normalizeName((string)$item->domain)
        ) {
            throw new NotFoundHttpException('Список сайтов изменился. Обновите страницу DNS.');
        }
        return $model;
    }

    /** Use the same representative and domain deduplication as the former report builder. */
    public static function currentItems(Server $server): array
    {
        $items = $server->getItems(true)->query->with(['childs', 'serverUser', 'server'])
            ->orderBy(['parent_id' => SORT_ASC, 'id' => SORT_ASC])->all();
        $domains = [];
        $result = [];
        foreach ($items as $item) {
            $domain = RecordMapper::normalizeName((string)$item->domain);
            if ($domain !== '' && !isset($domains[$domain])) {
                $domains[$domain] = true;
                $result[] = $item;
            }
        }
        return $result;
    }

    /** Lazily initialize site state; a unique item_id makes simultaneous opens safe. */
    public static function forItems(array $items): array
    {
        $states = static::find()->where(['item_id' => array_map(static fn(Item $item) => $item->id, $items)])
            ->indexBy('item_id')->all();
        $result = [];
        foreach ($items as $item) {
            $domain = RecordMapper::normalizeName((string)$item->domain);
            $model = $states[$item->id] ?? null;
            if ($model === null) {
                static::getDb()->createCommand()->upsert(static::tableName(), [
                    'item_id' => $item->id, 'domain' => $domain,
                ], false)->execute();
                $model = static::findOne(['item_id' => $item->id]);
            }
            if ($model->domain !== $domain) {
                // Do not erase a live operation; the next refresh can reset it after it finishes.
                $values = array_fill_keys(array_keys($model->getAttributes(null, ['id', 'item_id'])), null);
                $values['domain'] = $domain;
                $values['selected'] = 0;
                $values['status'] = self::STATUS_NEW;
                static::updateAll($values, ['and', ['id' => $model->id, 'domain' => $model->domain], ['or',
                    ['!=', 'status', self::STATUS_RUNNING], ['started_at' => null],
                    ['<', 'started_at', date('Y-m-d H:i:s', time() - self::LOCK_TIMEOUT)],
                ]]);
                $model->refresh();
                if ($model->domain !== $domain) {
                    continue;
                }
            }
            $model->populateRelation('item', $item);
            $result[] = $model;
        }
        usort($result, static fn(self $a, self $b) => strcmp($a->domain, $b->domain));
        return $result;
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
            ['and', ['id' => $this->id, 'domain' => $this->domain], ['or',
                ['!=', 'status', self::STATUS_RUNNING],
                ['started_at' => null],
                ['<', 'started_at', date('Y-m-d H:i:s', time() - self::LOCK_TIMEOUT)],
            ]]
        ) === 1;
        if ($locked) {
            $this->refresh();
        }
        return $locked;
    }

    public function unlock(string $status): void
    {
        $values = ['status' => $status, 'finished_at' => date('Y-m-d H:i:s')];
        static::updateAll($values, ['id' => $this->id]);
        $this->assignSaved($values);
    }

    /** Replace the last Cloudflare snapshot while holding the zone operation lock. */
    public function saveBefore(array $records, string $account, string $zoneId): void
    {
        $values = [
            'cf_before' => json_encode($records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'cf_before_loaded_at' => date('Y-m-d H:i:s'),
            'cf_account' => $account,
            'cf_zone_id' => $zoneId,
        ];
        static::updateAll($values, ['id' => $this->id]);
        $this->assignSaved($values);
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
