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

    <table class="table table-bordered detail-view">
    <tr>
        <td>Host</td>
        <td>Result</td>
    </tr>
    <?php
    foreach ($checked as $key => $value) {
        if ($value === '0') {
            $class = 'error';
        } else {
            $class = '';
        }
        echo '<tr class="'.$class.'">';
        echo '<td>' . $key . '</td>';
        echo '<td>' . $value . '</td>';
        echo '</td>';
    }
    ?>
    </table>

    <style>
        table {
            background: white;
        }
        .error {
            background: #ffd3d3;
        }
    </style>

</div>
