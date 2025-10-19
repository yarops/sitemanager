<?php

/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 09.07.14
 * Time: 9:26
 */

use yii\helpers\Html;
use common\models\Check;

/* @var $model common\models\Item */

$status = 'check disabled';
$class = 'default';

if (!empty($model->lastCheck->check_status)) {
    $status = $model->lastCheck->check_status;
    switch ($status) {
        case '200':
            $class = 'success';
            $statusText = 'UP';
            break;
        case '302':
            $class = 'warn';
            $statusText = 'REDIRECT';
            break;
        default:
            $class = 'danger';
            $statusText = 'DOWN';
            break;
    }
} else {
    $statusText = 'Не проверялся';
}

$has_subdomains = is_array($model->childs) && !empty($model->childs);
?>
<div class="item-card <?php echo ($has_subdomains) ? 'item-card--backgrounded' : ''; ?>">
    <div class="item-card__heading">
        <?php
        echo Html::a(
            Yii::t('frontend', $model->domain),
            [
                'item/view',
                'id' => $model->id,
            ],
            ['class' => 'item-card__title']
        )
        ?>

        <a href="<?php echo $model->protocol . '://' . $model->domain; ?>" target="_blank"
           class="btn btn-success btn-xs">To site</a>
        <a href="<?php echo $model->protocol . '://' . $model->domain . $model->admin_link; ?>" target="_blank"
           class="btn btn-success btn-xs">To admin</a>
        <a href="<?php echo $model->server->link; ?>" target="_blank" class="btn btn-success btn-xs">To ispmanager</a>
    </div>

    <div class="meta">
        <p><?php echo Yii::t('frontend', 'Server'); ?>:
            <?php
            echo Html::a(
                $model->server->title,
                [
                    'server/view',
                    'id' => $model->server->id,
                ]
            )
            ?>
        </p>
        <p class="item-card__template">
            <?php echo Yii::t('frontend', 'Template'); ?>:
            <?= $model->template->title; ?>
        </p>
        <div class="monitoring-info">
            <?php if ($model->lastCheck): ?>
                <p class="check-status check-status--<?php echo $class; ?>">
                    Status: <?php echo $statusText; ?>
                </p>
                <p class="monitoring-details">
                    <small>
                        <strong>Последняя проверка:</strong> <?= Yii::$app->formatter->asDatetime($model->lastCheck->check_date) ?><br>
                        <?php if ($model->lastCheck->response_time): ?>
                            <strong>Время отклика:</strong> <?= $model->lastCheck->response_time ?> мс<br>
                        <?php endif; ?>
                        <?php if ($model->lastCheck->error_message): ?>
                            <strong>Ошибка:</strong> <span class="text-danger"><?= Html::encode($model->lastCheck->error_message) ?></span><br>
                        <?php endif; ?>
                        <?= Html::a('История проверок', ['check/site', 'id' => $model->id], ['class' => 'btn btn-xs btn-info']) ?>
                    </small>
                </p>
            <?php else: ?>
                <p class="check-status check-status--default">
                    Status: <?php echo $statusText; ?>
                </p>
                <p class="monitoring-details">
                    <small>
                        <strong>Мониторинг:</strong> 
                        <?php if ($model->check_enabled): ?>
                            <span class="text-success">Включен</span>
                        <?php else: ?>
                            <span class="text-muted">Отключен</span>
                        <?php endif; ?>
                    </small>
                </p>
            <?php endif; ?>
        </div>
    </div>

</div>
