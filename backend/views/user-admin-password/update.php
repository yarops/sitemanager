<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\UserAdminPassword */

$this->title = Yii::t('backend', 'Update User Admin Password:') . ' ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('backend', 'Update');
?>
<div class="user-admin-password-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'action' => ['user-admin-password/update']
    ]) ?>

</div>
