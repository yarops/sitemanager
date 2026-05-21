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
        <?php if ($model->isArchived()): ?>
            <?= Html::a(Yii::t('backend', 'Restore'), ['restore', 'id' => $model->id], [
                'class' => 'btn btn-success',
                'data' => [
                    'confirm' => Yii::t('backend', 'Restore this item from archive?'),
                    'method' => 'post',
                ],
            ]) ?>
        <?php else: ?>
            <?= Html::a(Yii::t('backend', 'Archive'), ['archive', 'id' => $model->id], [
                'class' => 'btn btn-warning',
                'data' => [
                    'confirm' => Yii::t('backend', 'Archive this item and disable monitoring?'),
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
        <?= Html::a(Yii::t('backend', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('backend', 'Hard delete this item? Use archive for historical records.'),
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
            [
                'attribute' => 'is_archived',
                'value' => $model->isArchived() ? 'Yes' : 'No',
            ],
            'archived_at',
            'archived_by',
            'publish_date',
        ],
    ]) ?>

</div>
