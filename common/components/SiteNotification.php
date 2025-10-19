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

        return $this->sendEmail($subject, $html);
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
        $subject = "📊 Ежедневный отчет мониторинга сайтов";
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
            Yii::error("Failed to send notification: " . $e->getMessage());
            return false;
        }
    }
}
