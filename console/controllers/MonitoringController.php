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

        $items = Item::find()
            ->where(['check_enabled' => 1])
            ->andWhere(['<=', 'next_check_at', $now])
            ->orWhere(['next_check_at' => null])
            ->andWhere(['check_enabled' => 1])
            ->all();

        $scheduled = 0;

        foreach ($items as $item) {
            $url = $item->protocol . '://' . $item->domain;

            $job = new WorkerCheck([
                'item_id' => $item->id,
                'url' => $url,
            ]);

            Yii::$app->queue->push($job);

            // Вычисляет время следующей проверки
            $nextCheckTime = date('Y-m-d H:i:s', strtotime("+{$item->check_interval} minutes"));
            $item->updateAttributes(['next_check_at' => $nextCheckTime]);

            $scheduled++;
            $this->stdout("✓ Запланирована проверка для {$item->domain} (следующая: {$nextCheckTime})\n");
        }

        if ($scheduled === 0) {
            $this->stdout("Нет сайтов для проверки в данный момент.\n");
        } else {
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
            ->where(['notify_strategy' => Item::NOTIFY_SUMMARY])
            ->with([
                'lastCheck' => function ($query) use ($yesterday) {
                    $query->where(['>=', 'check_date', $yesterday]);
                }
            ])
            ->all();

        $failedSites = [];

        foreach ($items as $item) {
            if ($item->lastCheck && $item->lastCheck->check_status !== '200') {
                $failedSites[] = [
                    'domain' => $item->domain,
                    'status' => $item->lastCheck->check_status,
                    'error' => $item->lastCheck->error_message,
                    'time' => $item->lastCheck->check_date,
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
