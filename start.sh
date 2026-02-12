#!/bin/sh
# Startup script for Render deployment
# Runs auto-migration then starts web server

echo "Starting OffMeta E-commerce..."

# Run auto-migration if DATABASE_URL is set
if [ -n "$DATABASE_URL" ]; then
    echo "Running auto-migration check..."
    php migrations/auto-migrate.php
fi

# Start PHP web server
echo "Starting web server on port ${PORT:-10000}..."
php -S 0.0.0.0:${PORT:-10000}
