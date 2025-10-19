<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:03
 */

namespace frontend\controllers;

use common\models\Item;
use common\models\Server;
use common\models\ServerCheck;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use DOMDocument;
use yii\web\Response;

class ServerController extends Controller
{


    public function actionIndex(): string
    {
        $query   = Server::find();
        $servers = new ActiveDataProvider(
            [
                'query'      => $query,
                'pagination' => [
                    'pageSize' => 20,
                ],
                'sort'       => [
                    'defaultOrder' => [
                        'title' => SORT_ASC,
                    ],
                ],
            ]
        );

        return $this->render(
            'index',
            [
                'servers' => $servers,
            ]
        );
    }

    public function actionView(int $id): string
    {
        $server = Server::findById($id);

        $filter_https = Yii::$app->request->get('http');

        if ($filter_https == 'on') {
            $items = new ActiveDataProvider(
                [
                    'query' => Item::find()
                        ->where([ 'protocol' => 'http' ])
                        ->andWhere([ 'server_id' => $server->id ])
                        ->orderBy([ 'publish_date' => SORT_DESC ]),
                ]
            );

            $items->setPagination(
                [
                    'pageSize' => Yii::$app->params['pageSize'],
                ]
            );

            return $this->render(
                'view',
                [
                    'server'  => $server,
                    'items'   => $items,
                    'servers' => Server::findServers(),
                ]
            );
        }

        $items = $server->getItems();
        $items->setPagination(
            [
                'pageSize' => Yii::$app->params['pageSize'],
            ]
        );

        return $this->render(
            'view',
            [
                'server'  => $server,
                'items'   => $items,
                'servers' => Server::findServers(),
            ]
        );
    }

    public function actionCheckDiff(int $id): string
    {
        $result       = '';
        $server       = Server::findById($id, true);
        $domainsQuery = $server->getItems();
        $domains      = $domainsQuery->query->all();

        $api_url = 'https://' . $server->ip . ':1500/ispmgr?authinfo=root:' . $server->password . '&out=xml&func=webdomain';

        $curlInit = curl_init($api_url);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlInit, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curlInit, CURLOPT_SSL_VERIFYPEER, 0);

        // get answer
        $response = curl_exec($curlInit);

        if (curl_errno($curlInit)) {
            var_dump(curl_errno($curlInit));
            die;
        }

        $curl_info   = curl_getinfo($curlInit);
        $header_size = $curl_info['header_size'];
        $header      = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);

        // После этого переменная $result содержит XML-документ со списком WWW-доменов,
        // либо сообщение об ошибке

        $isp_domains   = [];
        $admin_domains = [];

        $doc = new DOMDocument();
        if ($doc->loadXML($body)) {
            $root = $doc->documentElement;
            foreach ($root->childNodes as $elem) {
                foreach ($elem->childNodes as $node) {
                    if ($node->nodeType !== 1) { // Element nodes are of nodeType 1. Text 3. Comments 8. etc rtm
                        continue;
                    }
                    if ($node->tagName == 'name') {
                        $isp_domains[] = $node->nodeValue;
                    }
                }
            }
        }

        foreach ($domains as $domain) {
            $admin_domains[] = $domain->domain;
        }

        return $this->render(
            'checkDiff',
            [
                'model'      => $server,
                'diff_isp'   => array_diff($isp_domains, $admin_domains),
                'diff_admin' => array_diff($admin_domains, $isp_domains),
            ]
        );
    }

    /**
     * @return string|Response
     */
    public function actionCheckOnline(int $id)
    {
        ini_set('max_execution_time', 1800); // 300 seconds = 5 minutes

        $result      = [];
        $server       = Server::findById($id, true);
        $domainsQuery = $server->getItems();
        $domains      = $domainsQuery->query->all();

        foreach ($domains as $domain) {
            $host = $domain->protocol . '://' . $domain->domain;

            $result[ $host ] = $this->checkOnline($host);
        }

        $serverCheckModel = new ServerCheck();

        if (!empty($result)) {
            $serverCheckModel->server_id = $server->id;
            $serverCheckModel->title = $server->ip . date('j F Y');
            $serverCheckModel->report = json_encode($result);

            if ($serverCheckModel->save()) {
                return $this->redirect(
                    ['server-check/view', 'id' => $serverCheckModel->id]
                );
            }
        }

        return $this->render(
            'checkOnline',
            [
                'model'   => $server,
                'checked' => false,
            ]
        );
    }

    /**
     * Check host and return http response code
     *
     * @param string $domain
     *
     * @return string
     */
    private function checkOnline(string $domain): string
    {
        $curlInit = curl_init($domain);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

        // get answer
        $response = curl_exec($curlInit);

        if (! curl_errno($curlInit)) {
            $info = curl_getinfo($curlInit);
        }

        curl_close($curlInit);

        if ($response) {
            return $info['http_code'];
        }
        return '0';
    }
}
