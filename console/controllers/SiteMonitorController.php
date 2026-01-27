<?php

namespace console\controllers;

use common\models\Item;
use common\models\Check;
use common\components\SiteNotification;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Site monitoring console controller.
 */
class SiteMonitorController extends Controller
{
    /**
     * Check all active sites manually.
     *
     * @return int
     */
    public function actionCheckAll()
    {
        $this->stdout("Starting manual site monitoring check...\n", Console::FG_GREEN);

        $items = Item::find()
            ->where(['check_enabled' => 1, 'publish_status' => Item::STATUS_PUBLISH])
            ->all();

        $totalSites = count($items);
        $checkedSites = 0;
        $downSites = 0;

        if ($totalSites === 0) {
            $this->stdout("No sites to check.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("Found {$totalSites} sites to check.\n", Console::FG_CYAN);

        foreach ($items as $item) {
            $url = $item->protocol . '://' . $item->domain;
            $this->stdout("Checking: {$item->domain} ({$url})... ", Console::FG_CYAN);

            $result = $this->checkSite($url);
            $checkedSites++;

            if ($result['status'] === '200') {
                $this->stdout('UP', Console::FG_GREEN);
                $this->stdout(" ({$result['response_time']}ms)\n");
            } else {
                $this->stdout('DOWN', Console::FG_RED);
                $this->stdout(" ({$result['error']})\n");
                $downSites++;
            }

            // Save check result.
            $this->saveCheckResult($item, $result);
        }

        $this->stdout("\nCheck completed!\n", Console::FG_GREEN);
        $this->stdout("Total sites: {$totalSites}\n");
        $this->stdout("Checked: {$checkedSites}\n");
        $this->stdout("Down: {$downSites}\n");

        if ($downSites > 0) {
            $this->stdout("⚠️  {$downSites} sites are currently down!\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Check a specific site by ID.
     *
     * @param int $itemId
     * @return int
     */
    public function actionCheckSite($itemId)
    {
        $item = Item::findOne($itemId);

        if (!$item) {
            $this->stdout("Site with ID {$itemId} not found.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $url = $item->protocol . '://' . $item->domain;
        $this->stdout("Checking site: {$item->domain} ({$url})\n", Console::FG_CYAN);

        $result = $this->checkSite($url);
        $this->saveCheckResult($item, $result);

        if ($result['status'] === '200') {
            $this->stdout('✅ Site is UP', Console::FG_GREEN);
            $this->stdout(" (Response time: {$result['response_time']}ms)\n");
            return ExitCode::OK;
        } else {
            $this->stdout('❌ Site is DOWN', Console::FG_RED);
            $this->stdout(" (Error: {$result['error']})\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Show status of all monitored sites.
     *
     * @return int
     */
    public function actionStatus()
    {
        $items = Item::find()
            ->where(['check_enabled' => 1, 'publish_status' => Item::STATUS_PUBLISH])
            ->with('lastCheck')
            ->all();

        if (empty($items)) {
            $this->stdout("No monitored sites found.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("Site Status Report\n", Console::FG_CYAN);
        $this->stdout("==================\n\n");

        foreach ($items as $item) {
            $url = $item->protocol . '://' . $item->domain;
            $latestCheck = $item->lastCheck;

            $this->stdout("{$item->domain} ({$url})\n");

            if ($latestCheck) {
                $status = $latestCheck->check_status;
                $color = $status === '200' ? Console::FG_GREEN : Console::FG_RED;
                $this->stdout('  Status: ', Console::FG_CYAN);
                $this->stdout($status, $color);
                $this->stdout("\n");
                $this->stdout("  Last check: {$latestCheck->check_date}\n");
                $this->stdout("  Response time: {$latestCheck->response_time}ms\n");
                if ($latestCheck->error_message) {
                    $this->stdout("  Error: {$latestCheck->error_message}\n");
                }
            } else {
                $this->stdout('  Status: ', Console::FG_CYAN);
                $this->stdout('Never checked', Console::FG_YELLOW);
                $this->stdout("\n");
            }

            $this->stdout("\n");
        }

        return ExitCode::OK;
    }

    /**
     * Show recent check history.
     *
     * @param int $limit
     * @return int
     */
    public function actionHistory($limit = 20)
    {
        $checks = Check::find()
            ->with('item')
            ->orderBy(['check_date' => SORT_DESC])
            ->limit($limit)
            ->all();

        if (empty($checks)) {
            $this->stdout("No check history found.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("Recent Check History\n", Console::FG_CYAN);
        $this->stdout("===================\n\n");

        foreach ($checks as $check) {
            $item = $check->item;
            if (!$item) {
                $this->stdout("{$check->check_date} - Unknown site (ID: {$check->item_id})\n");
                $this->stdout("  Status: {$check->check_status}\n\n");
                continue;
            }

            $url = $item->protocol . '://' . $item->domain;
            $status = $check->check_status;
            $color = $status === '200' ? Console::FG_GREEN : Console::FG_RED;

            $this->stdout("{$check->check_date} - {$item->domain} ({$url})\n");
            $this->stdout('  Status: ', Console::FG_CYAN);
            $this->stdout($status, $color);
            $this->stdout(" | Response: {$check->response_time}ms");
            if ($check->error_message) {
                $this->stdout(" | Error: {$check->error_message}");
            }
            $this->stdout("\n\n");
        }

        return ExitCode::OK;
    }

    /**
     * Enable monitoring for a site.
     *
     * @param int $itemId
     * @return int
     */
    public function actionEnable($itemId)
    {
        $item = Item::findOne($itemId);

        if (!$item) {
            $this->stdout("Site with ID {$itemId} not found.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $item->check_enabled = 1;
        if ($item->save()) {
            $this->stdout("✅ Monitoring enabled for {$item->domain}\n", Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stdout("❌ Failed to enable monitoring for {$item->domain}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Disable monitoring for a site.
     *
     * @param int $itemId
     * @return int
     */
    public function actionDisable($itemId)
    {
        $item = Item::findOne($itemId);

        if (!$item) {
            $this->stdout("Site with ID {$itemId} not found.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $item->check_enabled = 0;
        if ($item->save()) {
            $this->stdout("✅ Monitoring disabled for {$item->domain}\n", Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stdout("❌ Failed to disable monitoring for {$item->domain}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Check a single site and return result.
     *
     * @param string $url
     * @return array
     */
    private function checkSite($url): array
    {
        $startTime = microtime(true);

        // Try multiple endpoints for WordPress sites.
        $endpoints = [
            '/wp-sitemap.xml',
            '/wp-admin/',
            '/',
        ];

        foreach ($endpoints as $endpoint) {
            $testUrl = $url . $endpoint;
            $result = $this->checkUrl($testUrl);

            if ($result['status'] === '200') {
                $endTime = microtime(true);
                $result['response_time'] = round(($endTime - $startTime) * 1000);
                return $result;
            }
        }

        $endTime = microtime(true);
        $result['response_time'] = round(($endTime - $startTime) * 1000);
        return $result;
    }

    /**
     * Check specific URL.
     *
     * @param string $url
     * @return array
     */
    private function checkUrl($url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 30,
                'user_agent' => 'SiteMonitor/1.0',
                'follow_location' => true,
                'max_redirects' => 5,
            ]
        ]);

        $headers = @get_headers($url, 1, $context);

        if ($headers === false) {
            $error = error_get_last();
            return [
                'status' => '000',
                'error' => $error['message'] ?? 'Connection failed'
            ];
        }

        $statusCode = $this->getHttpResponseCode($headers);

        return [
            'status' => $statusCode,
            'error' => $statusCode === '200' ? null : "HTTP {$statusCode}"
        ];
    }

    /**
     * Get HTTP response code from headers.
     *
     * @param array $headers
     * @return string
     */
    private function getHttpResponseCode($headers): string
    {
        if (isset($headers[0])) {
            return substr($headers[0], 9, 3);
        }
        return '000';
    }

    /**
     * Save check result to database.
     *
     * @param Item $item
     * @param array $result
     * @return void
     */
    private function saveCheckResult(Item $item, array $result): void
    {
        $check = new Check();
        $check->item_id = $item->id;
        $check->check_status = $result['status'];
        $check->check_date = date('Y-m-d H:i:s');

        // Try to set new fields if they exist.
        if (property_exists($check, 'response_time')) {
            $check->response_time = $result['response_time'];
        }
        if (property_exists($check, 'error_message')) {
            $check->error_message = $result['error'];
        }

        if (!$check->save()) {
            $this->stdout("Warning: Failed to save check result for {$item->domain}\n", Console::FG_YELLOW);
            return;
        }

        // Send Telegram notification if check failed.
        if ($check->check_status !== '200') {
            try {
                $notification = new SiteNotification();
                $notification->sendTelegramNotification($item, $check);
            } catch (\Exception $e) {
                $this->stdout("Warning: Failed to send Telegram notification: {$e->getMessage()}\n", Console::FG_YELLOW);
            }
        }
    }
}
