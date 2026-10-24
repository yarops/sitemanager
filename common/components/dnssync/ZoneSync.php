<?php

namespace common\components\dnssync;

use common\components\cloudflare\DnsRecords;
use common\components\ispmanager\DnsZone;
use common\components\ispmanager\ZoneNotFound;
use common\models\DnsSyncZone;
use Yii;
use yii\mutex\Mutex;
use yii\mutex\MysqlMutex;

/** Actions over one zone of a DNS sync. Every action saves its results and messages in the row. */
class ZoneSync
{
    private DnsZone $isp;
    private DnsRecords $cloudflare;
    private array $config;
    private Mutex $mutex;
    private NameServers $nameServers;

    public function __construct(DnsZone $isp, DnsRecords $cloudflare, array $config, Mutex $mutex, NameServers $nameServers)
    {
        $this->isp = $isp;
        $this->cloudflare = $cloudflare;
        $this->nameServers = $nameServers;
        $this->config = $config;
        $this->mutex = $mutex;
    }

    public static function configured(): self
    {
        return new self(new DnsZone(), DnsRecords::configured(), Yii::$app->params['dnsSync'], new MysqlMutex(), new NameServers());
    }

    /** @return array|null records of synced types, null on failure */
    public function loadIsp(DnsSyncZone $zone): ?array
    {
        $records = null;
        try {
            $server = $zone->sync->ispServer;
            if ($server === null) {
                throw new \RuntimeException('Сервер ISPmanager не найден');
            }
            $records = RecordMapper::filter($this->isp->records($server, $zone->domain), $this->config['types']);
            $zone->setJson('isp_records', $records);
            $zone->isp_loaded_at = date('Y-m-d H:i:s');
            $zone->addMessage('info', 'ISP: ' . count($records) . ' записей');
            if (RecordMapper::isLegacy($records, $zone->domain)) {
                $zone->addMessage('warning', 'Легаси cPanel-зона (MX на корень), синк не поддерживается');
            }
        } catch (ZoneNotFound $exception) {
            $zone->setJson('isp_records', null);
            $zone->isp_loaded_at = date('Y-m-d H:i:s');
            $zone->addMessage('error', 'Нет зоны в ISP');
        } catch (\Throwable $exception) {
            $zone->addMessage('error', 'ISP: ' . $exception->getMessage());
        }
        $zone->save(false);
        return $records;
    }

    public function loadBefore(DnsSyncZone $zone): void
    {
        // Delegation snapshot: which registrar NS the domain uses before the move.
        try {
            $servers = $this->nameServers->lookup($zone->domain);
            $zone->setJson('ns_current', $servers);
            $zone->ns_loaded_at = date('Y-m-d H:i:s');
            $zone->addMessage('info', $servers === null ? 'NS: домен не существует (NXDOMAIN)'
                : 'NS: ' . ($servers ? implode(', ', $servers) : 'нет'));
        } catch (\Throwable $exception) {
            $zone->addMessage('error', 'NS: ' . $exception->getMessage());
        }
        try {
            $found = $this->cloudflare->findZone($zone->domain);
            if ($found['state'] !== DnsSyncZone::CF_FOUND) {
                $zone->cf_state = $found['state'];
                $zone->addMessage(
                    $found['state'] === DnsSyncZone::CF_MISSING ? 'info' : 'error',
                    $found['state'] === DnsSyncZone::CF_MISSING ? 'Нет в Cloudflare' : 'Cloudflare: ' . ($found['message'] ?? $found['state'])
                );
                return;
            }
            if ($zone->cf_before !== null
                && ($zone->cf_account !== $found['account'] || $zone->cf_zone_id !== $found['zone_id'])
            ) {
                // before belongs to another zone: keep the binding, the user decides.
                $zone->cf_state = DnsSyncZone::CF_ERROR;
                $zone->addMessage('error', 'Зона в Cloudflare сменилась после сохранения before');
                return;
            }
            $zone->cf_state = DnsSyncZone::CF_FOUND;
            $zone->cf_account = $found['account'];
            $zone->cf_zone_id = $found['zone_id'];
            $zone->cf_zone_status = $found['status'];
            $zone->setJson('cf_name_servers', $found['name_servers']);
            if ($zone->cf_before !== null) {
                $zone->addMessage('info', 'before уже сохранён');
                return;
            }
            $records = $this->cloudflare->list($found['account'], $found['zone_id']);
            $zone->addMessage('info', $zone->saveBeforeOnce($records, $found['account'], $found['zone_id'])
                ? 'Cloudflare before: ' . count($records) . ' записей'
                : 'before уже сохранён');
        } catch (\Throwable $exception) {
            $zone->addMessage('error', 'Cloudflare: ' . $exception->getMessage());
        } finally {
            $zone->save(false);
        }
    }

    /** Full reload of one zone: delete everything in Cloudflare, create the ISP records, one batch. */
    public function run(DnsSyncZone $zone): void
    {
        if (!$zone->tryLock()) {
            $zone->addMessage('error', 'Зона уже выполняется');
            $zone->save(false, ['messages']);
            return;
        }
        $status = DnsSyncZone::STATUS_ERROR;
        $mutexName = null;
        try {
            $records = $this->loadIsp($zone);
            if (!$records) {
                $zone->addMessage('error', 'Нет записей ISP для синка, Cloudflare не изменён');
                return;
            }
            if (RecordMapper::isLegacy($records, $zone->domain)) {
                $zone->addMessage('error', 'Легаси cPanel-зона, синк не поддерживается');
                return;
            }
            if ($zone->cf_state !== DnsSyncZone::CF_FOUND || !$zone->cf_account || !$zone->cf_zone_id) {
                $zone->addMessage('error', 'Зона не найдена в Cloudflare, синк не выполнен');
                return;
            }
            $posts = RecordMapper::toCloudflare($records, $zone->domain, $this->config);

            $name = 'dns-sync:zone:' . $zone->cf_account . ':' . $zone->cf_zone_id;
            if (!$this->mutex->acquire($name)) {
                $zone->addMessage('error', 'Зона выполняется в другом синке');
                return;
            }
            $mutexName = $name;

            $current = $this->cloudflare->list($zone->cf_account, $zone->cf_zone_id);
            if ($zone->cf_before === null && $zone->saveBeforeOnce($current, $zone->cf_account, $zone->cf_zone_id)) {
                $zone->addMessage('info', 'Cloudflare before: ' . count($current) . ' записей');
            }
            $operations = count($current) + count($posts);
            if ($operations > (int)$this->config['maxBatchOperations']) {
                $zone->addMessage('error', "Batch: $operations операций, лимит {$this->config['maxBatchOperations']}");
                return;
            }

            try {
                $result = $this->cloudflare->batch($zone->cf_account, $zone->cf_zone_id, array_column($current, 'id'), $posts);
                $zone->setJson('batch_result', $result);
                $zone->save(false, ['batch_result']);
                $zone->addMessage('info', 'Удалено ' . count($result['deletes']) . ', создано ' . count($result['posts']));
                $status = DnsSyncZone::STATUS_DONE;
            } catch (\Throwable $exception) {
                $zone->addMessage('error', 'Batch: ' . $exception->getMessage());
            }
            // after is read even when batch failed: the user sees the actual state.
            $this->loadAfter($zone);
        } catch (\Throwable $exception) {
            $zone->addMessage('error', $exception->getMessage());
        } finally {
            if ($mutexName !== null) {
                $this->mutex->release($mutexName);
            }
            $zone->save(false);
            $zone->unlock($status);
        }
    }

    /**
     * Adds a missing root zone to Cloudflare (no DNS import, settings untouched).
     * The records are uploaded afterwards by a regular run.
     */
    public function addZone(DnsSyncZone $zone, string $account): void
    {
        $records = $zone->getJson('isp_records');
        $refusal = null;
        if ($zone->cf_state !== DnsSyncZone::CF_MISSING) {
            $refusal = 'Добавить можно только зону со статусом «Нет в Cloudflare»';
        } elseif ($zone->item === null || $zone->item->parent_id) {
            $refusal = 'Добавляются только корневые сайты';
        } elseif (!$records) {
            $refusal = 'Сначала загрузите записи ISP';
        } elseif (RecordMapper::isLegacy($records, $zone->domain)) {
            $refusal = 'Легаси cPanel-зона, добавление не поддерживается';
        } elseif (!in_array($account, $this->cloudflare->accountLabels(), true)) {
            $refusal = 'Неизвестный аккаунт Cloudflare';
        }
        if ($refusal !== null || !$zone->tryLock()) {
            $zone->addMessage('error', $refusal ?? 'Зона уже выполняется');
            $zone->save(false, ['messages']);
            return;
        }
        $status = DnsSyncZone::STATUS_ERROR;
        $mutexName = null;
        try {
            $name = 'dns-sync:domain:' . $zone->domain;
            if (!$this->mutex->acquire($name)) {
                $zone->addMessage('error', 'Зона выполняется в другом синке');
                return;
            }
            $mutexName = $name;
            // The zone may have been added since the Cloudflare load: create only on a repeated "missing".
            $found = $this->cloudflare->findZone($zone->domain);
            if ($found['state'] === DnsSyncZone::CF_FOUND) {
                $zone->cf_state = DnsSyncZone::CF_FOUND;
                $zone->cf_account = $found['account'];
                $zone->cf_zone_id = $found['zone_id'];
                $zone->cf_zone_status = $found['status'];
                $zone->setJson('cf_name_servers', $found['name_servers']);
                $zone->addMessage('warning', 'Зона уже есть в Cloudflare, загрузите Cloudflare перед синком');
                $status = DnsSyncZone::STATUS_NEW;
                return;
            }
            if ($found['state'] === DnsSyncZone::CF_AMBIGUOUS) {
                $zone->cf_state = DnsSyncZone::CF_AMBIGUOUS;
                $zone->addMessage('error', 'Найдено несколько зон в Cloudflare');
                return;
            }
            if ($found['state'] !== DnsSyncZone::CF_MISSING) {
                $zone->addMessage('error', $found['message'] ?? 'Поиск неполный');
                return;
            }
            $created = $this->cloudflare->createZone($account, $zone->domain);
            $zone->cf_state = DnsSyncZone::CF_FOUND;
            $zone->cf_zone_status = $created['status'];
            $zone->setJson('cf_name_servers', $created['name_servers']);
            // A new zone is empty: the empty snapshot is its before.
            if (!$zone->saveBeforeOnce([], $account, $created['zone_id'])) {
                $zone->addMessage('warning', 'before был сохранён раньше и не перезаписан');
            }
            $zone->cf_account = $account;
            $zone->cf_zone_id = $created['zone_id'];
            $zone->addMessage('info', 'Зона создана, NS: ' . implode(', ', $created['name_servers']));
            $status = DnsSyncZone::STATUS_NEW;
        } catch (\Throwable $exception) {
            $zone->addMessage('error', 'Cloudflare: ' . $exception->getMessage());
        } finally {
            if ($mutexName !== null) {
                $this->mutex->release($mutexName);
            }
            $zone->save(false);
            $zone->unlock($status);
        }
    }

    public function loadAfter(DnsSyncZone $zone): void
    {
        try {
            if (!$zone->cf_account || !$zone->cf_zone_id) {
                throw new \RuntimeException('зона Cloudflare не определена');
            }
            $records = $this->cloudflare->list($zone->cf_account, $zone->cf_zone_id);
            $zone->setJson('cf_after', $records);
            $zone->cf_after_loaded_at = date('Y-m-d H:i:s');
            $message = 'Cloudflare after: ' . count($records) . ' записей';
            $expected = $zone->getJson('isp_records');
            if ($expected !== null) {
                $same = RecordMapper::formatForDisplay($records, $zone->domain)
                    === RecordMapper::formatForDisplay(RecordMapper::toCloudflare($expected, $zone->domain, $this->config), $zone->domain);
                $zone->addMessage($same ? 'info' : 'warning', $message . ($same ? ', совпадает с ISP' : ', отличается от ISP'));
            } else {
                $zone->addMessage('info', $message);
            }
        } catch (\Throwable $exception) {
            $zone->addMessage('error', 'Cloudflare after: ' . $exception->getMessage());
        } finally {
            $zone->save(false);
        }
    }
}
