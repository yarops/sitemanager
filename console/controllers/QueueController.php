<?php

namespace console\controllers;

use yii\console\Controller;
use yii\queue\redis\Queue;

/**
 * Queue management controller.
 *
 * This controller provides commands for managing the queue system.
 */
class QueueController extends Controller
{
    /**
     * Process one job from queue (wrapper for built-in queue/run).
     *
     * Usage: php yii queue/run
     */
    public function actionRun()
    {
        $this->stdout("Обработка задач из очереди...\n");

        /** @var Queue $queue */
        $queue = \Yii::$app->queue;
        $result = $queue->run(false);

        $this->stdout('Обработка завершена. Результат: ' . ($result ? 'задачи выполнены' : 'очередь пуста') . "\n");
    }

    /**
     * Show queue status.
     *
     * Usage: php yii queue/info
     */
    public function actionInfo()
    {
        /** @var Queue $queue */
        $queue = \Yii::$app->queue;

        $this->stdout("Queue Status:\n");
        $this->stdout('Channel: ' . $queue->channel . "\n");
        $this->stdout('Redis: ' . $queue->redis->hostname . ':' . $queue->redis->port . "\n");

        // Get queue size using Redis directly
        $redis = $queue->redis;
        $waitingKey = $queue->channel . '.waiting';
        $delayedKey = $queue->channel . '.delayed';
        $reservedKey = $queue->channel . '.reserved';
        $messagesKey = $queue->channel . '.messages';

        $waitingCount = $redis->llen($waitingKey);
        $delayedCount = $redis->zcard($delayedKey);
        $reservedCount = $redis->zcard($reservedKey);
        $messagesCount = $redis->hlen($messagesKey);

        $this->stdout('Waiting jobs: ' . $waitingCount . "\n");
        $this->stdout('Delayed jobs: ' . $delayedCount . "\n");
        $this->stdout('Reserved jobs: ' . $reservedCount . "\n");
        $this->stdout('Legacy messages: ' . $messagesCount . "\n");
        $this->stdout('Total jobs: ' . ($waitingCount + $delayedCount + $reservedCount + $messagesCount) . "\n");
    }

    /**
     * Clear queue.
     *
     * Usage: php yii queue/clear
     */
    public function actionClear()
    {
        $this->stdout("Clearing queue...\n");

        /** @var Queue $queue */
        $queue = \Yii::$app->queue;
        $redis = $queue->redis;

        // Clear all queue keys
        $keys = [
            $queue->channel . '.waiting',
            $queue->channel . '.delayed',
            $queue->channel . '.reserved',
            $queue->channel . '.done',
            $queue->channel . '.failed'
        ];

        foreach ($keys as $key) {
            $redis->del($key);
        }

        $this->stdout("Queue cleared.\n");
    }

    /**
     * Move all delayed jobs to waiting queue (execute immediately).
     *
     * Usage: php yii queue/run-delayed
     */
    public function actionRunDelayed()
    {
        $this->stdout("Moving delayed jobs to waiting queue...\n");

        /** @var Queue $queue */
        $queue = \Yii::$app->queue;
        $redis = $queue->redis;

        $delayedKey = $queue->channel . '.delayed';
        $waitingKey = $queue->channel . '.waiting';

        // Get all delayed jobs
        $delayedJobs = $redis->zrange($delayedKey, 0, -1, 'WITHSCORES');

        if (empty($delayedJobs)) {
            $this->stdout("No delayed jobs found.\n");
            return;
        }

        $movedCount = 0;
        // zrange with WITHSCORES returns [job1, score1, job2, score2, ...]
        for ($i = 0; $i < count($delayedJobs); $i += 2) {
            $job = $delayedJobs[$i];
            $score = $delayedJobs[$i + 1];

            // Move job from delayed to waiting
            $redis->lpush($waitingKey, $job);
            $redis->zrem($delayedKey, $job);
            $movedCount++;
        }

        $this->stdout("✅ Moved {$movedCount} delayed jobs to waiting queue.\n");
        $this->stdout("Jobs will be processed immediately by the queue worker.\n");
    }








    /**
     * Run immediate checks for all enabled sites (bypass delayed scheduling).
     *
     * Usage: php yii queue/run-immediate-checks
     */
    public function actionRunImmediateChecks()
    {
        $this->stdout("Running immediate checks for all enabled sites...\n");

        $items = \common\models\Item::find()
            ->where(['check_enabled' => 1, 'publish_status' => \common\models\Item::STATUS_PUBLISH])
            ->all();

        if (empty($items)) {
            $this->stdout("No enabled sites found.\n");
            return;
        }

        $count = 0;
        foreach ($items as $item) {
            $url = $item->protocol . '://' . $item->domain;

            $job = new \common\components\check\WorkerCheck([
                'item_id' => $item->id,
                'url' => $url
            ]);

            // Push immediately without delay
            \Yii::$app->queue->push($job);
            $count++;

            $this->stdout("Queued immediate check for {$item->domain} (ID: {$item->id})\n");
        }

        $this->stdout("\n✅ Immediate checks queued!\n");
        $this->stdout("Queued {$count} sites for immediate checking.\n");
        $this->stdout("Make sure queue worker is running: php yii queue/listen\n");
    }

    /**
     * Enable monitoring for all sites.
     *
     * Usage: php yii queue/enable-all-monitoring
     */
    public function actionEnableAllMonitoring()
    {
        $this->stdout("Enabling monitoring for all published sites...\n");

        $items = \common\models\Item::find()
            ->where(['publish_status' => \common\models\Item::STATUS_PUBLISH])
            ->all();

        $enabled = 0;
        $alreadyEnabled = 0;

        foreach ($items as $item) {
            if ($item->check_enabled == 1) {
                $alreadyEnabled++;
                $this->stdout("  {$item->domain} - already enabled\n");
            } else {
                $item->check_enabled = 1;
                if ($item->save()) {
                    $enabled++;
                    $this->stdout("  ✅ {$item->domain} - monitoring enabled\n");
                } else {
                    $this->stdout("  ❌ {$item->domain} - failed to enable\n");
                }
            }
        }

        $this->stdout("\n📊 Summary:\n");
        $this->stdout("  Enabled: {$enabled} sites\n");
        $this->stdout("  Already enabled: {$alreadyEnabled} sites\n");
        $this->stdout('  Total sites: ' . count($items) . "\n");

        if ($enabled > 0) {
            $this->stdout("\n💡 Tip: Мониторинг включен. Теперь тикер (monitoring/tick) начнет планировать проверки.\n");
        }
    }

    /**
     * Disable monitoring for all sites.
     *
     * Usage: php yii queue/disable-all-monitoring
     */
    public function actionDisableAllMonitoring()
    {
        $this->stdout("Disabling monitoring for all sites...\n");

        $items = \common\models\Item::find()
            ->where(['check_enabled' => 1])
            ->all();

        $disabled = 0;

        foreach ($items as $item) {
            $item->check_enabled = 0;
            if ($item->save()) {
                $disabled++;
                $this->stdout("  ✅ {$item->domain} - monitoring disabled\n");
            } else {
                $this->stdout("  ❌ {$item->domain} - failed to disable\n");
            }
        }

        $this->stdout("\n📊 Summary:\n");
        $this->stdout("  Disabled: {$disabled} sites\n");

        if ($disabled > 0) {
            $this->stdout("\n⚠️  Note: Existing scheduled checks will still run. Consider clearing the queue.\n");
            $this->stdout("   Run 'php yii queue/clear' to remove scheduled checks.\n");
        }
    }

    /**
     * Show monitoring status for all sites.
     *
     * Usage: php yii queue/monitoring-status
     */
    public function actionMonitoringStatus()
    {
        $this->stdout("Monitoring Status Report\n");
        $this->stdout("=======================\n\n");

        $allItems = \common\models\Item::find()
            ->where(['publish_status' => \common\models\Item::STATUS_PUBLISH])
            ->with('lastCheck')
            ->all();

        $enabled = 0;
        $disabled = 0;

        // Sort items by check status priority (DOWN sites first, then UP, then never checked, then disabled)
        usort($allItems, function ($a, $b) {
            $priorityA = $this->getCheckStatusPriority($a);
            $priorityB = $this->getCheckStatusPriority($b);

            if ($priorityA === $priorityB) {
                // If same priority, sort by domain alphabetically
                return strcmp($a->domain, $b->domain);
            }

            return $priorityA - $priorityB;
        });

        foreach ($allItems as $item) {
            $this->stdout("  {$item->domain} - ");

            if (!$item->check_enabled) {
                $this->stdout('DISABLED');
                $disabled++;
            } elseif (!$item->lastCheck) {
                $this->stdout('ENABLED (Never checked)');
                $enabled++;
            } elseif ($item->lastCheck->check_status === '200') {
                $this->stdout("UP ({$item->lastCheck->check_status})");
                $enabled++;
            } else {
                $this->stdout("DOWN ({$item->lastCheck->check_status})");
                $enabled++;
            }

            $this->stdout("\n");
        }

        $this->stdout("\n📊 Summary:\n");
        $this->stdout('  Total sites: ' . count($allItems) . "\n");
        $this->stdout("  Monitoring enabled: {$enabled}\n");
        $this->stdout("  Monitoring disabled: {$disabled}\n");

        if ($enabled > 0) {
            $this->stdout("\n💡 Commands:\n");
            $this->stdout("  Disable all: php yii queue/disable-all-monitoring\n");
            $this->stdout("  Run tick now: php yii monitoring/tick\n");
        } else {
            $this->stdout("\n💡 Commands:\n");
            $this->stdout("  Enable all: php yii queue/enable-all-monitoring\n");
        }
    }

    /**
     * Get check status priority for sorting.
     * Lower number = higher priority (shown first).
     *
     * @param \common\models\Item $item
     * @return int
     */
    private function getCheckStatusPriority($item)
    {
        // If monitoring is disabled, lowest priority
        if (!$item->check_enabled) {
            return 4;
        }

        // If no check data, medium priority
        if (!$item->lastCheck) {
            return 3;
        }

        // If site is down, highest priority
        if ($item->lastCheck->check_status !== '200') {
            return 1;
        }

        // If site is up, second priority
        return 2;
    }
}
