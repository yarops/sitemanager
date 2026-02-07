#!/bin/bash

# Настройка планировщика (Cron) для мониторинга сайтов
# Этот скрипт настраивает ежеминутный цикл: планирование + выполнение

# Определяем корневую директорию проекта (на уровень выше от res/)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Формируем запись для Cron: запуск каждую минуту
# 1. monitoring/tick - планирует задачи в очередь
# 2. queue/run - выполняет задачи из очереди
CRON_ENTRY_1="* * * * * cd $PROJECT_DIR && php yii monitoring/tick && php yii queue/run >> $PROJECT_DIR/runtime/logs/monitoring.log 2>&1"

# 3. monitoring/daily-report - отправляет сводный отчет в 9:00 утра
CRON_ENTRY_2="0 9 * * * cd $PROJECT_DIR && php yii monitoring/daily-report >> $PROJECT_DIR/runtime/logs/daily.log 2>&1"

# Добавление в crontab (с удалением старых записей мониторинга)
(crontab -l 2>/dev/null | grep -v "monitoring" | grep -v "site-monitor"; echo "$CRON_ENTRY_1"; echo "$CRON_ENTRY_2") | crontab -

echo "✅ Планировщик мониторинга успешно настроен!"
echo "1. Основной цикл (Tick + Run) будет запускаться КАЖДУЮ МИНУТУ."
echo "2. Сводный отчет (Daily Report) будет отправляться КАЖДЫЙ ДЕНЬ В 09:00."
echo ""
echo "Логи работы:"
echo "  - $PROJECT_DIR/runtime/logs/monitoring.log"
echo ""
echo "Полезные команды:"
echo "  - Посмотреть очередь: php yii queue/info"
echo "  - Ручной запуск: php yii monitoring/tick && php yii queue/run"
echo "  - Логи в реальном времени: tail -f $PROJECT_DIR/runtime/logs/monitoring.log"
