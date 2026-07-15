<?php

use yii\bootstrap5\LinkPager;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $items yii\data\ActiveDataProvider */
/* @var $servers yii\data\ActiveDataProvider */
/* @var $item common\models\Item */
/* @var $current_status_filter string|null */
/* @var $current_publication_filter string|null */
/* @var $current_http_filter bool */

$this->title = Yii::t('frontend', 'Items');

$filterUrl = static function (array $overrides = []) use (
    $current_status_filter,
    $current_publication_filter,
    $current_http_filter
): array {
    $params = ['item/index'];
    $filters = [
        'status' => $current_status_filter,
        'publication' => $current_publication_filter,
        'http' => $current_http_filter ? 'on' : null,
    ];

    foreach (array_merge($filters, $overrides) as $name => $value) {
        if ($value !== null) {
            $params[$name] = $value;
        }
    }

    return $params;
};
?>

<div class="row">
<div class="col-sm-9 item-index">

    <h1 class="page-title"><?= Html::encode($this->title) ?></h1>
    
    <div class="filters-and-sort">
        <div class="status-filters">
            <span class="filter-label">Публикация:</span>
            <?= Html::a(
                'Все' . (!$current_publication_filter ? ' ✓' : ''),
                $filterUrl(['publication' => null]),
                ['class' => 'filter-btn' . (!$current_publication_filter ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Опубликован' . ($current_publication_filter === \common\models\Item::STATUS_PUBLISH ? ' ✓' : ''),
                $filterUrl(['publication' => \common\models\Item::STATUS_PUBLISH]),
                ['class' => 'filter-btn' . ($current_publication_filter === \common\models\Item::STATUS_PUBLISH ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Черновик' . ($current_publication_filter === \common\models\Item::STATUS_DRAFT ? ' ✓' : ''),
                $filterUrl(['publication' => \common\models\Item::STATUS_DRAFT]),
                ['class' => 'filter-btn' . ($current_publication_filter === \common\models\Item::STATUS_DRAFT ? ' active' : '')]
            ) ?>
        </div>

        <div class="status-filters">
            <span class="filter-label">Мониторинг:</span>
            <?= Html::a(
                'Все' . (!$current_status_filter ? ' ✓' : ''),
                $filterUrl(['status' => null]),
                ['class' => 'filter-btn' . (!$current_status_filter ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Включен' . ($current_status_filter === 'enabled' ? ' ✓' : ''),
                $filterUrl(['status' => 'enabled']),
                ['class' => 'filter-btn' . ($current_status_filter === 'enabled' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Отключен' . ($current_status_filter === 'disabled' ? ' ✓' : ''),
                $filterUrl(['status' => 'disabled']),
                ['class' => 'filter-btn' . ($current_status_filter === 'disabled' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Работает' . ($current_status_filter === 'up' ? ' ✓' : ''),
                $filterUrl(['status' => 'up']),
                ['class' => 'filter-btn' . ($current_status_filter === 'up' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Не работает' . ($current_status_filter === 'down' ? ' ✓' : ''),
                $filterUrl(['status' => 'down']),
                ['class' => 'filter-btn' . ($current_status_filter === 'down' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Не проверялся' . ($current_status_filter === 'never_checked' ? ' ✓' : ''),
                $filterUrl(['status' => 'never_checked']),
                ['class' => 'filter-btn' . ($current_status_filter === 'never_checked' ? ' active' : '')]
            ) ?>
        </div>

        <div class="status-filters">
            <span class="filter-label">Протокол:</span>
            <?= Html::a(
                'Все' . (!$current_http_filter ? ' ✓' : ''),
                $filterUrl(['http' => null]),
                ['class' => 'filter-btn' . (!$current_http_filter ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Только HTTP' . ($current_http_filter ? ' ✓' : ''),
                $filterUrl(['http' => 'on']),
                ['class' => 'filter-btn' . ($current_http_filter ? ' active' : '')]
            ) ?>
            <?= Html::a('Сбросить', ['item/index'], ['class' => 'filter-btn']) ?>
        </div>
    </div>
    <?php
    foreach ($items->models as $item) {
        echo $this->render('shortView', [
            'model' => $item
        ]);
    }
    ?>
    <div>
        <?=
        LinkPager::widget([
            'pagination' => $items->getPagination()
        ]);
        ?>
    </div>
</div>

<div class="col-sm-3 blog-sidebar">
    <h1 class="side-title"><?= Yii::t('frontend', 'Servers') ?></h1>
    <ul class="list-group">
    <?php
    foreach ($servers->models as $server) {
        echo $this->render('//server/shortViewServer', [
            'model' => $server
        ]);
    }
    ?>
    </ul>
</div>
</div>
