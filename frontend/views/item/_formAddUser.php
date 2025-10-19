<?php
/**
 * Created by Ed.Creater <ed.creater@gmail.com>.
 * Author Site: http://codesweet.ru
 * Date: 14.03.2017
 */

use yii\helpers\Html;
use \yii\widgets\ActiveForm;
use \yii\helpers\ArrayHelper;

/* @var $model common\models\UserItemRelation */
/* @var $users common\models\User */
/* @var $item_id int */
?>

<div>
    <?php $form = ActiveForm::begin(['action' => '/user-item-relation/create']); ?>

    <?= $form->field($model, 'relation_item_id')->hiddenInput(['value' => $item_id])->label(false); ?>

    <?= $form->field($model, 'relation_user_id')->dropDownList(
        ArrayHelper::map($users, 'id', 'title')
    ) ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('frontend', 'Add user'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>