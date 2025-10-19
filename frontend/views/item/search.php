<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $items yii\data\ActiveDataProvider */
/* @var $servers yii\data\ActiveDataProvider */
/* @var $item common\models\Item */
/* @var $search_query string */

$this->title = Yii::t('frontend', 'Search by: ' . $search_query);
?>

<div class="col-sm-8 item-index">

    <h1><?= Html::encode($this->title) ?></h1>

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
