<?php

namespace frontend\controllers;

use common\models\Check;
use common\models\Item;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;

/**
 * Check controller for displaying site monitoring results.
 */
class CheckController extends Controller
{
    /**
     * Display all check results.
     */
    public function actionIndex(): string
    {
        $query = Check::find()
            ->with('item')
            ->orderBy(['check_date' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Display check results for a specific site.
     */
    public function actionSite(int $id): string
    {
        $item = Item::findById($id);

        $query = Check::find()
            ->where(['item_id' => $id])
            ->orderBy(['check_date' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('site', [
            'item' => $item,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Display monitoring dashboard with site statuses.
     */
    public function actionDashboard(): string
    {
        $items = Item::find()
            ->where(['check_enabled' => 1, 'publish_status' => Item::STATUS_PUBLISH])
            ->with('lastCheck')
            ->orderBy(['domain' => SORT_ASC])
            ->all();

        return $this->render('dashboard', [
            'items' => $items,
        ]);
    }
}
