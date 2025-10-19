<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $items yii\data\ActiveDataProvider */
/* @var $servers yii\data\ActiveDataProvider */
/* @var $item common\models\Item */

$this->title = Yii::t('frontend', 'Items');
?>

<div class="row">
<div class="col-sm-9 item-index">

    <h1 class="page-title"><?= Html::encode($this->title) ?></h1>
    
    <div class="filters-and-sort">
        <form action="" method="$_GET" class="filter-form">
            <label>
                <input type="checkbox" id="http" name="http" <?php echo (Yii::$app->request->get('http') == 'on') ? 'checked' : ''; ?>>
                Only http
            </label>
            <button>Filter</button>
            <?= Html::a('Clear', ['item/index']); ?>
        </form>
        
        <div class="status-filters">
            <span class="filter-label">Фильтр по статусу мониторинга:</span>
            <?= Html::a(
                'Все' . (!$current_status_filter ? ' ✓' : ''),
                ['item/index', 'http' => Yii::$app->request->get('http')],
                ['class' => 'filter-btn' . (!$current_status_filter ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Включен' . ($current_status_filter === 'enabled' ? ' ✓' : ''),
                ['item/index', 'status' => 'enabled', 'http' => Yii::$app->request->get('http')],
                ['class' => 'filter-btn' . ($current_status_filter === 'enabled' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Отключен' . ($current_status_filter === 'disabled' ? ' ✓' : ''),
                ['item/index', 'status' => 'disabled', 'http' => Yii::$app->request->get('http')],
                ['class' => 'filter-btn' . ($current_status_filter === 'disabled' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Работает' . ($current_status_filter === 'up' ? ' ✓' : ''),
                ['item/index', 'status' => 'up', 'http' => Yii::$app->request->get('http')],
                ['class' => 'filter-btn' . ($current_status_filter === 'up' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Не работает' . ($current_status_filter === 'down' ? ' ✓' : ''),
                ['item/index', 'status' => 'down', 'http' => Yii::$app->request->get('http')],
                ['class' => 'filter-btn' . ($current_status_filter === 'down' ? ' active' : '')]
            ) ?>
            <?= Html::a(
                'Не проверялся' . ($current_status_filter === 'never_checked' ? ' ✓' : ''),
                ['item/index', 'status' => 'never_checked', 'http' => Yii::$app->request->get('http')],
                ['class' => 'filter-btn' . ($current_status_filter === 'never_checked' ? ' active' : '')]
            ) ?>
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
        <?= LinkPager::widget([
        'pagination' => $items->getPagination()
]) ?>
    </div>
</div>

<div class="col-sm-3 blog-sidebar">
    <h1><?= Yii::t('frontend', 'Servers') ?></h1>
    <ul>
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