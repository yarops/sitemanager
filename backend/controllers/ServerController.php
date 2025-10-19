<?php

namespace backend\controllers;

use backend\models\search\ServerSearch;
use common\models\ServerUser;
use Yii;
use common\models\Server;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

class ServerController extends Controller
{

    public function behaviors(): array
    {
        return array(
            'access' => array(
                'class' => AccessControl::class,
                'rules' => array(
                    array(
                        'actions' => array( 'index', 'view', 'create', 'update', 'delete' ),
                        'allow'   => true,
                        'roles'   => array( 'admin' ),
                    ),
                ),
            ),
            'verbs'  => array(
                'class'   => VerbFilter::class,
                'actions' => array(
                    'delete' => array( 'post' ),
                ),
            ),
        );
    }

    public function actionIndex(): string
    {
        $searchModel  = new ServerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render(
            'index',
            array(
                'searchModel'  => $searchModel,
                'dataProvider' => $dataProvider,
            )
        );
    }

    /**
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new Server();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(
                array(
                    'update',
                    'id' => $model->id,
                )
            );
        }

        return $this->render(
            'create',
            array(
                'model' => $model,
            )
        );
    }

    /**
     * @return string|Response
     */
    public function actionUpdate(int $id)
    {
        $model = Server::findById($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(
                array(
                    'update',
                    'id' => $model->id,
                )
            );
        }

        return $this->render(
            'update',
            array(
                'model'      => $model,
                'serverUser' => new ServerUser(),
            )
        );
    }

    public function actionDelete(int $id): Response
    {
        Server::findById($id)->delete();

        return $this->redirect(array( 'index' ));
    }

    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model' => Server::findById($id, true),
        ]);
    }
}
