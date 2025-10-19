<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Server */
/* @var $serverUser common\models\ServerUser */

$this->title = Yii::t('backend', 'Update Server:') . ' ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Servers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('backend', 'Update');
?>
<div class="server-update">

  <h1><?= Html::encode($this->title) ?></h1>

  <?= Html::a(Yii::t('backend', 'Delete'), ['delete', 'id' => $model->id], [
    'class' => 'btn btn-danger',
    'data' => [
      'confirm' => Yii::t('backend', 'Are you sure you want to delete this item?'),
      'method' => 'post',
    ],
  ]) ?>

  <?= $this->render('_form', [
    'model' => $model,
    'serverUser' => $serverUser,
  ]) ?>

</div>