<?php
/**
 * Plugin Name: Health Check Monitor
 * Plugin URI: https://devgamescom.local
 * Description: Простой плагин для мониторинга состояния WordPress сайта через REST API.
 * Version: 1.0.0
 * Author: DevGamesCom
 * License: GPL v2 or later
 * Text Domain: health-check-monitor
 */

// Предотвращаем прямой доступ к файлу.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Основной класс плагина.
 */
class HealthCheckMonitor
{
    /**
     * Конструктор плагина.
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_health_endpoint']);
    }

    /**
     * Регистрирует REST API эндпоинт для проверки здоровья.
     */
    public function register_health_endpoint()
    {
        register_rest_route('custom/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check_callback'],
            'permission_callback' => '__return_true', // Публичный доступ для мониторинга.
        ]);
    }

    /**
     * Обработчик эндпоинта проверки здоровья.
     *
     * @param WP_REST_Request $request Объект запроса.
     * @return WP_REST_Response Ответ с результатами проверки.
     */
    public function health_check_callback($request)
    {
        $health_data = [
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'checks' => []
        ];

        // Проверка подключения к базе данных.
        $db_check = $this->check_database();
        $health_data['checks']['database'] = $db_check;

        // Проверка файловой системы.
        $fs_check = $this->check_filesystem();
        $health_data['checks']['filesystem'] = $fs_check;

        // Проверка памяти.
        $memory_check = $this->check_memory();
        $health_data['checks']['memory'] = $memory_check;

        // Проверка плагинов.
        $plugins_check = $this->check_plugins();
        $health_data['checks']['plugins'] = $plugins_check;

        // Определяем общий статус.
        $all_healthy = true;
        foreach ($health_data['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                $all_healthy = false;
                break;
            }
        }

        $health_data['status'] = $all_healthy ? 'healthy' : 'unhealthy';

        // Возвращаем соответствующий HTTP статус.
        $http_status = $all_healthy ? 200 : 503;

        return new WP_REST_Response($health_data, $http_status);
    }

    /**
     * Проверяет подключение к базе данных.
     *
     * @return array Результат проверки.
     */
    private function check_database()
    {
        global $wpdb;

        try {
            // Простой запрос для проверки подключения.
            $result = $wpdb->get_var("SELECT 1");
            
            if ($result === '1') {
                return [
                    'status' => 'ok',
                    'message' => 'Database connection successful'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Database query failed'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Проверяет файловую систему.
     *
     * @return array Результат проверки.
     */
    private function check_filesystem()
    {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'];

        if (!is_dir($upload_path)) {
            return [
                'status' => 'error',
                'message' => 'Upload directory does not exist'
            ];
        }

        if (!is_writable($upload_path)) {
            return [
                'status' => 'warning',
                'message' => 'Upload directory is not writable'
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'Filesystem is accessible'
        ];
    }

    /**
     * Проверяет использование памяти.
     *
     * @return array Результат проверки.
     */
    private function check_memory()
    {
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        
        // Конвертируем лимит памяти в байты.
        $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
        $usage_percent = ($memory_usage / $memory_limit_bytes) * 100;

        if ($usage_percent > 90) {
            return [
                'status' => 'warning',
                'message' => sprintf('High memory usage: %.1f%%', $usage_percent),
                'usage' => $this->format_bytes($memory_usage),
                'limit' => $memory_limit
            ];
        }

        return [
            'status' => 'ok',
            'message' => sprintf('Memory usage: %.1f%%', $usage_percent),
            'usage' => $this->format_bytes($memory_usage),
            'limit' => $memory_limit
        ];
    }

    /**
     * Проверяет состояние плагинов.
     *
     * @return array Результат проверки.
     */
    private function check_plugins()
    {
        $active_plugins = get_option('active_plugins', []);
        $plugin_errors = [];

        foreach ($active_plugins as $plugin) {
            if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                $plugin_errors[] = $plugin . ' (file not found)';
            }
        }

        if (!empty($plugin_errors)) {
            return [
                'status' => 'warning',
                'message' => 'Some plugins have issues',
                'errors' => $plugin_errors
            ];
        }

        return [
            'status' => 'ok',
            'message' => sprintf('%d plugins active', count($active_plugins))
        ];
    }

    /**
     * Конвертирует строку с размером памяти в байты.
     *
     * @param string $size Размер в формате строки (например, "128M").
     * @return int Размер в байтах.
     */
    private function convert_to_bytes($size)
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;

        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    /**
     * Форматирует размер в байтах в читаемый вид.
     *
     * @param int $bytes Размер в байтах.
     * @return string Отформатированный размер.
     */
    private function format_bytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Инициализируем плагин.
new HealthCheckMonitor();
