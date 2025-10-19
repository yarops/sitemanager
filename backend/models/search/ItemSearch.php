<?php

namespace backend\models\search;

use common\models\Item;
use kartik\daterange\DateRangeBehavior;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * ItemSearch represents the model behind the search form about `common\models\Item`.
 */
class ItemSearch extends Item
{
    public $createTimeRange;
    public $createTimeStart;
    public $createTimeEnd;

    public function behaviors()
    {
        return [
            [
                'class' => DateRangeBehavior::className(),
                'attribute' => 'publish_date',
                'dateStartAttribute' => 'createTimeStart',
                'dateEndAttribute' => 'createTimeEnd',
            ]
        ];
    }

    public function attributes()
    {
        // add related fields to searchable attributes
        return array_merge(parent::attributes(), ['server.title', 'serverUser.title']);
    }

    public function rules(): array
    {
        return [
            [['domain', 'server.title', 'serverUser.title'], 'safe'],
            [['publish_date'], 'match', 'pattern' => '/^.+\s\-\s.+$/'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Item::find();
        $query->joinWith(['server']);
        $query->joinWith(['serverUser']);
        $query->orderBy(['id' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'OR',
            ['LIKE', 'domain', $this->domain],
            ['LIKE', 'server.title', $this->getAttribute('server.title')],
            ['LIKE', 'server_user.title', $this->getAttribute('serverUser.title')],
        ]);

        if ($this->createTimeStart !== null && $this->createTimeEnd) {
            $query->andFilterWhere(
                [
                    'BETWEEN',
                    new Expression(
                        'DATE_FORMAT(publish_date,"%Y/%m/%d")'
                    ),
                    date("Y/m/d", $this->createTimeStart),
                    date("Y/m/d", $this->createTimeEnd),
                ]
            );
        }

        return $dataProvider;
    }

    /**
     * Convert string time in format $format to UTC time format for SQL where clause
     * @param string $time Time in format $format
     * @param string $format Format of $time for the function date, default 'Y-m-d H:i:s'
     */
    private function timeToUTC($time, $format = 'Y-m-d H:i:s')
    {
        $timezoneOffset = \Yii::$app->formatter->asDatetime('now', 'php:O');
        return date($format, $time);
    }
}
