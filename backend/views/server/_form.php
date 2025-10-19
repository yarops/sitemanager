<?php

use common\models\Item;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\ServerUser;

/* @var $this yii\web\View */
/* @var $model common\models\Server */
/* @var common\models\ServerUser $serverUser */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="server-form">

    <?php $form = ActiveForm::begin(); ?>

  <div class="box">

    <div class="row">
      <div class="col-sm-6">
        <?= $form->field($model, 'title')->textInput(['maxlength' => 255]) ?>
      </div>
      <div class="col-sm-6">
        <?= $form->field($model, 'ip')->textInput(['maxlength' => 255]) ?>
      </div>
    </div>

    <?= $form->field($model, 'link')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'credentials')->textarea(['maxlength' => 512]) ?>

    <?= $form->field($model, 'password')->textInput(['maxlength' => 255]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('backend', 'Create') : Yii::t('backend', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  </div>


    <?php ActiveForm::end(); ?>

    <?php if (isset($serverUser)) : ?>
    <div class="box">
      <div class="server-users">

        <?= $this->render('../server-user/_form', [
          'model' => $serverUser,
          'server_id' => $model->id,
          'action' => ['server-user/create']
        ]) ?>

        <?php /** @var ServerUser $server_user */ ?>
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th scope="col">User credentials</th>
              <th scope="col">Ftp</th>
              <th scope="col">Editor FTP</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 0; ?>
            <?php
            $i = 0;
            foreach ($model->getServerUsers()->models as $sUser) :
                $i++;
                $count_items = Item::find()
                ->where(['server_user_id' => $sUser->id])
                ->count();
                ?>
              <tr class="server-user">
                <td><?php echo $i; ?></td>
                <td>
                  <?= htmlspecialchars($sUser->title) . ' [' . $count_items . ']' ?>
                </td>
                <td>
                  <?= htmlspecialchars($sUser->title) ?><br>
                  <?= htmlspecialchars($sUser->user_pass) ?>
                </td>
                <td>
                  <?= htmlspecialchars($sUser->ftp_login) ?><br>
                  <?= htmlspecialchars($sUser->ftp_pass) ?>

                </td>
                <td>
                  <?= htmlspecialchars($sUser->editor_ftp_login) ?><br>
                  <?= htmlspecialchars($sUser->editor_ftp_pass) ?>

                </td>
                <td class="td">
                  <?= Html::a(Yii::t('backend', 'Edit'), ['server-user/update', 'id' => $sUser->id], ['class' => 'btn btn-primary']) ?>
                  <?= Html::a(Yii::t('backend', 'Delete'), ['server-user/delete', 'id' => $sUser->id], [
                    'class' => 'btn btn-danger',
                    'data'  => [
                      'confirm' => Yii::t('backend', 'Are you sure you want to delete this item?'),
                      'method'  => 'post',
                    ],
                  ]) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>
    <?php endif; ?>
</div>