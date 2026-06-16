<?php

namespace console\controllers;

use common\components\check\WorkerCheck;
use common\models\Item;
use Yii;
use yii\console\Controller;

/**
 * Контроллер для управления мониторингом сайтов
 */
class MonitoringController extends Controller
{
    /**
     * Планировщик проверок (запускается по крону каждую минуту)
     *
     * Выбирает сайты, у которых подошло время проверки, и ставит задачи в очередь
     */
    public function actionTick(): int
    {
        $now = date('Y-m-d H:i:s');
        Yii::info("Запуск планировщика мониторинга. Текущее время: $now", 'monitoring');

        $items = Item::find()
            ->where([
                'check_enabled' => 1,
                'is_archived' => 0,
                'publish_status' => Item::STATUS_PUBLISH,
            ])
            ->andWhere([
                'or',
                ['<=', 'next_check_at', $now],
                ['next_check_at' => null],
            ])
            ->all();

        $scheduled = 0;

        foreach ($items as $item) {
            $url = $item->protocol . '://' . $item->domain;

            $job = new WorkerCheck([
                'item_id' => $item->id,
                'url' => $url,
            ]);

            try {
                Yii::$app->queue->push($job);
                Yii::info("Запланирована проверка для сайта: {$item->domain} (ID: {$item->id})", 'monitoring');
            } catch (\Exception $e) {
                Yii::error("Ошибка постановки в очередь для {$item->domain}: " . $e->getMessage(), 'monitoring');
                continue;
            }

            // Вычисляет время следующей проверки
            $nextCheckTime = date('Y-m-d H:i:s', strtotime("+{$item->check_interval} minutes"));
            $item->updateAttributes(['next_check_at' => $nextCheckTime]);

            $scheduled++;
            $this->stdout("✓ Запланирована проверка для {$item->domain} (следующая: {$nextCheckTime})\n");
        }

        if ($scheduled === 0) {
            Yii::info('Нет сайтов для проверки в данный момент.', 'monitoring');
            $this->stdout("Нет сайтов для проверки в данный момент.\n");
        } else {
            Yii::info("Успешно запланировано проверок: {$scheduled}", 'monitoring');
            $this->stdout("\n✅ Запланировано проверок: {$scheduled}\n");
        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Формирует и отправляет ежедневный отчет по сайтам со стратегией 'summary'
     */
    public function actionDailyReport(): int
    {
        $this->stdout("Формирование ежедневного отчета...\n");

        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));

        // Находит все сайты со стратегией summary
        $items = Item::find()
            ->where([
                'notify_strategy' => Item::NOTIFY_SUMMARY,
                'is_archived' => 0,
                'publish_status' => Item::STATUS_PUBLISH,
            ])
            ->all();

        $failedSites = [];

        foreach ($items as $item) {
            $lastCheck = $item->lastCheck;

            if ($lastCheck && $lastCheck->check_date >= $yesterday && $lastCheck->check_status !== '200') {
                $failedSites[] = [
                    'domain' => $item->domain,
                    'status' => $lastCheck->check_status,
                    'error' => $lastCheck->error_message,
                    'time' => $lastCheck->check_date,
                ];
            }
        }

        if (count($failedSites) > 0) {
            $this->stdout('Обнаружено проблемных сайтов: ' . count($failedSites) . "\n");

            // Формирует сообщение для Telegram
            $message = "<b>📊 Ежедневный отчет мониторинга</b>\n\n";
            $message .= "<b>Проблемные сайты за последние 24 часа:</b>\n\n";

            foreach ($failedSites as $site) {
                $message .= "❌ <b>{$site['domain']}</b>\n";
                $message .= "   Статус: {$site['status']}\n";
                if ($site['error']) {
                    $message .= "   Ошибка: {$site['error']}\n";
                }
                $message .= "   Время: {$site['time']}\n\n";
            }

            // Отправляет отчет в Telegram
            $this->sendTelegramReport($message);

            $this->stdout("✅ Отчет отправлен в Telegram\n");
        } else {
            $this->stdout("✅ Все сайты работают нормально. Отчет не требуется.\n");
        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Отправляет отчет в Telegram
     */
    private function sendTelegramReport(string $message): bool
    {
        $botToken = Yii::$app->params['telegram_bot_token'] ?? null;
        $chatId = Yii::$app->params['telegram_chat_id'] ?? null;
        $messageThreadId = Yii::$app->params['message_thread_id'] ?? null;

        if (empty($botToken) || empty($chatId)) {
            Yii::warning('Telegram bot token or chat_id is not configured');
            return false;
        }

        $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        if (!empty($messageThreadId)) {
            $data['message_thread_id'] = $messageThreadId;
        }

        try {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram report: ' . $e->getMessage());
            return false;
        }
    }
}
