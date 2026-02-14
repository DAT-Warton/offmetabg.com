# 🔧 Setup Guide - OffMeta E-Commerce

This guide will help you set up the OffMeta e-commerce platform on your server.

## 📋 Prerequisites

Before you begin, ensure you have:
- PHP 8.1 or higher
- PostgreSQL 14 or higher
- Nginx or Apache web server
- SSH access to your server
- MailerSend account (for emails)
- Domain name (optional, can use IP for testing)

## 🗄️ Database Setup

### 1. Create PostgreSQL Database

```bash
# Connect to PostgreSQL
sudo -u postgres psql

# Create database and user
CREATE DATABASE offmetabg_db;
CREATE USER offmetabg_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE offmetabg_db TO offmetabg_user;
GRANT ALL ON SCHEMA public TO offmetabg_user;

# Exit PostgreSQL
\q
```

### 2. Run Database Migrations

```bash
# Navigate to project directory
cd /path/to/offmetabg

# Run schema migration
psql -U offmetabg_user -d offmetabg_db -f migrations/postgresql-schema.sql
```

## ⚙️ Configuration Files

### 1. Database Configuration

Copy the example file and configure:

```bash
cp config/database.json.example config/database.json
```

Edit `config/database.json`:
```json
{
    "host": "localhost",
    "port": 5432,
    "database": "offmetabg_db",
    "username": "offmetabg_user",
    "password": "your_secure_password"
}
```

### 2. Email Configuration

Copy the example file and configure:

```bash
cp config/email-config.example.php config/email-config.php
```

Edit `config/email-config.php`:
- Add your MailerSend API token
- Configure sender email (must be verified)
- Set your site URL

### 3. Courier Configuration (Optional)

Edit `config/courier-config.php`:
- Add Econt credentials (if using)
- Add Speedy credentials (if using)

## 🌐 Web Server Configuration

### Nginx Configuration

Create a server block file:

```bash
sudo nano /etc/nginx/sites-available/offmetabg
```

Basic configuration:
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/offmetabg;
    index index.php;

    location / {
        try_files $uri $uri/ /router.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/offmetabg /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 📁 File Permissions

Set proper permissions:

```bash
# Web root
sudo chown -R www-data:www-data /var/www/offmetabg

# Storage directories (read/write)
sudo chmod -R 755 storage/
sudo chmod -R 755 uploads/
sudo chmod -R 755 cache/
sudo chmod -R 755 logs/

# Configuration files (read only)
sudo chmod 644 config/*.php
sudo chmod 644 config/*.json
```

## 🔒 Security Setup

### 1. SSL Certificate (HTTPS)

Using Certbot (Let's Encrypt):

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 2. Firewall Configuration

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 3. Secure Configuration Files

Ensure sensitive files are not accessible:
- Add `.htaccess` rules (Apache)
- Block access to `/config/`, `/storage/`, `/logs/` in Nginx

## 👤 Admin Account Setup

### Create Initial Admin Account

Use the install script or manually via database:

```bash
php install.php
```

Or via PostgreSQL:

```sql
INSERT INTO admins (username, email, password, created_at)
VALUES (
    'admin',
    'admin@yourdomain.com',
    '$2y$10$...',  -- Use password_hash('YourPassword', PASSWORD_DEFAULT)
    NOW()
);
```

## 📧 Email Testing

Test email configuration:

```bash
php config/test-email.php
```

Or via admin panel:
- Navigate to Settings → Email
- Click "Send Test Email"

## 🚀 Production Checklist

Before going live, ensure:

- [ ] SSL certificate installed (HTTPS)
- [ ] All configuration files secured
- [ ] Strong admin passwords set
- [ ] Email service configured and tested
- [ ] Database backups configured
- [ ] Error logging enabled
- [ ] Debug mode disabled
- [ ] File upload limits configured
- [ ] CSRF protection enabled
- [ ] Session security configured

## 🔄 Regular Maintenance

### Database Backups

Create automated backups:

```bash
# Backup script
pg_dump -U offmetabg_user offmetabg_db > backup_$(date +%Y%m%d).sql

# Add to crontab for daily backups
0 2 * * * pg_dump -U offmetabg_user offmetabg_db > /backups/db_$(date +\%Y\%m\%d).sql
```

### Update Dependencies

```bash
# Update PHP packages
composer update

# Check for security updates
apt update && apt upgrade
```

## 🐛 Troubleshooting

### Database Connection Issues

1. Check PostgreSQL is running:
   ```bash
   sudo systemctl status postgresql
   ```

2. Verify credentials in `config/database.json`

3. Check PostgreSQL logs:
   ```bash
   sudo tail -f /var/log/postgresql/postgresql-14-main.log
   ```

### Email Not Sending

1. Verify MailerSend API token
2. Check sender email is verified
3. Review error logs in `logs/`
4. Test with `config/test-email.php`

### Permission Errors

```bash
# Reset permissions
sudo chown -R www-data:www-data /var/www/offmetabg
sudo chmod -R 755 storage/ uploads/ cache/ logs/
```

## 📚 Additional Resources

- PHP Documentation: https://www.php.net/docs.php
- PostgreSQL Documentation: https://www.postgresql.org/docs/
- Nginx Documentation: https://nginx.org/en/docs/
- MailerSend Documentation: https://www.mailersend.com/help

## 🆘 Support

For issues or questions:
- Check the documentation
- Review error logs
- Check configuration files
- Verify server requirements

---

**Note:** This is proprietary software. See LICENSE file for usage terms.
