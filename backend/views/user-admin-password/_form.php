<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 14.12.14
 * Time: 0:07
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var yii\web\View $this */
/* @var array $action */
/* @var string $user_id */
/* @var yii\widgets\ActiveForm $form */
/* @var common\models\UserAdminPassword $model */
?>

<div class="user-admin-password-form">

    <?php
    $form = ActiveForm::begin(['action' => $action]);
    ?>

    <h3 class="title">Add new user</h3>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'user_admin_pass')->textInput(['maxlength' => 128]) ?>
        </div>
        <div class="col-sm-6">
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col-sm-8">
                <?php
                if ($model->user_id) {
                    echo $form->field($model, 'user_id')->hiddenInput()->label(false);
                } else {
                    echo $form->field($model, 'user_id')->hiddenInput(['value'=>$user_id])->label(false);
                }
                ?>
            </div>
            <div class="col-sm-4">
                <?= Html::submitButton(Yii::t('backend', 'Add'), ['class' => 'btn btn-success pull-right']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
