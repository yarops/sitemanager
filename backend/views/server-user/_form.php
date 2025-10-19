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
/* @var string $server_id */
/* @var yii\widgets\ActiveForm $form */
/* @var common\models\ServerUser $model */
?>

<div class="server-user-form">

    <?php
    $form = ActiveForm::begin(['action' => $action]);
    ?>

    <h3 class="title">Add new user</h3>
    <div class="row">
      <div class="col-sm-6">
        <?= $form->field($model, 'title')->textInput(['maxlength' => 255]) ?>
      </div>
      <div class="col-sm-6">
        <?php
        if ($model->server_id) {
            echo $form->field($model, 'server_id')->textInput()->label(false);
        } else {
            echo $form->field($model, 'server_id')->textInput(['value'=>$server_id])->label('Server id');
        }
        ?>
      </div>
    </div>

    <div class="row">
        <div class="col-sm-4">
            
            <?= $form->field($model, 'user_login')->textInput(['maxlength' => 128]) ?>
            <?= $form->field($model, 'user_pass')->textInput(['maxlength' => 128]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'ftp_login')->textInput(['maxlength' => 128]) ?>
            <?= $form->field($model, 'ftp_pass')->textInput(['maxlength' => 128]) ?>
        </div>
        <div class="col-sm-4">
            <?= $form->field($model, 'editor_ftp_login')->textInput(['maxlength' => 128]) ?>
            <?= $form->field($model, 'editor_ftp_pass')->textInput(['maxlength' => 128]) ?>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col-sm-8">
                
            </div>
            <div class="col-sm-4">
                <?= Html::submitButton(Yii::t('backend', 'Add'), ['class' => 'btn btn-success pull-right']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
