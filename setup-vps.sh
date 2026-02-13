#!/bin/bash
# VPS Setup Script - Run this after git pull
# Sets up configuration files and permissions
#
# ⚠️  IMPORTANT: Edit the Configuration section below before running!

set -e

echo "🚀 Setting up project on VPS..."
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run as root or with sudo"
    exit 1
fi

# Configuration - CHANGE THESE VALUES!
WEB_ROOT="/var/www/your-project"
DB_NAME="your_database"
DB_USER="your_db_user"
DB_PASS="your_secure_password"

cd "$WEB_ROOT"

# Create config files from templates if they don't exist
echo "📝 Creating configuration files..."

if [ ! -f "config/database.json" ]; then
    echo "   Creating config/database.json..."
    cat > config/database.json << EOF
{
    "driver": "pgsql",
    "host": "localhost",
    "port": 5432,
    "database": "$DB_NAME",
    "user": "$DB_USER",
    "password": "$DB_PASS"
}
EOF
fi

if [ ! -f "config/email-config.php" ]; then
    echo "   Creating config/email-config.php..."
    echo "   ⚠️  You need to update config/email-config.php with your MailerSend API token!"
    cp config/email-config.example.php config/email-config.php
fi

# Create necessary directories
echo ""
echo "📁 Creating directories..."
mkdir -p storage cache logs uploads
mkdir -p uploads/products uploads/pages uploads/posts

# Set proper permissions
echo ""
echo "🔒 Setting permissions..."
chown -R www-data:www-data "$WEB_ROOT"
chmod -R 755 "$WEB_ROOT"
chmod -R 777 storage cache logs uploads

# Initialize database schema if needed
echo ""
echo "🗄️  Checking database..."
if sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
    echo "   Database $DB_NAME exists"
else
    echo "   Creating database..."
    sudo -u postgres psql -c "CREATE DATABASE $DB_NAME;"
    sudo -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';"
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;"
    sudo -u postgres psql -d "$DB_NAME" -c "GRANT ALL ON SCHEMA public TO $DB_USER;"
    
    # Import schema if it exists
    if [ -f "migrations/postgresql-schema.sql" ]; then
        echo "   Importing database schema..."
        sudo -u postgres psql -d "$DB_NAME" -f migrations/postgresql-schema.sql
    fi
fi

# Nginx configuration
echo ""
echo "🌐 Configuring Nginx..."
SITE_NAME=$(basename "$WEB_ROOT")
cat > /etc/nginx/sites-available/$SITE_NAME << NGINX_EOF
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;  # CHANGE THIS!
    root $WEB_ROOT;
    index index.php index.html;
    
    # Logging
    access_log /var/log/nginx/${SITE_NAME}-access.log;
    error_log /var/log/nginx/${SITE_NAME}-error.log;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINX_EOF

# Enable site
ln -sf /etc/nginx/sites-available/$SITE_NAME /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test nginx config
nginx -t

# Reload nginx
systemctl reload nginx

echo ""
echo "✅ Setup complete!"
echo ""
echo "📌 Next steps:"
echo "   1. Edit /etc/nginx/sites-available/$SITE_NAME and set your domain"
echo "   2. Edit config/email-config.php with your MailerSend API token"
echo "   3. Setup SSL: certbot --nginx -d yourdomain.com -d www.yourdomain.com"
echo "   4. Visit your domain"
echo ""
