<?php

namespace frontend\controllers;

use common\components\dnssync\RecordMapper;
use common\components\dnssync\ZoneSync;
use common\models\DnsSync;
use common\models\DnsSyncZone;
use common\models\Item;
use common\models\Server;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

/** DNS sync ISPmanager → Cloudflare: one sync per server, AJAX actions over one zone each. */
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

    public function actionIndex(): string
    {
        return $this->render('index', [
            'items' => new ActiveDataProvider([
                'query' => DnsSync::find()->with(['sourceServer', 'ispServer', 'author']),
                'pagination' => ['pageSize' => 20],
                'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            ]),
        ]);
    }

    public function actionCreate()
    {
        $servers = Server::find()->orderBy(['title' => SORT_ASC])->all();
        $request = Yii::$app->request;
        if ($request->isPost) {
            $source = Server::findOne((int)$request->post('source_server_id'));
            $isp = Server::findOne((int)$request->post('isp_server_id')) ?? $source;
            if ($source === null || $isp === null) {
                throw new BadRequestHttpException('Server is not selected.');
            }
            $sync = $this->createSync($source, $isp);
            return $this->redirect(['view', 'id' => $sync->id]);
        }

        return $this->render('create', [
            'servers' => $servers,
            'serverId' => (int)$request->get('server') ?: null,
        ]);
    }

    public function actionView(int $id): string
    {
        $sync = DnsSync::findById($id);

        return $this->render('view', [
            'model' => $sync,
            'zones' => $sync->getZones()->with(['item.childs', 'item.serverUser'])->all(),
        ]);
    }

    public function actionLoadIsp(int $id, int $zone): array
    {
        $model = $this->findZone($id, $zone);
        ZoneSync::configured()->loadIsp($model);
        return $this->row($model);
    }

    public function actionLoadNs(int $id, int $zone): array
    {
        $model = $this->findZone($id, $zone);
        ZoneSync::configured()->loadNs($model);
        return $this->row($model);
    }

    public function actionLoadBefore(int $id, int $zone): array
    {
        $model = $this->findZone($id, $zone);
        ZoneSync::configured()->loadBefore($model);
        return $this->row($model);
    }

    public function actionToggle(int $id, int $zone): array
    {
        $model = $this->findZone($id, $zone);
        $model->selected = (int)Yii::$app->request->post('selected') === 1 ? 1 : 0;
        $model->save(false, ['selected']);
        return $this->row($model);
    }

    public function actionRun(int $id, int $zone): array
    {
        $model = $this->findZone($id, $zone);
        set_time_limit(120);
        // Do not hold the session lock: other tabs must get "Зона уже выполняется", not wait.
        Yii::$app->session->close();
        ZoneSync::configured()->run($model);
        return $this->row($model);
    }

    public function actionLoadAfter(int $id, int $zone): array
    {
        $model = $this->findZone($id, $zone);
        ZoneSync::configured()->loadAfter($model);
        return $this->row($model);
    }

    public function actionAddZone(int $id, int $zone): array
    {
        $model = $this->findZone($id, $zone);
        Yii::$app->session->close();
        ZoneSync::configured()->addZone($model, (string)Yii::$app->request->post('account'));
        return $this->row($model);
    }

    /** All non-archived sites of the server (published, drafts and demo), one row per zone. */
    private function createSync(Server $source, Server $isp): DnsSync
    {
        $transaction = DnsSync::getDb()->beginTransaction();
        try {
            $sync = new DnsSync([
                'title' => $source->ip . ' → ' . $isp->ip . ' ' . date('Y-m-d H:i'),
                'source_server_id' => $source->id,
                'isp_server_id' => $isp->id,
                'created_by' => Yii::$app->user->id,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $sync->save(false);
            // The provider paginates by 20: take the whole query.
            $items = $source->getItems(true)->query->orderBy(['parent_id' => SORT_ASC, 'id' => SORT_ASC])->all();
            $domains = [];
            /** @var Item $item */
            foreach ($items as $item) {
                $domain = RecordMapper::normalizeName((string)$item->domain);
                if ($domain === '' || isset($domains[$domain])) {
                    continue;
                }
                $domains[$domain] = true;
                (new DnsSyncZone(['sync_id' => $sync->id, 'item_id' => $item->id, 'domain' => $domain]))->save(false);
            }
            $transaction->commit();
            return $sync;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    private function findZone(int $id, int $zone): DnsSyncZone
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return DnsSyncZone::findInSync($id, $zone);
    }

    private function row(DnsSyncZone $zone): array
    {
        $zone->refresh();
        return ['row' => $this->renderPartial('_zone-row', ['zone' => $zone])];
    }
}
