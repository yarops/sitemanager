<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:03
 */

namespace frontend\controllers;

use common\models\ServerCheck;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;

class ServerCheckController extends Controller
{
    public function actionIndex(): string
    {
        //$items = Item::findByCurrentUser();
        $query = ServerCheck::find();
        $items = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index', [
            'items' => $items,
        ]);
    }

    public function actionView(int $id): string
    {
        $serverCheck = ServerCheck::findById($id);

        return $this->render('view', [
            'model' => $serverCheck,
        ]);
    }
}
