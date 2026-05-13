<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:03
 */

namespace frontend\controllers;

use common\models\Server;
use common\models\ServerCheck;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class ServerCheckController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['archive', 'restore'],
                'rules' => [
                    [
                        'actions' => ['archive', 'restore', 'archive-all'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'archive' => ['post'],
                    'restore' => ['post'],
                    'archive-all' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $isArchived = (int)Yii::$app->request->get('archived', 0) === 1;
        $query = ServerCheck::find()
            ->where(['is_archived' => $isArchived ? 1 : 0])
            ->with('server');

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
            'server' => null,
            'servers' => Server::findServers(),
            'isArchived' => $isArchived,
        ]);
    }

    public function actionServer(int $id): string
    {
        $server = Server::findById($id);
        $isArchived = (int)Yii::$app->request->get('archived', 0) === 1;
        $query = ServerCheck::find()
            ->where([
                'server_id' => $id,
                'is_archived' => $isArchived ? 1 : 0,
            ])
            ->with('server');

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
            'server' => $server,
            'servers' => Server::findServers(),
            'isArchived' => $isArchived,
        ]);
    }

    public function actionView(int $id): string
    {
        $serverCheck = ServerCheck::findById($id);

        return $this->render('view', [
            'model' => $serverCheck,
        ]);
    }

    public function actionArchive(int $id): Response
    {
        $serverCheck = ServerCheck::findById($id);
        $serverCheck->is_archived = 1;
        $serverCheck->archived_at = date('Y-m-d H:i:s');
        $serverCheck->archived_by = Yii::$app->user->id;
        $serverCheck->save(false);

        $referrer = Yii::$app->request->referrer;
        if (!empty($referrer)) {
            return $this->redirect($referrer);
        }

        return $this->redirect(['index']);
    }

    public function actionRestore(int $id): Response
    {
        $serverCheck = ServerCheck::findById($id);
        $serverCheck->is_archived = 0;
        $serverCheck->archived_at = null;
        $serverCheck->archived_by = null;
        $serverCheck->save(false);

        $referrer = Yii::$app->request->referrer;
        if (!empty($referrer)) {
            return $this->redirect($referrer);
        }

        return $this->redirect(['index', 'archived' => 1]);
    }

    public function actionArchiveAll(?int $serverId = null): Response
    {
        $condition = ['is_archived' => 0];
        if ($serverId !== null) {
            $condition['server_id'] = $serverId;
        }

        ServerCheck::updateAll(
            [
                'is_archived' => 1,
                'archived_at' => date('Y-m-d H:i:s'),
                'archived_by' => Yii::$app->user->id,
            ],
            $condition
        );

        if ($serverId !== null) {
            return $this->redirect(['server', 'id' => $serverId]);
        }

        return $this->redirect(['index']);
    }
}
