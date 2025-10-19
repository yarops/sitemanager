<?php

namespace backend\controllers;

use backend\models\search\ItemSearch;
use common\models\Server;
use common\models\ServerUser;
use common\models\Template;
use common\models\User;
use Yii;
use common\models\Item;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
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
                        'actions' => [
                            'index', 'view', 'create', 'update', 'delete', 'search', 'server-user'
                        ],
                        'allow' => true,
                        'roles' => ['admin'],
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
        $searchModel = new ItemSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render(
            'index',
            [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]
        );
    }

    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model' => Item::findById($id, true),
        ]);
    }

  /**
   * Create item.
   *
   * @return string|Response
   */
    public function actionCreate()
    {

        $model = new Item();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if ($model->save()) {
                return $this->redirect(['index']);
            }

            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->author_id = Yii::$app->user->id;
        return $this->render('create', [
            'model' => $model,
            'server' => Server::find()->all(),
            'template' => Template::find()->orderBy(['id' => SORT_DESC])->all(),
            'authors' => User::find()->all()
        ]);
    }

  /**
   * Update item.
   *
   * @param int $id
   *
   * @return string|Response
   */
    public function actionUpdate(int $id)
    {
        $item = Item::findById($id, true);

        if ($item->load(Yii::$app->request->post()) && $item->save()) {
            return $this->redirect(['view', 'id' => $item->id]);
        }

        return $this->render('update', [
            'model' => $item,
            'server' => Server::find()->all(),
            'serverUsers' => self::getServerUsersByServerId($item->server_id),
            'template' => Template::find()->orderBy(['id' => SORT_DESC])->all(),
            'authors' => User::find()->all()
        ]);
    }

    public function actionDelete(int $id): Response
    {
        Item::findById($id, true)->delete();

        return $this->redirect(['index']);
    }

  /**
   * Search items.
   *
   * @return string
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
   * Get server users by server id.
   *
   * @return array
   */
    public function actionServerUser()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = [];
        if (isset($_POST['depdrop_parents'])) {
            $parents = $_POST['depdrop_parents'];
            if ($parents != null) {
                $server_id = $parents[0];
                $serverUsers = self::getServerUsersByServerId($server_id);

                if (is_array($serverUsers)) {
                    foreach ($serverUsers as $serverUser) {
                        $out[] = [
                            'id' => $serverUser['id'],
                            'name' => $serverUser['title'],
                        ];
                    }
                }

                // the getSubCatList function will query the database based on the
                // cat_id and return an array like below:
                // [
                //    ['id'=>'<sub-cat-id-1>', 'name'=>'<sub-cat-name1>'],
                //    ['id'=>'<sub-cat_id_2>', 'name'=>'<sub-cat-name2>']
                // ]

                $selected = [];
                if (!empty($_POST['depdrop_params'])) {
                    $params = $_POST['depdrop_params'];
                    $selected = $params[0];
                }

                return ['output' => $out, 'selected' => $selected];
            }
        }
        return ['output' => '', 'selected' => ''];
    }

    public function getServerUsersByServerId($server_id)
    {
        if (!$server_id) {
            return false;
        }

        if ($server_users = ServerUser::findByServerId($server_id)) {
            $data = ArrayHelper::toArray($server_users, [
                'common\models\ServerUser' => [
                    'id',
                    'title',
                ],
            ]);

            return $data;
        }

        return false;
    }
}
