<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Template */
/* @var $templates yii\db\ActiveRecord[] */

$this->title = Yii::t('backend', 'Update Template:') . ' ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Templates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('backend', 'Update');
?>
<div class="template-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'templates' => $templates
    ]) ?>

</div>
