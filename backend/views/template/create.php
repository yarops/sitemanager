<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Template */
/* @var $templates yii\db\ActiveRecord[] */

$this->title = Yii::t('backend', 'Create Template');
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Templates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="template-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'templates' => $templates
    ]) ?>

</div>
