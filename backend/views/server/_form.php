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
                    ->where(['server_user_id' => $sUser->id, 'is_archived' => 0])
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
                  <?= Html::a(Yii::t('backend', 'Archive'), ['server-user/archive', 'id' => $sUser->id], [
                    'class' => 'btn btn-warning',
                    'data'  => [
                      'confirm' => Yii::t('backend', 'Archive this server user? It must not have active sites.'),
                      'method'  => 'post',
                    ],
                  ]) ?>
                  <?= Html::a(Yii::t('backend', 'Delete'), ['server-user/delete', 'id' => $sUser->id], [
                    'class' => 'btn btn-danger',
                    'data'  => [
                      'confirm' => Yii::t('backend', 'Hard delete this server user? Use archive for historical records.'),
                      'method'  => 'post',
                    ],
                  ]) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php
        $archivedServerUsers = ServerUser::find()
            ->where(['server_id' => $model->id, 'is_archived' => 1])
            ->orderBy(['id' => SORT_DESC])
            ->all();
        ?>
        <?php if (!empty($archivedServerUsers)): ?>
          <h3>Archived server users</h3>
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th scope="col">User credentials</th>
                <th scope="col">Ftp</th>
                <th scope="col">Archived at</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 0; ?>
              <?php foreach ($archivedServerUsers as $sUser) : ?>
                <?php $i++; ?>
                <tr class="server-user">
                  <td><?php echo $i; ?></td>
                  <td><?= htmlspecialchars($sUser->title) ?></td>
                  <td>
                    <?= htmlspecialchars($sUser->user_login) ?><br>
                    <?= htmlspecialchars($sUser->user_pass) ?>
                  </td>
                  <td>
                    <?= htmlspecialchars($sUser->ftp_login) ?><br>
                    <?= htmlspecialchars($sUser->ftp_pass) ?>
                  </td>
                  <td><?= htmlspecialchars($sUser->archived_at) ?></td>
                  <td class="td">
                    <?= Html::a(Yii::t('backend', 'Edit'), ['server-user/update', 'id' => $sUser->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a(Yii::t('backend', 'Restore'), ['server-user/restore', 'id' => $sUser->id], [
                      'class' => 'btn btn-success',
                      'data'  => [
                        'confirm' => Yii::t('backend', 'Restore this server user from archive?'),
                        'method'  => 'post',
                      ],
                    ]) ?>
                    <?= Html::a(Yii::t('backend', 'Delete'), ['server-user/delete', 'id' => $sUser->id], [
                      'class' => 'btn btn-danger',
                      'data'  => [
                        'confirm' => Yii::t('backend', 'Hard delete this server user? Use archive for historical records.'),
                        'method'  => 'post',
                      ],
                    ]) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>
</div>
