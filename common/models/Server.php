<?php

namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\web\NotFoundHttpException;

/**
 * Server model.
 *
 * @property string $id
 * @property string $title
 * @property string $link
 * @property string $ip
 * @property string $credentials
 * @property string $password
 * @property Item[] $items
 */
class Server extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%server}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['title'], 'required'],
            [['title', 'link', 'ip', 'password'], 'string', 'max' => 255],
            [['credentials',], 'string', 'max' => 512]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('backend', 'ID'),
            'title' => Yii::t('backend', 'Title'),
            'link' => Yii::t('backend', 'Link'),
            'ip' => Yii::t('backend', 'IP'),
            'credentials' => Yii::t('backend', 'Credentials'),
            'password' => Yii::t('backend', 'Password'),
        ];
    }

    /**
     * Return items for a server
     *
     * @return ActiveDataProvider
     */
    public function getItems(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Item::find()
                ->where([
                    'server_id' => $this->id,
                    'publish_status' => Item::STATUS_PUBLISH
                ])
        ]);
    }

    /**
     * Return servers list
     *
     * @return ActiveDataProvider
     */
    public static function findServers(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Server::find(),
            'pagination' => false
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findById(int $id): Server
    {
        if (($model = Server::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested item does not exist.');
    }

    public function getServerUsers(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $this->hasMany(ServerUser::class, ['server_id' => 'id']),
            'pagination' => false,
            'sort'=> ['defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }
}
