<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model common\models\Template */
/* @var $form yii\widgets\ActiveForm */
/* @var $templates yii\db\ActiveRecord[] */
?>

<div class="template-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'parent_id')->dropDownList(
                ArrayHelper::map($templates, 'id', 'title'),
                [
                    'prompt' => ' -- Select Template --'
                ]
            ) ?>
        </div>
        <div class="col-sm-6">
            <?= $form->field($model, 'title')->textInput(['maxlength' => 255]) ?>
        </div>
    </div>

    <?= $form->field($model, 'link')->textInput(['maxlength' => 512]) ?>

    <?= $form->field($model, 'image_link')->textInput(['maxlength' => 512]) ?>

    <?= $form->field($model, 'content')->textarea(['rows' => 6]) ?>

    <div class="row">
        <div class="col-sm-4">
            <?= $form->field($model, 'publish_date')->textInput() ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(
            $model->isNewRecord ?
                Yii::t('backend', 'Create') :
                Yii::t('backend', 'Update'),
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        );
?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
