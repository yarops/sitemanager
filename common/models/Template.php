<?php

namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * Template model.
 *
 * @property string $id
 * @property string $parent_id
 * @property string $title
 * @property string $link
 * @property string $image_link
 * @property string $content
 * @property string $publish_date
 *
 * @property Template $template
 */
class Template extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%template}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['parent_id'], 'integer'],
            [['content'], 'string'],
            [['link', 'image_link'], 'string', 'max' => 512],
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
            'parent_id' => Yii::t('backend', 'Parent ID'),
            'title' => Yii::t('backend', 'Title'),
            'link' => Yii::t('backend', 'Link'),
            'image_link' => Yii::t('backend', 'Image link'),
            'content' => Yii::t('backend', 'Content'),
            'publish_date'   => Yii::t('backend', 'Publish date'),
        ];
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasMany(Item::class, array('id' => 'parent_id'));
    }

    /**
     * Return items for a template
     *
     * @return ActiveDataProvider
     */
    public function getItems(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Item::find()
                ->where([
                    'template_id' => $this->id,
                    'publish_status' => Item::STATUS_PUBLISH
                ])
        ]);
    }

    /**
     * Return templates list
     *
     * @return ActiveDataProvider
     */
    public static function findTemplates(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Template::find(),
            'pagination' => false
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findById(int $id): Template
    {
        if (($model = Template::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested item does not exist.');
    }
}
