<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Item */
/* @var $authors yii\db\ActiveRecord[] */
/* @var $server yii\db\ActiveRecord[] */
/* @var $serverUsers yii\db\ActiveRecord[] */
/* @var $template yii\db\ActiveRecord[] */

$this->title = Yii::t('backend', 'Update Item:') . ' ' . $model->domain;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->domain, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('backend', 'Update');
?>
<div class="item-update">

    <h1><?= Html::encode($this->title) ?> - <?= Html::encode($model->id) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'server' => $server,
        'serverUsers' => $serverUsers,
        'template' => $template,
        'authors' => $authors
    ]) ?>

</div>
