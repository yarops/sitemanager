<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 13.12.14
 * Time: 23:37
 */

namespace backend\controllers;

use common\models\UserAdminPassword;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\User;

class UserAdminPasswordController extends Controller
{

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['create', 'update', 'delete', 'user-admin-password'],
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
        $model = new UserAdminPassword();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['user/update', 'id' => $model->user_id]);
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
        $model = UserAdminPassword::findById($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['user/update', 'id' => $model->user_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        $userAdminPassword = UserAdminPassword::findById($id, true);
        $userAdminPassword->delete();

        return $this->redirect(['user/update', 'id' => $userAdminPassword->user_id]);
    }
}