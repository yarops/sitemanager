<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Server */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Servers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <table class="table table-striped table-bordered detail-view">
    <tr>
        <td><b>Difference on server</b></td>
    </tr>
    <?php
    foreach ($diff_isp as $domain) {
        echo '<tr><td>' . $domain . '</td></tr>';
    }
    ?>

    <tr>
        <td><b>Difference on admin</b></td>
    </tr>
    <?php
    foreach ($diff_admin as $domain) {
        echo '<tr><td>' . $domain . '</td></tr>';
    }
    ?>
    </table>

</div>
