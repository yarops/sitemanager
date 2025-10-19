#!/bin/bash

# Setup cron job for site monitoring
# This script sets up automatic site monitoring once per day

# Get the current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Create cron job entries
# 1. Setup daily monitoring at 6:00 AM
# 2. Start queue worker (if not running) at 6:01 AM
CRON_ENTRY_1="0 6 * * * cd $SCRIPT_DIR && php yii queue/setup-daily-monitoring >> $SCRIPT_DIR/runtime/logs/site-monitor.log 2>&1"
CRON_ENTRY_2="1 6 * * * cd $SCRIPT_DIR && pgrep -f 'php.*queue/run' > /dev/null || (nohup php yii queue/run >> $SCRIPT_DIR/runtime/logs/queue-worker.log 2>&1 &)"

# Add to crontab if not already exists
(crontab -l 2>/dev/null | grep -v "site-monitor"; echo "$CRON_ENTRY_1"; echo "$CRON_ENTRY_2") | crontab -

echo "Cron jobs added successfully!"
echo "1. Site monitoring will be scheduled once per day at 6:00 AM"
echo "2. Queue worker will be started automatically at 6:01 AM (if not running)"
echo ""
echo "Logs:"
echo "  - Site monitoring: $SCRIPT_DIR/runtime/logs/site-monitor.log"
echo "  - Queue worker: $SCRIPT_DIR/runtime/logs/queue-worker.log"
echo ""
echo "Manual commands:"
echo "  - Start worker: php yii queue/run"
echo "  - Check status: php yii queue/info"
echo "  - View logs: tail -f $SCRIPT_DIR/runtime/logs/queue-worker.log"
echo "  - Remove cron: crontab -e"
