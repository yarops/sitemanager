<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Item */

$this->title = $model->domain;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
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
            'parent_id',
            [
                'label' => 'Link',
                'format' => 'raw',
                'value'=>function ($model) {
                    $url = $model->protocol  . '://' . $model->domain;
                    return Html::a($url, $url, ['target' => '_blank']);
                },
            ],
            [
                'label' => Yii::t('backend', 'Admin link'),
                'format' => 'raw',
                'value' => function ($model) {
                    $url = $model->protocol  . '://' . $model->domain . $model->admin_link;
                    return Html::a($url, $url, ['target' => '_blank']);
                }
            ],
            [
                'label' => Yii::t('backend', 'Server'),
                'value' => $model->server->title
            ],
            [
                'label' => Yii::t('backend', 'Server user'),
                'value' => (!empty($model->serverUser->title)) ? $model->serverUser->title : ''
            ],
            [
                'label' => Yii::t('backend', 'Template'),
                'value' => $model->template->title
            ],
            'content:ntext',
            'publish_status',
            'publish_date',
        ],
    ]) ?>

</div>
