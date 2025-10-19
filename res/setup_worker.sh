#!/bin/bash

# Setup queue worker as systemd service
# This script creates a systemd service to run the queue worker automatically

# Get the current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
USER=$(whoami)

# Create systemd service file
SERVICE_FILE="/etc/systemd/system/sitemanager-queue.service"

echo "Creating systemd service for queue worker..."

sudo tee $SERVICE_FILE > /dev/null <<EOF
[Unit]
Description=SiteManager Queue Worker
After=network.target redis.service

[Service]
Type=simple
User=$USER
WorkingDirectory=$SCRIPT_DIR
ExecStart=/usr/bin/php yii queue/run
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd and enable service
sudo systemctl daemon-reload
sudo systemctl enable sitemanager-queue.service

echo "✅ Systemd service created and enabled!"
echo ""
echo "To start the service:"
echo "  sudo systemctl start sitemanager-queue"
echo ""
echo "To check status:"
echo "  sudo systemctl status sitemanager-queue"
echo ""
echo "To view logs:"
echo "  sudo journalctl -u sitemanager-queue -f"
echo ""
echo "To stop the service:"
echo "  sudo systemctl stop sitemanager-queue"
