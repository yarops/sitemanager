<?php

namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * ServerCheck model.
 *
 * @property string $id
 * @property string $server_id
 * @property string $title
 * @property string $report
 * @property string $publish_date
 * @property int $is_archived
 * @property string|null $archived_at
 * @property int|null $archived_by
 *
 * @property ServerCheck $template
 */
class ServerCheck extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%server_check}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['server_id', 'is_archived', 'archived_by'], 'integer'],
            [['report'], 'string'],
            [['publish_date', 'archived_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('backend', 'ID'),
            'server_id' => Yii::t('backend', 'Parent ID'),
            'title' => Yii::t('backend', 'Title'),
            'report' => Yii::t('backend', 'Report'),
            'publish_date'   => Yii::t('backend', 'Publish date'),
            'is_archived' => Yii::t('backend', 'Archived'),
            'archived_at' => Yii::t('backend', 'Archived at'),
            'archived_by' => Yii::t('backend', 'Archived by'),
        ];
    }

    /**
     * Return server check list
     *
     * @return ActiveDataProvider
     */
    public static function findServerChecks(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => ServerCheck::find(),
            'pagination' => false
        ]);
    }

    /**
     * Linked server.
     */
    public function getServer(): ActiveQuery
    {
        return $this->hasOne(Server::class, ['id' => 'server_id']);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findById(int $id): ServerCheck
    {
        if (($model = ServerCheck::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested item does not exist.');
    }
}
