<?php

namespace common\components\check;

use common\models\Check;
use common\models\Item;
use common\components\SiteNotification;
use yii\queue\JobInterface;

class WorkerCheck implements JobInterface
{
    public $item_id;
    public $url;


    /**
     * Constructor.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($config = [])
    {
        if (!empty($config)) {
            \Yii::configure($this, $config);
        }
    }

    /**
     * Execute the job.
     * @param \yii\queue\Queue $queue which pushed and is running the job
     */
    public function execute($queue)
    {
        try {
            \Yii::info("WorkerCheck: Starting check for {$this->url} (item_id: {$this->item_id})", 'queue');

            if (empty($this->item_id)) {
                \Yii::error("WorkerCheck: item_id is empty or null. URL: {$this->url}", 'queue');
                return;
            }

            $startTime = microtime(true);
            $checkResult = $this->performSiteCheck($this->url);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            \Yii::info("WorkerCheck: Check result - Status: {$checkResult['status']}, Response time: {$responseTime}ms", 'queue');

            // Save check result.
            $check = new Check();
            $check->item_id = $this->item_id;
            $check->job_id = $queue->id ?? 'unknown';
            $check->check_status = $checkResult['status'];
            $check->response_time = $responseTime;
            $check->error_message = $checkResult['error'];
            $check->check_date = date('Y-m-d H:i:s');

            if ($check->save()) {
                \Yii::info("WorkerCheck: Check result saved successfully with ID: {$check->id}", 'queue');

                // Send notification if check failed.
                if ($check->check_status !== '200') {
                    $item = Item::findOne($this->item_id);
                    if ($item) {
                        try {
                            $notification = new SiteNotification();
                            $notification->sendTelegramNotification($item, $check);
                        } catch (\Exception $e) {
                            \Yii::error('WorkerCheck: Failed to send Telegram notification: ' . $e->getMessage(), 'queue');
                        }
                    }
                }
            } else {
                \Yii::error('WorkerCheck: Failed to save check result: ' . json_encode($check->errors), 'queue');
            }


            \Yii::info('WorkerCheck: Job completed successfully', 'queue');
        } catch (\Exception $e) {
            \Yii::error('WorkerCheck: Exception occurred: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'queue');
            throw $e;
        }
    }


    /**
     * Get HTTP response code from headers.
     *
     * @param array $headers
     * @return string
     */
    private function getHttpResponseCode($headers)
    {
        if (is_array($headers) && isset($headers[0])) {
            return substr($headers[0], 9, 3);
        }
        return '000';
    }


    /**
     * Perform comprehensive site check.
     *
     * @param string $url
     * @return array
     */
    private function performSiteCheck($url)
    {
        // Try multiple endpoints for WordPress sites.
        $endpoints = [
            '/wp-json/custom/v1/status',
        ];

        foreach ($endpoints as $endpoint) {
            $testUrl = $url . $endpoint;
            $result = $this->checkUrl($testUrl);

            if ($result['status'] === '200') {
                return $result;
            }
        }

        // If all endpoints failed, return the last result.
        return $result ?? ['status' => '000', 'error' => 'All endpoints failed'];
    }

    /**
     * Check specific URL.
     *
     * @param string $url
     * @return array
     */
    private function checkUrl($url)
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
}
