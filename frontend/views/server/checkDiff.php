<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Server */
/* @var $diff_isp string[] */
/* @var $diff_admin string[] */
/* @var $itemsByDomain common\models\Item[] */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Servers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p><?= Html::a('Check users diff', ['server/check-users-diff', 'id' => $model->id], ['class' => 'btn btn-success btn-sm']) ?></p>

    <table class="table table-striped table-bordered detail-view">
    <tr>
        <td><b>Difference on server</b></td>
    </tr>
    <?php
    foreach ($diff_isp as $domain) {
        echo '<tr><td>' . (isset($itemsByDomain[$domain])
            ? $this->render('//item/shortView', ['model' => $itemsByDomain[$domain]])
            : Html::encode($domain)) . '</td></tr>';
    }
    ?>

    <tr>
        <td><b>Difference on admin</b></td>
    </tr>
    <?php
    foreach ($diff_admin as $domain) {
        echo '<tr><td>' . (isset($itemsByDomain[$domain])
            ? $this->render('//item/shortView', ['model' => $itemsByDomain[$domain]])
            : Html::encode($domain)) . '</td></tr>';
    }
    ?>
    </table>

</div>
