#!/bin/bash

# EdQuill CI4 Cron Jobs Setup Script
# This script sets up the cron jobs for the migrated CI4 application

echo "Setting up EdQuill CI4 Cron Jobs..."

# Get the current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_PATH=$(which php)

# Check if PHP is available
if [ -z "$PHP_PATH" ]; then
    echo "Error: PHP not found in PATH"
    exit 1
fi

echo "Using PHP: $PHP_PATH"
echo "Script directory: $SCRIPT_DIR"

# Create cron jobs
echo "Creating cron jobs..."

# Database backup (daily at 2 AM)
echo "0 2 * * * cd $SCRIPT_DIR && $PHP_PATH spark db:backup --compress" | crontab -

# Email notifications (every 15 minutes)
echo "*/15 * * * * cd $SCRIPT_DIR && $PHP_PATH spark email:send-notifications" | crontab -

# Overdue content emails (daily at 9 AM)
echo "0 9 * * * cd $SCRIPT_DIR && $PHP_PATH spark content:overdue-email --days 1" | crontab -

# Student platform report (weekly on Monday at 6 AM)
echo "0 6 * * 1 cd $SCRIPT_DIR && $PHP_PATH spark reports:student-platform --date-from \$(date -d '7 days ago' +%Y-%m-%d) --date-to \$(date -d '1 day ago' +%Y-%m-%d)" | crontab -

# Day-wise report (daily at 11 PM)
echo "0 23 * * * cd $SCRIPT_DIR && $PHP_PATH spark reports:daywise --date-from \$(date -d '1 day ago' +%Y-%m-%d) --date-to \$(date -d '1 day ago' +%Y-%m-%d)" | crontab -

# Admin mail data insert (daily at 1 AM)
echo "0 1 * * * cd $SCRIPT_DIR && $PHP_PATH spark admin:mail-data-insert" | crontab -

# Admin mail sent (daily at 2 AM)
echo "0 2 * * * cd $SCRIPT_DIR && $PHP_PATH spark admin:mail-sent" | crontab -

# Below score report (daily at 3 AM)
echo "0 3 * * * cd $SCRIPT_DIR && $PHP_PATH spark reports:below-score" | crontab -

# Check space (daily at 4 AM)
echo "0 4 * * * cd $SCRIPT_DIR && $PHP_PATH spark system:check-space" | crontab -

# Future class shift (daily at 5 AM)
echo "0 5 * * * cd $SCRIPT_DIR && $PHP_PATH spark classes:future-shift" | crontab -

# EdQuill registration mail (daily at 6 AM)
echo "0 6 * * * cd $SCRIPT_DIR && $PHP_PATH spark registration:send-mail" | crontab -

# File path conversion (daily at 7 AM)
echo "0 7 * * * cd $SCRIPT_DIR && $PHP_PATH spark files:path-conversion" | crontab -

# Inbox cron (every 30 minutes)
echo "*/30 * * * * cd $SCRIPT_DIR && $PHP_PATH spark inbox:process" | crontab -

# Notify parents send email (daily at 8 AM)
echo "0 8 * * * cd $SCRIPT_DIR && $PHP_PATH spark parents:notify-email" | crontab -

# Upgrade students (daily at 10 AM)
echo "0 10 * * * cd $SCRIPT_DIR && $PHP_PATH spark students:upgrade" | crontab -

echo "Cron jobs have been set up successfully!"
echo ""
echo "To view current cron jobs, run: crontab -l"
echo "To remove all cron jobs, run: crontab -r"
echo ""
echo "Note: Make sure the following directories exist and are writable:"
echo "- $SCRIPT_DIR/writable/logs"
echo "- $SCRIPT_DIR/writable/backups"
echo "- $SCRIPT_DIR/writable/reports"
echo ""
echo "You can test individual commands manually:"
echo "cd $SCRIPT_DIR && $PHP_PATH spark db:backup --dry-run"
echo "cd $SCRIPT_DIR && $PHP_PATH spark email:send-notifications --dry-run"
