<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $item common\models\Item */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'История проверок: ' . $item->domain;
$this->params['breadcrumbs'][] = ['label' => 'Мониторинг', 'url' => ['check/dashboard']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="check-site">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box__heading">
                    <h2 class="box__title">Информация о сайте</h2>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>Домен:</strong></td>
                                <td><?= Html::encode($item->domain) ?></td>
                            </tr>
                            <tr>
                                <td><strong>URL:</strong></td>
                                <td>
                                    <?= Html::a(
                                        $item->protocol . '://' . $item->domain,
                                        $item->protocol . '://' . $item->domain,
                                        ['target' => '_blank', 'class' => 'btn-link']
                                    ) ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Сервер:</strong></td>
                                <td><?= $item->server ? $item->server->title : '-' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Мониторинг:</strong></td>
                                <td>
                                    <?php if ($item->check_enabled): ?>
                                        <span class="check-status check-status--success">Включен</span>
                                    <?php else: ?>
                                        <span class="check-status check-status--default">Отключен</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="text-right">
                            <?= Html::a('Назад к дашборду', ['check/dashboard'], ['class' => 'btn btn-default']) ?>
                            <?= Html::a('Все проверки', ['check/index'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box__heading">
                    <h2 class="box__title">История проверок</h2>
                </div>
                
                <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
        ['class' => 'yii\grid\SerialColumn'],

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

.table th {
    background-color: #f5f5f5;
    font-weight: bold;
}
</style>
