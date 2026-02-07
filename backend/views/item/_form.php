<?php

use common\models\Item;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\ServerUser;
use yii\helpers\Url;
use kartik\depdrop\DepDrop;

/* @var $this yii\web\View */
/* @var $model common\models\Item */
/* @var $form yii\widgets\ActiveForm */
/* @var $authors yii\db\ActiveRecord[] */
/* @var $server yii\db\ActiveRecord[] */
/* @var $serverUsers yii\db\ActiveRecord[] */
/* @var $template yii\db\ActiveRecord[] */
/* @var $summa yii\db\ActiveRecord[] */
?>

<div class="item-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="box">
        <div class="row">
            <div class="col-xs-2">
                <?= $form->field($model, 'protocol')->dropDownList([
                'https' => 'https',
                'http' => 'http',
]) ?>
            </div>
            <div class="col-xs-4">
                <?= $form->field($model, 'domain')->textInput(['maxlength' => 255]) ?>
            </div>
            <div class="col-xs-4">
                <?= $form->field($model, 'alias')->textInput(['maxlength' => 255]) ?>
            </div>
            <div class="col-xs-2">
                <?= $form->field($model, 'parent_id')->textInput(['maxlength' => 11]) ?>
            </div>
        </div>

        <?= $form->field($model, 'admin_link')->textInput(['maxlength' => 512]) ?>

        <div class="admin_link-autocomplit">
            <a href="#" data-id="/sitemanage"
               class="admin_link-autocomplit__link admin_link-autocomplit--js">/sitemanage</a>
            <a href="#" data-id="/wp-admin"
               class="admin_link-autocomplit__link admin_link-autocomplit--js">/wp-admin</a>
            <a href="#" data-id="/user/login"
               class="admin_link-autocomplit__link admin_link-autocomplit--js">/user/login</a>
        </div>
    </div>

    <div class="box">
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'server_id')->dropDownList(
                    ArrayHelper::map(
                        $server,
                        'id',
                        function ($server) {
                            return $server['ip'] . ' - ' . $server['title'];
                        }
                    ),
                    ['id' => 'server-id']
                ); ?>
                <?= Html::hiddenInput('input-server-user-id', $model->server_user_id, ['id' => 'input-server-user-id']); ?>
            </div>
            <div class="col-sm-6">
                <?php
                if (!empty($serverUsers)) {
                    foreach ($serverUsers as $serverUser) {
                        $data[$serverUser['id']] = $serverUser['title'];
                    }
                    echo $form->field($model, 'server_user_id')->widget(DepDrop::classname(), [
                        'type' => DepDrop::TYPE_SELECT2,
                        'options' => ['id' => 'server-user-id'],
                        'data' => $data,
                        'pluginOptions' => [
                            'depends' => ['server-id'],
                            'placeholder' => 'Select...',
                            'url' => Url::to(['/item/server-user']),
                            'params' => ['input-server-user-id'],
                        ],
                    ]);
                } else {
                    echo $form->field($model, 'server_user_id')->widget(DepDrop::classname(), [
                        'options' => ['id' => 'server-user-id'],
                        'pluginOptions' => [
                            'depends' => ['server-id'],
                            'placeholder' => 'Select...',
                            'url' => Url::to(['/item/server-user']),
                            'params' => ['input-server-user-id'],
                        ],
                    ]);
                }
                ?>
            </div>
        </div>
    </div>

    <div class="box">
        <?= $form->field($model, 'template_id')->dropDownList(
            ArrayHelper::map($template, 'id', 'title')
        ) ?>

        <?= $form->field($model, 'content')->textarea(['rows' => 6]) ?>

        <div class="row">
            <div class="col-xs-4">
                <?= $form->field($model, 'check_interval')->dropDownList([
                5 => '5 минут (критичные)',
                15 => '15 минут',
                60 => '1 час',
                360 => '6 часов',
                720 => '12 часов',
                1440 => '1 день (по умолчанию)',
], ['prompt' => 'Выберите интервал']) ?>
            </div>
            <div class="col-xs-4">
                <?= $form->field($model, 'notify_strategy')->dropDownList([
                Item::NOTIFY_IMMEDIATE => 'Мгновенно (в Telegram)',
                Item::NOTIFY_SUMMARY => 'Ежедневный отчет',
                Item::NOTIFY_DISABLED => 'Отключено',
]) ?>
            </div>
            <div class="col-xs-4">
                <?php echo $form->field($model, 'check_enabled')->checkbox() ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <?= $form->field($model, 'publish_date')->textInput() ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'publish_status')->dropDownList(
                    [
                        Item::STATUS_PUBLISH => Yii::t('backend', 'Published'),
                        Item::STATUS_DRAFT => Yii::t('backend', 'Draft'),
                    ]
                ) ?>
            </div>
            <div class="col-sm-4">
                <?= $form->field($model, 'author_id')->dropDownList(
                    ArrayHelper::map($authors, 'id', 'title')
                ) ?>
            </div>
        </div>

    </div>

    <div class="form-group">
        <?php echo Html::submitButton(
            $model->isNewRecord
                ? Yii::t('backend', 'Create')
                : Yii::t('backend', 'Update'),
            [
                'class' => $model->isNewRecord
                    ? 'btn btn-success'
                    : 'btn btn-primary'
            ],
        ); ?>
    </div>
    <?php ActiveForm::end(); ?>

</div>
