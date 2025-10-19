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
            [['server_id'], 'integer'],
            [['report'], 'string'],
            [['publish_date'], 'safe'],
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
