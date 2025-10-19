<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var backend\models\search\ServerSearch $searchModel
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('backend', 'Servers');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="server-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('backend', 'Create Server'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <div class="box">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel'  => $searchModel,
            'columns'      => [
                ['class' => 'yii\grid\SerialColumn'],
                'id',
                'title',
                'ip',
                ['class' => 'yii\grid\ActionColumn', 'template' => '{view} {update} {delete}'],
            ],
        ]); ?>
    </div>
</div>
