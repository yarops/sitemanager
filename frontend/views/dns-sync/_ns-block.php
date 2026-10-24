<?php

use yii\helpers\Html;

/** @var $this yii\web\View */
/** @var $title string */
/** @var $servers string[]|null */
/** @var $empty string shown when there are no servers */
/** @var $class string text colour class */
?>
<div class="small mt-1 <?= Html::encode($class) ?>">
    <?= Html::encode($title) ?><?= $servers ? '' : ': ' . Html::encode($empty) ?>
    <?php if ($servers): ?>
        <?= Html::button('<i class="bi bi-clipboard"></i>', [
            'class' => 'btn btn-link btn-sm p-0 ms-1 align-baseline dns-sync-copy',
            'data-copy' => implode("\n", $servers), 'title' => 'Копировать ' . $title,
        ]) ?>
        <?php foreach ($servers as $server): ?>
            <div><?= Html::encode($server) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
