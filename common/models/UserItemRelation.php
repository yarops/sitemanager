<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * ServerUser model
 *
 * @property int $id
 * @property int $relation_user_id идентификатор пользователя
 * @property int $relation_item_id идентификатор итема
 *
 * @property User $user
 * @property Item $item
 */
class UserItemRelation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%user_item_relation}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'relation_user_id', 'relation_item_id'], 'integer'],
            [['relation_user_id', 'relation_item_id'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('backend', 'ID'),
            'relation_user_id' => Yii::t('backend', 'User ID'),
            'relation_item_id' => Yii::t('backend', 'Item ID'),
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'relation_user_id']);
    }

    public function getItem(): ActiveQuery
    {
        return $this->hasOne(Item::class, ['id' => 'relation_item_id']);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findItemsByUser(int $user_id): array
    {
        if (($item_ids = UserItemRelation::find()->where(['relation_user_id' => $user_id])->all()) !== null) {

            return Item::findByIds($item_ids);
        }

        throw new NotFoundHttpException('The requested ServerUser does not exist.');
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findUsersByItem(int $item_id): array
    {
        if (($users = UserItemRelation::find()->where(['relation_item_id' => $item_id])->all()) !== null) {

            $users_ids = ArrayHelper::getColumn($users, 'relation_user_id');

            return User::findByUsersIds($users_ids);
        }

        throw new NotFoundHttpException('The requested ServerUser does not exist.');
    }
}
