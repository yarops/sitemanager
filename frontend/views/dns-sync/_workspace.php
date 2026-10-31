<?php

use common\models\DnsSyncZone;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\YiiAsset;

/** @var $this yii\web\View */
/** @var $server \common\models\Server */
/** @var $zones DnsSyncZone[] */

$this->registerJsFile('@web/js/dns-sync.js', ['depends' => [YiiAsset::class]]);
$urls = [];
foreach (['load-isp' => 'loadIsp', 'load-ns' => 'loadNs', 'load-before' => 'loadBefore', 'toggle' => 'toggle', 'run' => 'run', 'load-after' => 'loadAfter', 'add-zone' => 'addZone'] as $route => $key) {
    $urls[$key] = Url::to([$route, 'server' => $server->id]);
}
?>

<div id="dns-sync" data-urls="<?= Html::encode(json_encode($urls)) ?>">
    <div class="mb-2">
        Зон: <span data-counter="total"><?= count($zones) ?></span>
        · отмечено: <span data-counter="selected">0</span>
        · done: <span data-counter="done">0</span>
        · error: <span data-counter="error">0</span>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
        <button type="button" class="btn btn-secondary btn-sm" data-queue="loadIsp">Загрузить ISP</button>
        <button type="button" class="btn btn-secondary btn-sm" data-queue="loadNs">Загрузить NS</button>
        <button type="button" class="btn btn-secondary btn-sm" data-queue="loadBefore">Загрузить Cloudflare</button>
        <button type="button" class="btn btn-danger btn-sm" data-queue="run" data-selected-only
                data-confirm="Перезалить DNS отмеченных зон в Cloudflare? Все записи этих зон в Cloudflare будут удалены и созданы заново из ISP.">Синхронизировать отмеченные</button>
        <button type="button" class="btn btn-outline-danger btn-sm d-none" data-stop>Стоп</button>
        <div class="progress flex-grow-1 d-none" style="max-width: 400px" data-progress>
            <div class="progress-bar" role="progressbar" style="width: 0"></div>
        </div>
        <span class="small text-muted" data-progress-text></span>
    </div>
    <p class="small text-muted">
        Синк удаляет в Cloudflare все записи зоны и создаёт записи ISP (<?= Html::encode(implode(', ', Yii::$app->params['dnsSync']['types'])) ?>).
        before обновляется при загрузке Cloudflare и перед каждым синком.
    </p>

    <div class="dns-sync-scroll">
    <table class="table table-bordered align-top dns-sync-table">
        <thead>
        <tr>
            <th>Зона</th>
            <th>ISP (ожидаемо)</th>
            <th>Cloudflare before</th>
            <th>Cloudflare after</th>
            <th>Статус / инфо</th>
            <th>Синк</th>
            <th>Действия</th>
        </tr>
        </thead>
        <?php foreach ($zones as $zone): ?>
            <?= $this->render('_zone-row', ['zone' => $zone]) ?>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<style>
    /* Only the table scrolls: header row and zone column stay in place. */
    .dns-sync-scroll {
        --dns-sync-bg: #e1f2ff; /* .wrap background: sticky cells must be opaque */
        max-height: calc(100vh - 90px);
        overflow: auto;
        border: 1px solid var(--bs-border-color);
    }
    .dns-sync-table {
        --bs-table-bg: var(--dns-sync-bg);
        margin-bottom: 0;
        /* Collapsed borders scroll away from sticky cells. */
        border-collapse: separate;
        border-spacing: 0;
    }
    .dns-sync-table > :not(caption) > * { border-width: 0; }
    .dns-sync-table > :not(caption) > * > * { border-width: 0 1px 1px 0; }
    .dns-sync-table > :not(caption) > * > :last-child { border-right-width: 0; }
    .dns-sync-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        white-space: nowrap;
    }
    .dns-sync-table tr > :first-child:not([colspan]) {
        position: sticky;
        left: 0;
        z-index: 1;
        min-width: 220px;
        max-width: 300px;
        box-shadow: inset -2px 0 0 var(--bs-border-color), inset 0 0 0 9999px var(--bs-table-accent-bg);
    }
    .dns-sync-table > thead > tr > :first-child:not([colspan]) { z-index: 3; }
    .dns-sync-table .dns-sync-sticky-text { position: sticky; left: 0.5rem; }
    .dns-sync-table td { font-size: 0.875rem; }
    .dns-sync-table .dns-sync-records { max-width: 360px; }
    .dns-sync-table .dns-sync-record { overflow-wrap: break-word; font-family: var(--bs-font-monospace); font-size: 0.8rem; }
    .dns-sync-table .dns-sync-status { min-width: 260px; }
    .dns-sync-table .dns-sync-actions { white-space: nowrap; }
    /* Fade content, not cells: a translucent sticky cell would show rows scrolling under it. */
    .dns-sync-table tr.dns-sync-dimmed > td > *,
    .dns-sync-table tbody[data-busy] td > * { opacity: 0.55; }
</style>
