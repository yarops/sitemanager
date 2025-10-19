<?php

use common\models\User;
use common\models\UserAdminPassword;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var UserAdminPassword $userAdminPassword */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <div class="box">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'username')->textInput() ?>

        <?= $form->field($model, 'title')->textInput() ?>

        <?= $form->field($model, 'email')->textInput() ?>

        <?= $form->field($model, 'new_password')->passwordInput() ?>

        <?= $form->field($model, 'status')->dropDownList([
            User::STATUS_DELETED => Yii::t('backend', 'Inactive'),
            User::STATUS_ACTIVE  => Yii::t('backend', 'Active'),
        ]) ?>

        <?= $form->field($model, 'role')->dropDownList([
            User::ROLE_USER  => Yii::t('backend', 'User'),
            User::ROLE_ADMIN => Yii::t('backend', 'Administrator'),
        ]) ?>

        <?= $form->field($model, 'admin_username')->textInput() ?>

        <?= $form->field($model, 'admin_password')->textInput() ?>
    </div>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('backend', 'Create') : Yii::t('backend', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?php if (isset($userAdminPassword)) : ?>
        <div class="box">
            <div class="server-users">
                <?php /** @var UserAdminPassword $user_admin_password */ ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>User admin password</th>
                        <th scope="col">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($model->getUserAdminPasswords()->models as $userPass) : ?>
                        <tr class="server-user">
                            <td>
                                <?= htmlspecialchars($userPass->user_admin_pass) ?>
                            </td>
                            <td class="td">
                                <?= Html::a(Yii::t('backend', 'Edit'), ['server-user/update', 'id' => $userPass->id], ['class' => 'btn btn-primary']) ?>
                                <?= Html::a(Yii::t('backend', 'Delete'), ['server-user/delete', 'id' => $userPass->id], [
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

                <?= $this->render('../user-admin-password/_form', [
                    'model' => $userAdminPassword,
                    'user_id' => $model->id,
                    'action' => ['user-admin-password/create']
                ]) ?>
            </div>
        </div>
    <?php endif; ?>

</div>
