<?php

namespace frontend\models\search;

use common\models\Item;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * StuffSearch represents the model behind the search form about `common\models\Stuff`.
 */
class ItemSearch extends Item
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'server_id'], 'integer'],
            [['slug', 'domain'], 'safe'],
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

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $query->andFilterWhere(['like', 'domain', $params]);


        return $dataProvider;
    }
}
