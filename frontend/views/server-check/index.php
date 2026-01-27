<?php

/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:14
 */

use yii\helpers\Html;
use yii\bootstrap5\LinkPager;

/** @var $this yii\web\View */
/** @var $items \yii\data\ActiveDataProvider список категорий */

$this->title = Yii::t('frontend', 'Templates');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-sm-8 item-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="templates-grid">
        <?php
        foreach ($items->models as $item) {
            echo $this->render('shortViewTemplate', [
                'model' => $item
            ]);
        }
        ?>
    </div>
    
    <div>
        <?= LinkPager::widget([
            'pagination' => $items->getPagination()
        ]) ?>
    </div>

</div>
