#!/bin/bash
# Backup sensitive runtime files before pulling new changes on VPS
# Usage: run from /var/www/offmetabg on the VPS before git pull
set -e
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/var/www/offmetabg/backups/config-backup-$TIMESTAMP"
mkdir -p "$BACKUP_DIR"
cp -a .env "$BACKUP_DIR/" 2>/dev/null || true
cp -a config/email-config.php "$BACKUP_DIR/" 2>/dev/null || true
cp -a config/courier-config.php "$BACKUP_DIR/" 2>/dev/null || true
cp -a config/database.json "$BACKUP_DIR/" 2>/dev/null || true
echo "Backed up runtime config to $BACKUP_DIR"
