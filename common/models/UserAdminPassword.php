<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * UserAdminPassword model
 *
 * @property int $id
 * @property int $user_id идентификатор родительского комментария
 * @property string $user_admin_pass пароль
 *
 * @property User $user
 */
class UserAdminPassword extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%user_admin_password}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'user_id'], 'integer'],
            [['user_admin_pass'], 'required'],
            [['user_admin_pass'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('backend', 'ID'),
            'user_id' => Yii::t('backend', 'User ID'),
            'user_admin_pass' => Yii::t('backend', 'User admin pass'),
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findById(int $id, bool $ignorePublishStatus = false): UserAdminPassword
    {
        if (($model = UserAdminPassword::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested user admin password does not exist.');
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findByUserId(int $user_id, bool $ignorePublishStatus = false): array
    {
        if (($user_passwords = UserAdminPassword::find()->where(['user_id' => $user_id])->all()) !== null) {

            return $user_passwords;
        }

        throw new NotFoundHttpException('The requested user admin password does not exist.');
    }
}
