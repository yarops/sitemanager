<?php

use yii\helpers\Html;

/** @var $this yii\web\View */
/** @var $model common\models\Server */
/** @var $available bool */
/** @var $diffIsp string[] */
/** @var $diffCrm string[] */
/** @var $ispCount int */
/** @var $crmCount int */

$this->title = $model->title . ' — сравнение пользователей';
$this->params['breadcrumbs'][] = ['label' => Yii::t('frontend', 'Servers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p><?= Html::a('Check diff: сайты', ['server/check-diff', 'id' => $model->id], ['class' => 'btn btn-success btn-sm']) ?></p>

    <?php if (!$available): ?>
        <div class="alert alert-danger" role="alert">
            Не удалось получить список пользователей ISPmanager. Сравнение не выполнено. Повторите проверку позже.
        </div>
    <?php else: ?>
        <p>Сравнение по логину для выбранного сервера. Архивные пользователи CRM не учитываются.</p>
        <p>Пользователей в ISP: <?= $ispCount ?>. Активных логинов в CRM: <?= $crmCount ?>.</p>

        <?php foreach ([
            ['Есть в ISP, нет в CRM', $diffIsp],
            ['Есть в CRM, нет в ISP', $diffCrm],
        ] as [$heading, $logins]): ?>
            <h2><?= Html::encode($heading) ?> (<?= count($logins) ?>)</h2>
            <?php if ($logins === []): ?>
                <p>Расхождений нет.</p>
            <?php else: ?>
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Логин</th></tr></thead>
                    <tbody>
                    <?php foreach ($logins as $login): ?>
                        <tr><td><?= Html::encode($login) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
