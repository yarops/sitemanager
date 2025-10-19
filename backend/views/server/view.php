<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Server */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Servers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('backend', 'Check sites'), ['check', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('backend', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('backend', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('backend', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'label' => 'Title',
                'value'=>$model->title,
            ],
            [
                'label' => 'IP',
                'value'=>$model->ip,
            ],
            [
                'label' => 'Link',
                'format' => 'raw',
                'value'=>function ($model) {
                    $url = $model->link;
                    return Html::a($url, $url, ['target' => '_blank']);
                },
            ],
            [
                'label' => 'Creditnails',
                'value'=>$model->credentials,
            ],
            [
                'label' => 'Username',
                'value'=>'root',
            ],
            'password',
            'content:ntext',
            'publish_status',
            'publish_date',
        ],
    ]) ?>

</div>
