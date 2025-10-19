<?php

namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * Template model.
 *
 * @property string $id
 * @property string $job_id
 * @property string $item_id
 * @property string $check_status
 * @property string $check_date
 * @property int $response_time
 * @property string $error_message
 */
class Check extends ActiveRecord
{
    /**
     * Table name.
     *
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%check}}';
    }

    /**
     * Rules.
     *
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['item_id'], 'required'],
            [['item_id', 'response_time'], 'integer'],
            [['job_id', 'check_status', 'error_message'], 'string', 'max' => 255],
            [['check_date'], 'safe'],
        ];
    }

    /**
     * Attribute labels.
     *
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('backend', 'ID'),
            'job_id' => Yii::t('backend', 'Job id'),
            'item_id' => Yii::t('backend', 'Item id'),
            'check_status' => Yii::t('backend', 'Status'),
            'check_date' => Yii::t('backend', 'Date'),
            'response_time' => Yii::t('backend', 'Response Time (ms)'),
            'error_message' => Yii::t('backend', 'Error Message'),
        ];
    }

    /**
     * Return checks list
     *
     * @return ActiveDataProvider
     */
    public static function findChecks(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Check::find(),
            'pagination' => false
        ]);
    }

    /**
     * Get related item.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getItem()
    {
        return $this->hasOne(Item::class, ['id' => 'item_id']);
    }

    /**
     * Find check by id.
     *
     * @param int $id
     *
     * @return Check
     * @throws NotFoundHttpException
     */
    public static function findById(int $id): Check
    {
        if (($model = Check::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested item does not exist.');
    }
}
