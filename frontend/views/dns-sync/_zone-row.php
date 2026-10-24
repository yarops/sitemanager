<?php

use common\components\cloudflare\DnsRecords;
use common\components\dnssync\RecordMapper;
use common\models\DnsSyncZone;
use yii\helpers\Html;

/** @var $this yii\web\View */
/** @var $zone DnsSyncZone */

$config = Yii::$app->params['dnsSync'];
$isp = $zone->getJson('isp_records');
$ispLines = null;
$legacy = false;
if ($isp !== null) {
    $legacy = RecordMapper::isLegacy($isp, $zone->domain);
    try {
        // Expected Cloudflare state, with the proxied flags the sync will set.
        $ispLines = RecordMapper::formatForDisplay(RecordMapper::toCloudflare($isp, $zone->domain, $config), $zone->domain);
    } catch (\Throwable $exception) {
        $ispLines = RecordMapper::formatForDisplay($isp, $zone->domain);
    }
}
$before = $zone->getJson('cf_before');
$after = $zone->getJson('cf_after');
$messages = $zone->getJson('messages') ?? [];
$nameServers = $zone->getJson('cf_name_servers') ?? [];
$item = $zone->item;
$childs = $item ? $item->childs : [];
$alias = $item && !empty($item->alias) && $item->alias !== $item->domain ? $item->alias : null;
$dimmed = $zone->cf_state !== DnsSyncZone::CF_FOUND || empty($isp);

$records = static function (?array $lines, ?string $loadedAt): string {
    if ($lines === null) {
        return '<span class="text-muted">—</span>';
    }
    $html = '<div class="small text-muted">' . count($lines) . ' зап.' . ($loadedAt ? ' · ' . Html::encode($loadedAt) : '') . '</div>';
    foreach ($lines as $line) {
        $html .= '<div class="dns-sync-record">' . Html::encode($line) . '</div>';
    }
    return $html;
};
$badge = static fn(string $text, string $class): string => Html::tag('span', Html::encode($text), ['class' => "badge text-bg-$class me-1"]);
$cfClass = [
    DnsSyncZone::CF_FOUND => 'success', DnsSyncZone::CF_MISSING => 'secondary',
    DnsSyncZone::CF_AMBIGUOUS => 'danger', DnsSyncZone::CF_ERROR => 'danger',
];
$statusClass = [
    DnsSyncZone::STATUS_NEW => 'light', DnsSyncZone::STATUS_RUNNING => 'info',
    DnsSyncZone::STATUS_DONE => 'success', DnsSyncZone::STATUS_ERROR => 'danger',
];
$levelClass = ['info' => '', 'warning' => 'text-warning-emphasis', 'error' => 'text-danger'];
$message = static fn(array $m): string => Html::tag('div',
    Html::tag('span', Html::encode(substr((string)$m['at'], 11)), ['class' => 'text-muted me-1']) . Html::encode($m['text']),
    ['class' => 'small ' . ($levelClass[$m['level']] ?? '')]
);
?>
<tbody data-zone-id="<?= (int)$zone->id ?>" data-status="<?= Html::encode($zone->status) ?>">
<tr class="<?= $childs ? 'table-warning' : '' ?> <?= $dimmed ? 'dns-sync-dimmed' : '' ?>">
    <td>
        <?php if ($item && $item->serverUser): ?>
            <div class="small text-muted">
                <?= Html::encode($item->serverUser->user_login) ?>
                <?= Html::button('<i class="bi bi-clipboard"></i>', [
                    'class' => 'btn btn-link btn-sm p-0 ms-1 align-baseline dns-sync-copy',
                    'data-copy' => $item->serverUser->user_login, 'title' => 'Копировать пользователя',
                ]) ?>
            </div>
        <?php endif; ?>
        <strong><?= Html::encode($zone->domain) ?></strong>
        <?= Html::button('<i class="bi bi-clipboard"></i>', [
            'class' => 'btn btn-link btn-sm p-0 ms-1 align-baseline dns-sync-copy',
            'data-copy' => $zone->domain, 'title' => 'Копировать домен',
        ]) ?>
        <?php if ($item): ?>
            <div class="small"><?= Html::a('сайт #' . (int)$item->id, ['item/view', 'id' => $item->id], ['target' => '_blank']) ?>
                <?= $item->publish_status !== 'publish' ? Html::encode($item->publish_status) : '' ?>
                <?= $item->parent_id ? 'поддомен' : '' ?></div>
        <?php endif; ?>
        <?php if ($childs): ?>
            <div class="small text-warning-emphasis">есть поддомены</div>
        <?php endif; ?>
        <?php if ($zone->ns_loaded_at): ?>
            <?php $ns = $zone->getJson('ns_current'); ?>
            <?= $this->render('_ns-block', [
                'title' => 'NS',
                'servers' => $ns,
                'empty' => $ns === null ? 'домен не существует' : 'нет',
            ]) ?>
        <?php endif; ?>
        <?php if ($nameServers): ?>
            <?= $this->render('_ns-block', ['title' => 'Cloudflare NS', 'servers' => $nameServers, 'empty' => 'нет']) ?>
        <?php endif; ?>
    </td>
    <td><?= $records($ispLines, $zone->isp_loaded_at) ?></td>
    <td><?= $records($before === null ? null : RecordMapper::formatForDisplay($before, $zone->domain), $zone->cf_before_loaded_at) ?></td>
    <td><?= $records($after === null ? null : RecordMapper::formatForDisplay($after, $zone->domain), $zone->cf_after_loaded_at) ?></td>
    <td class="dns-sync-status">
        <div class="mb-1">
            <?= $zone->cf_state ? $badge('CF: ' . $zone->cf_state, $cfClass[$zone->cf_state] ?? 'secondary') : '' ?>
            <?= $badge($zone->status, $statusClass[$zone->status] ?? 'secondary') ?>
            <?= $legacy ? $badge('легаси cPanel', 'warning') : '' ?>
        </div>
        <?php if ($zone->cf_zone_id): ?>
            <div class="small text-muted">
                <?= Html::encode($zone->cf_account) ?><?= $zone->cf_zone_status ? ' · зона ' . Html::encode($zone->cf_zone_status) : '' ?>
            </div>
        <?php endif; ?>
        <?php foreach (array_slice($messages, -3) as $m): ?>
            <?= $message($m) ?>
        <?php endforeach; ?>
        <?php if (count($messages) > 3): ?>
            <details class="small">
                <summary>Весь журнал (<?= count($messages) ?>)</summary>
                <?php foreach ($messages as $m): ?>
                    <?= $message($m) ?>
                <?php endforeach; ?>
            </details>
        <?php endif; ?>
        <div class="dns-sync-request-error small text-danger"></div>
    </td>
    <td class="text-center">
        <?= Html::checkbox('selected', (bool)$zone->selected, [
            'class' => 'form-check-input dns-sync-select', 'title' => 'Синк',
        ]) ?>
    </td>
    <td class="dns-sync-actions">
        <?= Html::button('Перезапуск', [
            'class' => 'btn btn-outline-danger btn-sm', 'data-action' => 'run',
            'data-confirm' => "Перезалить DNS зоны {$zone->domain} в Cloudflare?",
        ]) ?>
        <?= Html::button('Обновить after', ['class' => 'btn btn-outline-secondary btn-sm', 'data-action' => 'loadAfter']) ?>
        <?php if ($zone->cf_state === DnsSyncZone::CF_MISSING && $item && !$item->parent_id): ?>
            <?php $accounts = DnsRecords::configured()->accountLabels(); ?>
            <?php foreach ($accounts as $account): ?>
                <?= Html::button('Добавить в Cloudflare' . (count($accounts) > 1 ? ': ' . Html::encode($account) : ''), [
                    'class' => 'btn btn-outline-primary btn-sm mt-1', 'data-action' => 'addZone',
                    'data-params' => json_encode(['account' => $account]),
                    'data-confirm' => "Добавить зону {$zone->domain} в Cloudflare ($account)? Записи не импортируются, NS у регистратора не меняются.",
                ]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </td>
</tr>
<?php foreach ($childs as $child): ?>
    <tr class="table-warning">
        <td colspan="7" class="small"><span class="dns-sync-sticky-text">↳ поддомен <?= Html::encode($child->domain) ?> — не обрабатывается, проверьте вручную</span></td>
    </tr>
<?php endforeach; ?>
<?php if ($alias): ?>
    <tr>
        <td colspan="7" class="small text-muted"><span class="dns-sync-sticky-text">↳ алиас <?= Html::encode($alias) ?> — не обрабатывается</span></td>
    </tr>
<?php endif; ?>
</tbody>
