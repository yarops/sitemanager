<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'История проверок';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="check-index">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box__heading">
                    <h2 class="box__title">Все проверки</h2>
                    <div>
                        <?= Html::a('Дашборд', ['check/dashboard'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
                
                <?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                [
            'attribute' => 'item.domain',
            'label' => 'Сайт',
            'format' => 'raw',
            'value' => function ($model) {
                    if ($model->item) {
                        return Html::a(
                            $model->item->domain,
                            ['check/site', 'id' => $model->item_id],
                            ['class' => 'btn-link']
                        );
                    }
                                return 'Неизвестный сайт (ID: ' . $model->item_id . ')';
                    }
        ],

                [
            'attribute' => 'check_status',
            'label' => 'Статус',
            'format' => 'raw',
            'value' => function ($model) {
                                $statusClass = 'default';
                                $statusText = $model->check_status;

                    switch ($model->check_status) {
                        case '200':
                            $statusClass = 'success';
                            $statusText = 'UP';
                            break;
                        case '302':
                            $statusClass = 'warning';
                            $statusText = 'REDIRECT';
                            break;
                        default:
                            $statusClass = 'danger';
                            $statusText = 'DOWN';
                            break;
                    }

                                return '<span class="check-status check-status--' . $statusClass . '">' . $statusText . '</span>';
                    }
        ],

                [
            'attribute' => 'response_time',
            'label' => 'Время отклика',
            'value' => function ($model) {
                                return $model->response_time ? $model->response_time . ' мс' : '-';
                    }
        ],

                [
            'attribute' => 'check_date',
            'label' => 'Дата проверки',
            'format' => 'datetime'
        ],

                [
            'attribute' => 'error_message',
            'label' => 'Ошибка',
            'format' => 'raw',
            'value' => function ($model) {
                    if ($model->error_message) {
                        return '<span class="text-danger">' . Html::encode($model->error_message) . '</span>';
                    }
                                return '<span class="text-muted">-</span>';
                    }
        ],

                [
            'attribute' => 'job_id',
            'label' => 'Job ID',
            'value' => function ($model) {
                                return $model->job_id ?: '-';
                    }
        ],
                ],
]); ?>
            </div>
        </div>
    </div>
</div>

<style>
.check-status {
    color: #fff;
    position: relative;
    background: darkgray;
    display: inline-block;
    border-radius: 5px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: bold;
}

.check-status--success {
    background: #5cb85c;
}

.check-status--danger {
    background: #d9534f;
}

.check-status--warning {
    background: #f0ad4e;
}

.check-status--default {
    background: #777;
}

.text-muted {
    color: #777;
}

.text-danger {
    color: #d9534f;
}

.btn-link {
    color: #337ab7;
    text-decoration: none;
}

.btn-link:hover {
    color: #23527c;
    text-decoration: underline;
}
</style>
