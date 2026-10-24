<?php

use common\models\DnsSyncZone;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\YiiAsset;

/** @var $this yii\web\View */
/** @var $model \common\models\DnsSync */
/** @var $zones DnsSyncZone[] */

$this->title = 'DNS синк: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'DNS синк', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->title;

$this->registerJsFile('@web/js/dns-sync.js', ['depends' => [YiiAsset::class]]);
$urls = [];
foreach (['load-isp' => 'loadIsp', 'load-before' => 'loadBefore', 'toggle' => 'toggle', 'run' => 'run', 'load-after' => 'loadAfter', 'add-zone' => 'addZone'] as $route => $key) {
    $urls[$key] = Url::to([$route, 'id' => $model->id]);
}
?>

<div id="dns-sync" data-urls="<?= Html::encode(json_encode($urls)) ?>">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="mb-2 text-muted">
        Сайты: <?= $model->sourceServer ? Html::encode($model->sourceServer->title . ' (' . $model->sourceServer->ip . ')') : 'N/A' ?>
        · ISPmanager: <?= $model->ispServer ? Html::encode($model->ispServer->title . ' (' . $model->ispServer->ip . ')') : 'N/A' ?>
        · <?= $model->author ? Html::encode($model->author->username) : '' ?> <?= Html::encode($model->created_at) ?>
    </div>
    <div class="mb-2">
        Зон: <span data-counter="total"><?= count($zones) ?></span>
        · отмечено: <span data-counter="selected">0</span>
        · done: <span data-counter="done">0</span>
        · error: <span data-counter="error">0</span>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
        <button type="button" class="btn btn-secondary btn-sm" data-queue="loadIsp">Загрузить ISP</button>
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
        before сохраняется один раз до первого изменения.
    </p>

    <div class="table-responsive">
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
    .dns-sync-table td { font-size: 0.875rem; }
    .dns-sync-table .dns-sync-record { white-space: nowrap; font-family: var(--bs-font-monospace); font-size: 0.8rem; }
    .dns-sync-table .dns-sync-status { min-width: 260px; }
    .dns-sync-table .dns-sync-actions { white-space: nowrap; }
    .dns-sync-table tr.dns-sync-dimmed > td { opacity: 0.6; }
    .dns-sync-table tbody[data-busy] { opacity: 0.5; }
</style>
