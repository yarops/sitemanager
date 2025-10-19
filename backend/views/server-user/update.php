<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\ServerUser */

$this->title = Yii::t('backend', 'Update Server user:') . ' ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('backend', 'Update');
?>
<div class="server-user-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'action' => ['server-user/update/' . $model->id]
    ]) ?>

</div>
