<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Server */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Servers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['fluidContainer'] = true;
?>
<div class="item-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="table-responsive">
    <table class="table table-bordered align-top">
        <thead><tr><th scope="col">Host</th><th scope="col">Result</th></tr></thead>
        <tbody>
        <?php foreach (is_array($checked) ? $checked : [] as $key => $value): ?>
            <tr class="<?= (int)$value === 0 ? 'table-danger' : ((int)$value === 200 ? 'table-success' : 'table-warning') ?>">
                <td><?= Html::encode($key) ?></td>
                <td><?= Html::encode($value) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($checked)): ?>
            <tr><td colspan="2">Нет опубликованных сайтов для проверки.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

</div>
