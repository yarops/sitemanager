<?php

use yii\bootstrap5\LinkPager;
use yii\helpers\Html;

/** @var $this yii\web\View */
/** @var $items \yii\data\ActiveDataProvider */

$this->title = 'DNS синк';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>
<p class="text-muted">Перезаливка DNS-записей зон из ISPmanager в Cloudflare: все записи зоны в Cloudflare удаляются, создаются записи ISP.</p>
<div class="mb-3">
    <?= Html::a('Новый синк', ['create'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>

<div class="table-responsive">
<table class="table table-striped table-bordered align-top">
    <thead>
    <tr>
        <th>ID</th>
        <th>Синк</th>
        <th>Сервер сайтов</th>
        <th>ISPmanager</th>
        <th>Автор</th>
        <th>Создан</th>
    </tr>
    </thead>
    <tbody>
    <?php /** @var \common\models\DnsSync $sync */ ?>
    <?php foreach ($items->models as $sync): ?>
        <tr>
            <td><?= (int)$sync->id ?></td>
            <td><?= Html::a(Html::encode($sync->title), ['view', 'id' => $sync->id]) ?></td>
            <td><?= $sync->sourceServer ? Html::encode($sync->sourceServer->title . ' (' . $sync->sourceServer->ip . ')') : 'N/A' ?></td>
            <td><?= $sync->ispServer ? Html::encode($sync->ispServer->title . ' (' . $sync->ispServer->ip . ')') : 'N/A' ?></td>
            <td><?= $sync->author ? Html::encode($sync->author->username) : '' ?></td>
            <td><?= Html::encode($sync->created_at) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?= LinkPager::widget(['pagination' => $items->getPagination()]) ?>
