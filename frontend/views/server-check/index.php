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
/** @var $server \common\models\Server|null */
/** @var $servers \yii\data\ActiveDataProvider */
/** @var $isArchived bool */

$this->title = $server
    ? Yii::t('frontend', $isArchived ? 'Server archive checks: {server}' : 'Server checks: {server}', ['server' => $server->title])
    : Yii::t('frontend', $isArchived ? 'Archived server checks' : 'All server checks');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
<div class="col-md-8 item-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= Html::a('Active', ['server-check/index'], ['class' => 'btn btn-secondary btn-sm']) ?>
        <?= Html::a('Archive', ['server-check/index', 'archived' => 1], ['class' => 'btn btn-secondary btn-sm']) ?>
        <?php if (!$isArchived): ?>
            <?php if ($server): ?>
                <?= Html::a('Архивировать все на сервере', ['server-check/archive-all', 'serverId' => $server->id], [
                    'class' => 'btn btn-warning btn-sm',
                    'data-method' => 'post',
                    'data-confirm' => 'Архивировать все активные проверки этого сервера?',
                ]) ?>
            <?php else: ?>
                <?= Html::a('Архивировать все', ['server-check/archive-all'], [
                    'class' => 'btn btn-warning btn-sm',
                    'data-method' => 'post',
                    'data-confirm' => 'Архивировать все активные проверки?',
                ]) ?>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($server): ?>
            <?= Html::a('Server active', ['server-check/server', 'id' => $server->id], ['class' => 'btn btn-secondary btn-sm']) ?>
            <?= Html::a('Server archive', ['server-check/server', 'id' => $server->id, 'archived' => 1], ['class' => 'btn btn-secondary btn-sm']) ?>
            <?= Html::a('Back to server', ['server/view', 'id' => $server->id], ['class' => 'btn btn-secondary btn-sm']) ?>
        <?php endif; ?>
    </p>

    <table class="table table-striped table-bordered detail-view server-checks-table">
        <tr>
            <th>ID</th>
            <th>Server</th>
            <th>Date</th>
            <th>Bad/Total</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($items->models as $item): ?>
            <?php
            $report = json_decode($item->report, true);
            if (!is_array($report)) {
                $report = [];
            }

            $total = count($report);
            $okCount = 0;
            foreach ($report as $result) {
                $status = is_array($result) ? ($result['status'] ?? 0) : $result;
                $aliasStatus = is_array($result) ? ($result['alias_status'] ?? null) : null;
                if ((int)$status === 200 && ($aliasStatus === null || (int)$aliasStatus === 200)) {
                    $okCount++;
                }
            }
            $badCount = $total - $okCount;
            $rowClass = $badCount > 0 ? 'table-warning' : 'table-success';
            ?>
            <tr class="<?= $rowClass ?>">
                <td><?= (int)$item->id ?></td>
                <td>
                    <?php if ($item->server): ?>
                        <?= Html::a(
                            Html::encode($item->server->title . ' (' . $item->server->ip . ')'),
                            ['server-check/server', 'id' => $item->server->id]
                        ) ?>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?= Html::encode($item->publish_date) ?></td>
                <td><?= $badCount ?>/<?= $total ?></td>
                <td>
                    <?= Html::a('Open report', ['server-check/view', 'id' => $item->id], ['class' => 'btn btn-primary btn-sm']) ?>
                    <?php if ($item->server): ?>
                        <?= Html::a(
                            'Server checks',
                            ['server-check/server', 'id' => $item->server->id, 'archived' => $isArchived ? 1 : 0],
                            ['class' => 'btn btn-secondary btn-sm']
                        ) ?>
                    <?php endif; ?>
                    <?php if (!$isArchived): ?>
                        <?= Html::a('Архивировать', ['server-check/archive', 'id' => $item->id], [
                            'class' => 'btn btn-warning btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Архивировать эту проверку?',
                        ]) ?>
                    <?php else: ?>
                        <?= Html::a('Восстановить', ['server-check/restore', 'id' => $item->id], [
                            'class' => 'btn btn-success btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Восстановить эту проверку из архива?',
                        ]) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <div>
        <?= LinkPager::widget([
            'pagination' => $items->getPagination()
        ]) ?>
    </div>

</div>

<div class="col-md-4 blog-sidebar">
    <h1><?= Yii::t('frontend', 'Servers') ?></h1>
    <ul>
        <?php foreach ($servers->models as $serverModel): ?>
            <?= $this->render('shortViewServer', [
                'model' => $serverModel,
            ]) ?>
        <?php endforeach; ?>
    </ul>
</div>
</div>

<style>
    .server-checks-table td,
    .server-checks-table th {
        vertical-align: top;
    }

    .server-checks-table td:nth-child(2) a {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .server-checks-table td:last-child {
        white-space: nowrap;
    }
</style>
