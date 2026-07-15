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
use common\models\Item;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class ServerCheckController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['archive', 'restore', 'archive-all', 'archive-item', 'recheck-site', 'remove-site-from-report'],
                'rules' => [
                    [
                        'actions' => ['archive', 'restore', 'archive-all', 'archive-item', 'recheck-site', 'remove-site-from-report'],
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
                    'archive-item' => ['post'],
                    'recheck-site' => ['post'],
                    'remove-site-from-report' => ['post'],
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
        $report = json_decode($serverCheck->report, true);
        if (!is_array($report)) {
            $report = [];
        }

        return $this->render('view', [
            'model' => $serverCheck,
            'itemsByUrl' => $this->findItemsByReportUrls($serverCheck, array_keys($report)),
        ]);
    }

    public function actionArchiveItem(int $id): Response
    {
        Item::findById($id, true)->archive(Yii::$app->user->id);

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /**
     * Recheck one URL from a saved server-check report and update its response code.
     */
    public function actionRecheckSite(int $id): Response
    {
        $serverCheck = ServerCheck::findById($id);
        $url = (string)Yii::$app->request->get('url', '');
        $report = json_decode($serverCheck->report, true);

        if (!is_array($report) || $url === '' || !array_key_exists($url, $report)) {
            throw new BadRequestHttpException('URL is not present in this server check report.');
        }

        $result = [
            'status' => $this->checkUrl($url),
        ];
        $itemsByUrl = $this->findItemsByReportUrls($serverCheck, [$url]);
        $item = $itemsByUrl[$url] ?? null;
        $aliasUrl = $item && !empty($item->alias) && $item->alias !== $item->domain
            ? $item->protocol . '://' . $item->alias
            : (is_array($report[$url]) ? ($report[$url]['alias_url'] ?? null) : null);

        if ($aliasUrl) {
            $result['alias_url'] = $aliasUrl;
            $result['alias_status'] = $this->checkUrl($aliasUrl);
        }
        $report[$url] = $result;
        $serverCheck->report = json_encode($report);
        $serverCheck->save(false, ['report']);

        return $this->redirect(['view', 'id' => $serverCheck->id]);
    }

    /**
     * Remove one URL from a saved server-check report without changing the site itself.
     */
    public function actionRemoveSiteFromReport(int $id): Response
    {
        $serverCheck = ServerCheck::findById($id);
        $url = (string)Yii::$app->request->get('url', '');
        $report = json_decode($serverCheck->report, true);

        if (!is_array($report) || $url === '' || !array_key_exists($url, $report)) {
            throw new BadRequestHttpException('URL is not present in this server check report.');
        }

        unset($report[$url]);
        $serverCheck->report = json_encode($report);
        $serverCheck->save(false, ['report']);

        return $this->redirect(['view', 'id' => $serverCheck->id]);
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

    private function findItemsByReportUrls(ServerCheck $serverCheck, array $urls): array
    {
        $domains = [];
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if (!empty($host)) {
                $domains[] = $host;
            }
        }

        $domains = array_values(array_unique($domains));
        if (empty($domains)) {
            return [];
        }

        $query = Item::find()->where(['domain' => $domains]);
        if (!empty($serverCheck->server_id)) {
            $query->andWhere(['server_id' => $serverCheck->server_id]);
        }

        $itemsByUrl = [];
        foreach ($query->all() as $item) {
            $itemsByUrl[$item->protocol . '://' . $item->domain] = $item;
        }

        return $itemsByUrl;
    }

    private function checkUrl(string $url): string
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $statusCode = !curl_errno($curl) && $response
            ? (string)curl_getinfo($curl, CURLINFO_HTTP_CODE)
            : '0';

        curl_close($curl);

        return $statusCode;
    }
}
