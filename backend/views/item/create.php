<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Item */
/* @var $authors yii\db\ActiveRecord[] */
/* @var $server yii\db\ActiveRecord[] */
/* @var $template yii\db\ActiveRecord[] */

$this->title = Yii::t('backend', 'Create Item');
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'server' => $server,
        'template' => $template,
        'authors' => $authors
    ]) ?>

</div>
