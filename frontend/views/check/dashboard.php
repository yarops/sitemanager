<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $items common\models\Item[] */

$this->title = 'Мониторинг сайтов';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="check-dashboard">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box__heading">
                    <h2 class="box__title">Статус мониторинга</h2>
                    <div>
                        <?= Html::a('Обновить', ['check/dashboard'], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('Все проверки', ['check/index'], ['class' => 'btn btn-default']) ?>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Сайт</th>
                                <th>Статус</th>
                                <th>Время отклика</th>
                                <th>Последняя проверка</th>
                                <th>Ошибка</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $lastCheck = $item->lastCheck;
                                $statusClass = 'default';
                                $statusText = 'Не проверялся';
                                $responseTime = '-';
                                $lastCheckDate = '-';
                                $errorMessage = '-';

                                if ($lastCheck) {
                                    $statusText = $lastCheck->check_status;
                                    $responseTime = $lastCheck->response_time ? $lastCheck->response_time . ' мс' : '-';
                                    $lastCheckDate = Yii::$app->formatter->asDatetime($lastCheck->check_date);
                                    $errorMessage = $lastCheck->error_message ?: '-';

                                    switch ($lastCheck->check_status) {
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
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= Html::encode($item->domain) ?></strong><br>
                                        <small class="text-muted"><?= $item->protocol ?>://<?= $item->domain ?></small>
                                    </td>
                                    <td>
                                        <span class="check-status check-status--<?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td><?= $responseTime ?></td>
                                    <td><?= $lastCheckDate ?></td>
                                    <td>
                                        <?php if ($errorMessage !== '-'): ?>
                                            <span class="text-danger"><?= Html::encode($errorMessage) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= Html::a('История', ['check/site', 'id' => $item->id], ['class' => 'btn btn-xs btn-info']) ?>
                                        <?= Html::a('Сайт', $item->protocol . '://' . $item->domain, ['class' => 'btn btn-xs btn-success', 'target' => '_blank']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

.table th {
    background-color: #f5f5f5;
    font-weight: bold;
}

.text-muted {
    color: #777;
}

.text-danger {
    color: #d9534f;
}
</style>
