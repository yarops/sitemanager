<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 13.12.14
 * Time: 23:37
 */

namespace backend\controllers;

use common\models\ServerUser;
use backend\models\ServerUserForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\User;

class ServerUserController extends Controller
{

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['create', 'update', 'delete', 'server-user'],
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

    /**
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new ServerUser();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['server/update', 'id' => $model->server_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * @return string|Response
     */
    public function actionUpdate(int $id)
    {
        $model = ServerUser::findById($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['server/update', 'id' => $model->server_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $serverUser = ServerUser::findById($id, true);
        $serverUser->delete();

        return $this->redirect(['server/update', 'id' => $serverUser->server_id]);
    }
}