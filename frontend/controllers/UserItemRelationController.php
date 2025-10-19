<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 13.12.14
 * Time: 23:37
 */

namespace frontend\controllers;

use common\models\UserItemRelation;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\User;

class UserItemRelationController extends Controller
{

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['create', 'delete'],
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
        $model = new UserItemRelation();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['item/view', 'id' => $model->relation_item_id]);
        }

        return false;

    }

    public function actionDelete(int $id): Response
    {
        $serverUser = UserItemRelation::findById($id, true);
        $serverUser->delete();

        return $this->redirect(['server/update', 'id' => $serverUser->server_id]);
    }
}