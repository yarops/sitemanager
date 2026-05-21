<?php

namespace common\components;

use common\models\Item;
use common\models\Check;
use Yii;
use yii\base\Component;

/**
 * Site notification component for sending alerts when sites are down.
 */
class SiteNotification extends Component
{
    /**
     * Send notification when site goes down.
     *
     * @param Item $item
     * @param Check $check
     * @return bool
     */
    public function sendDownAlert(Item $item, Check $check): bool
    {
        $url = $item->protocol . '://' . $item->domain;

        $subject = "Сайт недоступен: {$item->domain}";
        $html = $this->renderDownAlertTemplate($item, $check, $url);

        $emailSent = $this->sendEmail($subject, $html);
        $telegramSent = $this->sendTelegramNotification($item, $check);

        return $emailSent || $telegramSent;
    }

    /**
     * Send notification when site comes back up.
     *
     * @param Item $item
     * @param Check $check
     * @return bool
     */
    public function sendUpAlert(Item $item, Check $check): bool
    {
        $url = $item->protocol . '://' . $item->domain;

        $subject = "✅ Сайт восстановлен: {$item->domain}";
        $html = $this->renderUpAlertTemplate($item, $check, $url);

        return $this->sendEmail($subject, $html);
    }

    /**
     * Send daily monitoring report.
     *
     * @param array $stats
     * @return bool
     */
    public function sendDailyReport(array $stats): bool
    {
        $subject = '📊 Ежедневный отчет мониторинга сайтов';
        $html = $this->renderDailyReportTemplate($stats);

        return $this->sendEmail($subject, $html);
    }

    /**
     * Render down alert template.
     *
     * @param Item $item
     * @param Check $check
     * @param string $url
     * @return string
     */
    private function renderDownAlertTemplate(Item $item, Check $check, string $url): string
    {
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #dc3545; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Сайт недоступен!</h2>
                </div>

                <div style='padding: 20px; background-color: #f8f9fa;'>
                    <h3>Детали инцидента:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Сайт:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$item->domain}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>URL:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'><a href='{$url}'>{$url}</a></td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Статус:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; color: #dc3545; font-weight: bold;'>{$check->check_status}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Время отклика:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$check->response_time}ms</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Ошибка:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$check->error_message}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Время:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$check->check_date}</td>
                        </tr>
                    </table>
                </div>

                <div style='padding: 20px; background-color: #e9ecef; text-align: center;'>
                    <p style='margin: 0; color: #6c757d; font-size: 14px;'>
                        Это автоматическое уведомление от системы мониторинга сайтов.
                    </p>
                </div>
            </div>
        ";
    }

    /**
     * Render up alert template.
     *
     * @param Item $item
     * @param Check $check
     * @param string $url
     * @return string
     */
    private function renderUpAlertTemplate(Item $item, Check $check, string $url): string
    {
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #28a745; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Сайт восстановлен!</h2>
                </div>

                <div style='padding: 20px; background-color: #f8f9fa;'>
                    <h3>Детали восстановления:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Сайт:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$item->domain}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>URL:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'><a href='{$url}'>{$url}</a></td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Статус:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; color: #28a745; font-weight: bold;'>{$check->check_status}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Время отклика:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$check->response_time}ms</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Время:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$check->check_date}</td>
                        </tr>
                    </table>
                </div>

                <div style='padding: 20px; background-color: #e9ecef; text-align: center;'>
                    <p style='margin: 0; color: #6c757d; font-size: 14px;'>
                        Это автоматическое уведомление от системы мониторинга сайтов.
                    </p>
                </div>
            </div>
        ";
    }

    /**
     * Render daily report template.
     *
     * @param array $stats
     * @return string
     */
    private function renderDailyReportTemplate(array $stats): string
    {
        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #007bff; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Ежедневный отчет мониторинга</h2>
                </div>

                <div style='padding: 20px; background-color: #f8f9fa;'>
                    <h3>Статистика за " . date('d.m.Y') . ":</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Всего проверок:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$stats['total_checks']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Успешных:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; color: #28a745;'>{$stats['successful_checks']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Неудачных:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; color: #dc3545;'>{$stats['failed_checks']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;'>Среднее время отклика:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$stats['avg_response_time']}ms</td>
                        </tr>
                    </table>
                </div>

                <div style='padding: 20px; background-color: #e9ecef; text-align: center;'>
                    <p style='margin: 0; color: #6c757d; font-size: 14px;'>
                        Это автоматический отчет от системы мониторинга сайтов.
                    </p>
                </div>
            </div>
        ";

        return $html;
    }

    /**
     * Send email notification.
     *
     * @param string $subject
     * @param string $html
     * @return bool
     */
    private function sendEmail(string $subject, string $html): bool
    {
        try {
            return Yii::$app->mailer->compose()
                ->setFrom('monitor@devgamescom.local')
                ->setTo('admin@devgamescom.local')
                ->setSubject($subject)
                ->setHtmlBody($html)
                ->send();
        } catch (\Exception $e) {
            Yii::error('Failed to send notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Telegram notification when site check fails.
     *
     * @param Item $item
     * @param Check $check
     * @return bool
     */
    public function sendTelegramNotification(Item $item, Check $check): bool
    {
        $botToken = Yii::$app->params['telegram_bot_token'] ?? null;
        $chatId = Yii::$app->params['telegram_chat_id'] ?? null;
        $messageThreadId = Yii::$app->params['message_thread_id'] ?? null;

        if (empty($botToken) || empty($chatId)) {
            Yii::warning('Telegram bot token or chat_id is not configured');
            return false;
        }

        $url = $item->protocol . '://' . $item->domain;
        $message = $this->formatTelegramMessage($item, $check, $url);

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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200) {
                Yii::error("Telegram API error: HTTP {$httpCode}, Response: {$response}");
                return false;
            }

            if (!empty($curlError)) {
                Yii::error("Telegram cURL error: {$curlError}");
                return false;
            }

            $result = json_decode($response, true);
            if (isset($result['ok']) && $result['ok'] === true) {
                Yii::info("Telegram notification sent successfully for {$item->domain}");
                return true;
            } else {
                Yii::error('Telegram API returned error: ' . json_encode($result));
                return false;
            }
        } catch (\Exception $e) {
            Yii::error('Failed to send Telegram notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format message for Telegram.
     *
     * @param Item $item
     * @param Check $check
     * @param string $url
     * @return string
     */
    private function formatTelegramMessage(Item $item, Check $check, string $url): string
    {
        $status = $check->check_status;
        $emoji = $status === '200' ? '✅' : '❌';
        $title = $status === '200' ? 'Сайт восстановлен' : 'Сайт недоступен';

        $message = "<b>{$emoji} {$title}</b>\n\n";
        $message .= "<b>Сайт:</b> {$item->domain}\n";
        $message .= "<b>URL:</b> <a href=\"{$url}\">{$url}</a>\n";
        $message .= "<b>Статус:</b> {$check->check_status}\n";

        if ($check->response_time !== null) {
            $message .= "<b>Время отклика:</b> {$check->response_time}ms\n";
        }

        if ($check->error_message) {
            $message .= '<b>Ошибка:</b> ' . htmlspecialchars($check->error_message) . "\n";
        }

        $message .= "<b>Время проверки:</b> {$check->check_date}\n";

        return $message;
    }
}
