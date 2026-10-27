<?php

namespace frontend\controllers;

use common\components\dnssync\ZoneSync;
use common\models\DnsSyncZone;
use common\models\Server;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/** DNS sync ISPmanager → Cloudflare: current sites of a server, AJAX actions over one zone each. */
class DnsSyncController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['get', 'post'],
                    'load-isp' => ['post'],
                    'load-ns' => ['post'],
                    'load-before' => ['post'],
                    'toggle' => ['post'],
                    'run' => ['post'],
                    'load-after' => ['post'],
                    'add-zone' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(?int $server = null): string
    {
        $model = $server === null ? null : Server::findById($server);
        return $this->render('index', [
            'servers' => Server::find()->orderBy(['title' => SORT_ASC])->all(),
            'server' => $model,
            'zones' => $model === null ? [] : DnsSyncZone::forItems(DnsSyncZone::currentItems($model)),
        ]);
    }

    /** Compatibility for bookmarks of the former reports. */
    public function actionCreate(): Response
    {
        return $this->redirect(['index']);
    }

    public function actionView(): Response
    {
        return $this->redirect(['index']);
    }

    public function actionLoadIsp(int $server, int $zone, string $domain): array
    {
        $model = $this->findZone($server, $zone, $domain);
        ZoneSync::configured()->loadIsp($model);
        return $this->row($model);
    }

    public function actionLoadNs(int $server, int $zone, string $domain): array
    {
        $model = $this->findZone($server, $zone, $domain);
        ZoneSync::configured()->loadNs($model);
        return $this->row($model);
    }

    public function actionLoadBefore(int $server, int $zone, string $domain): array
    {
        $model = $this->findZone($server, $zone, $domain);
        ZoneSync::configured()->loadBefore($model);
        return $this->row($model);
    }

    public function actionToggle(int $server, int $zone, string $domain): array
    {
        $model = $this->findZone($server, $zone, $domain);
        $model->selected = (int)Yii::$app->request->post('selected') === 1 ? 1 : 0;
        $model->save(false, ['selected']);
        return $this->row($model);
    }

    public function actionRun(int $server, int $zone, string $domain): array
    {
        $model = $this->findZone($server, $zone, $domain);
        set_time_limit(120);
        // Do not hold the session lock: other tabs must get "Зона уже выполняется", not wait.
        Yii::$app->session->close();
        ZoneSync::configured()->run($model);
        return $this->row($model);
    }

    public function actionLoadAfter(int $server, int $zone, string $domain): array
    {
        $model = $this->findZone($server, $zone, $domain);
        ZoneSync::configured()->loadAfter($model);
        return $this->row($model);
    }

    public function actionAddZone(int $server, int $zone, string $domain): array
    {
        $model = $this->findZone($server, $zone, $domain);
        Yii::$app->session->close();
        ZoneSync::configured()->addZone($model, (string)Yii::$app->request->post('account'));
        return $this->row($model);
    }

    private function findZone(int $server, int $zone, string $domain): DnsSyncZone
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return DnsSyncZone::findForServer($server, $zone, $domain);
    }

    private function row(DnsSyncZone $zone): array
    {
        $zone->refresh();
        return ['row' => $this->renderPartial('_zone-row', ['zone' => $zone])];
    }
}
