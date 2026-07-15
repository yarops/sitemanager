<?php

namespace frontend\controllers;

use common\models\Server;
use common\models\Template;
use common\models\User;
use common\models\UserItemRelation;
use frontend\models\ItemForm;
use frontend\models\search\ItemSearch;
use Yii;
use common\models\Item;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\Response;

class ItemController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'search', 'add-user', 'check-start'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $filterHttp = Yii::$app->request->get('http') === 'on';
        $filterStatus = Yii::$app->request->get('status');
        $filterPublication = Yii::$app->request->get('publication');
        if (!in_array($filterStatus, ['enabled', 'disabled', 'up', 'down', 'never_checked'], true)) {
            $filterStatus = null;
        }
        if (!in_array($filterPublication, [Item::STATUS_DRAFT, Item::STATUS_PUBLISH], true)) {
            $filterPublication = null;
        }

        $query = Item::find()->where(['is_archived' => 0]);
        if ($filterHttp) {
            $query->andWhere(['protocol' => 'http']);
        }
        if ($filterPublication !== null) {
            $query->andWhere(['publish_status' => $filterPublication]);
        }
        if ($filterStatus) {
            $query = $this->applyStatusFilter($query, $filterStatus);
        }
        $query->orderBy(['publish_date' => SORT_DESC]);

        $items = new ActiveDataProvider(['query' => $query]);
        $items->setPagination([
            'pageSize' => Yii::$app->params['pageSize']
        ]);

        return $this->render('index', [
            'items' => $items,
            'servers' => Server::findServers(),
            'current_status_filter' => $filterStatus,
            'current_publication_filter' => $filterPublication,
            'current_http_filter' => $filterHttp,
        ]);
    }

    /**
     * Apply status filter to query
     */
    private function applyStatusFilter($query, $status)
    {
        switch ($status) {
            case 'enabled':
                return $query->andWhere(['check_enabled' => 1]);
            case 'disabled':
                return $query->andWhere(['check_enabled' => 0]);
            case 'up':
                return $query->andWhere(['check_enabled' => 1])
                    ->leftJoin('`check`', '`check`.item_id = item.id AND `check`.check_date = (
                        SELECT MAX(check_date) FROM `check` WHERE `check`.item_id = item.id
                    )')
                    ->andWhere(['`check`.check_status' => '200']);
            case 'down':
                return $query->andWhere(['check_enabled' => 1])
                    ->leftJoin('`check`', '`check`.item_id = item.id AND `check`.check_date = (
                        SELECT MAX(check_date) FROM `check` WHERE `check`.item_id = item.id
                    )')
                    ->andWhere(['!=', '`check`.check_status', '200'])
                    ->andWhere(['not', ['`check`.check_status' => null]]);
            case 'never_checked':
                return $query->andWhere(['check_enabled' => 1])
                    ->leftJoin('`check`', '`check`.item_id = item.id')
                    ->andWhere(['`check`.check_status' => null]);
            default:
                return $query;
        }
    }

    public function actionView(int $id): string
    {
        $acceptedUsers = UserItemRelation::findUsersByItem($id);

        return $this->render('view', [
            'model' => $this->findVisibleItem($id),
            'acceptedUsers' => $acceptedUsers,
            'userItemRelation' => new UserItemRelation(),
            'users' => User::find()->all(),
        ]);
    }

    /**
     * @param $slug
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionSearch()
    {
        $request = Yii::$app->request;
        $search_query = $request->post('search_query');

        $searchModel = new ItemSearch();
        $items = $searchModel->search($search_query);

        return $this->render('search', [
            'items' => $items,
            'search_query' => ($search_query) ? $search_query : '',
        ]);
    }

    /**
     * @param $slug
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionAddUser()
    {
        $model = new UserItemRelation();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->item_id]);
        }

        // On failure, redirect back to the item page if possible.
        $itemId = $model->item_id ?: Yii::$app->request->post('UserItemRelation')['item_id'] ?? null;
        if ($itemId) {
            return $this->redirect(['view', 'id' => $itemId]);
        }

        return $this->redirect(['index']);
    }

    /**
     * @param $slug
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionCheckStart($id)
    {
        $model = $this->findVisibleItem($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // On failure, redirect back to the item's view page.
        return $this->redirect(['index']);
    }

    private function findVisibleItem(int $id): Item
    {
        $item = Item::find()->where(['id' => $id, 'is_archived' => 0])->one();
        if ($item === null) {
            throw new \yii\web\NotFoundHttpException('The requested item does not exist.');
        }

        return $item;
    }
}
