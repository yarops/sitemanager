<?php

namespace backend\models\search;

use common\models\Server;
use kartik\daterange\DateRangeBehavior;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * ServerSearch represents the model behind the search form about `common\models\Item`.
 */
class ServerSearch extends Server
{

  public function rules(): array
  {
    return [
      [['title'], 'safe'],
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
    $query = Server::find();

    $dataProvider = new ActiveDataProvider([
      'query' => $query,
    ]);

    if (!($this->load($params) && $this->validate())) {
      return $dataProvider;
    }

    $query->andFilterWhere([
      'OR',
      // 'id' => $this->id,
      ['LIKE', 'title', $this->title],
    ]);

    return $dataProvider;
  }
}
