<?php

use yii\helpers\Html;
use yii\grid\GridView;
use kartik\export\ExportMenu;
use kartik\daterange\DateRangePicker;
use yii\helpers\Url;

/**
 * Item index view.
 *
 * @var yii\web\View $this
 * @var backend\models\search\ItemSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */

$gridColumns = [
    ['class' => 'yii\grid\SerialColumn'],
    'id',
    'title',
    'content',
    [
        'attribute' => 'server_id',
        'label'     => 'Server',
        'vAlign'    => 'middle',
        'width'     => '190px',
        'value'     => function ($model, $key, $index, $widget) {
            if (!$model->server) {
                return 'None';
            }

            return Html::a($model->server->title, '#', []);
        },
        'format'    => 'raw',
    ],
    [
        'attribute' => 'server_user_id',
        'label'     => 'Server user',
        'vAlign'    => 'middle',
        'width'     => '190px',
        'value'     => function ($model, $key, $index, $widget) {
            return Html::a($model->server_user->title, '#', []);
        },
        'format'    => 'raw',
    ],
    [
        'attribute' => 'template_id',
        'label'     => 'Template',
        'vAlign'    => 'middle',
        'width'     => '190px',
        'value'     => function ($model, $key, $index, $widget) {
            return Html::a($model->template->title, '#', []);
        },
        'format'    => 'raw',
    ],
    [
        'attribute' => 'author_id',
        'label'     => 'Author',
        'vAlign'    => 'middle',
        'width'     => '190px',
        'value'     => function ($model, $key, $index, $widget) {
            return Html::a($model->author->title, '#', []);
        },
        'format'    => 'raw',
    ],
    'publish_date',
    ['class' => 'yii\grid\ActionColumn'],
];

$this->title = Yii::t('backend', 'Items');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div style="display: flex;flex-flow:row nowrap;align-items: center;justify-content: space-between;margin: 0 0 40px;">
        <?= Html::a(Yii::t('backend', 'Create Item'), ['create'], ['class' => 'btn btn-success']) ?>

        <?= ExportMenu::widget([
            'dataProvider' => $dataProvider,
            'columns'      => $gridColumns,
            'clearBuffers' => true, //optional
        ]); ?>
    </div>

    <div class="box">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'columns'      => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'label' => 'Link',
                'attribute' => 'domain',
                'format' => 'raw',
                'value' => function ($model) {
                    $url = $model->protocol  . '://' . $model->domain;
                    return Html::a($url, $url, ['target' => '_blank']);
                },
            ],
            [
                'label' => Yii::t('backend', 'Server'),
                'attribute' => 'server.title',
                'format' => 'raw',
                'value' => function ($model) {
                    if (!$model->server) {
                        return 'None';
                    }
                    return Html::a($model->server->title, ['server/update', 'id' => $model->server->id]);
                },
            ],
            [
                'label' => Yii::t('backend', 'Server user'),
                'attribute' => 'serverUser.title',
                'value' => 'serverUser.title',
            ],
            [
                'label' => Yii::t('backend', 'Template'),
                'value' => 'template.title',
            ],
            [
                'label' => Yii::t('backend', 'Author'),
                'value' => 'author.title',
            ],
            'publish_status',
            [
                'attribute' => 'publish_date',
                'format' => 'date',
                'filter'    => DateRangePicker::widget([
                    'model'         => $searchModel,
                    'attribute'     => 'publish_date',
                    'presetDropdown' => true,
                    'convertFormat' => true,
                    'includeMonthsFilter' => true,
                    'pluginOptions' => ['locale' => ['format' => 'Y-m-d']],
                    'options' => ['placeholder' => 'Select Date']
                ]),
            ],
            [
              'class' => 'yii\grid\ActionColumn',
              'buttons' => [
                'view' => function ($url) {
                    return Html::a(
                        '<i class="bi bi-eye"></i>',
                        $url,
                        [
                            'class' => 'btn btn-outline-secondary btn-sm',
                            'target' => '_blank',
                            'title' => 'View'
                        ]
                    );
                },
                'update' => function ($url) {
                    return Html::a(
                        '<i class="bi bi-pencil"></i>',
                        $url,
                        [
                            'class' => 'btn btn-outline-primary btn-sm',
                            'target' => '_blank',
                            'title' => 'Edit'
                        ]
                    );
                },
                'delete' => function ($url) {
                    return Html::a(
                        '<i class="bi bi-trash"></i>',
                        $url,
                        [
                            'class' => 'btn btn-outline-danger btn-sm',
                            'data-method' => 'post',
                            'title' => 'Delete',
                            'data-confirm' => 'Are you sure you want to delete this item?'
                        ]
                    );
                },
              ],
            ],
        ],
    ]) ?>
    </div>
</div>
