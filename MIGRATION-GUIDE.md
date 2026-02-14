# 🔄 Database Migration & Environment Setup Guide

**Date:** 2026-02-14  
**Status:** ✅ Ready for Production

---

## 📋 Overview

This guide covers:
1. Setting up environment variables (.env)
2. Running PostgreSQL migrations
3. Configuring site settings via admin panel
4. Removing hardcoded credentials from code

---

## 🔐 Step 1: Environment Variables Setup

### Create .env File

```bash
# Copy the example file
cp .env.example .env

# Edit with your credentials (NEVER commit .env to git!)
nano .env
```

### Required Environment Variables

**Database (Priority #1):**
```env
DB_TYPE=postgresql
DB_HOST=your-database-host.render.com
DB_PORT=5432
DB_NAME=offmetabg_db
DB_USER=offmetabg_user
DB_PASSWORD=your_secure_password

# Or use DATABASE_URL (Render/Heroku style)
DATABASE_URL=postgresql://user:password@host:5432/database
```

**Email Configuration:**
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@offmetabg.com
MAIL_FROM_NAME="OffMeta BG"
```

**Cloudflare (for cache purging):**
```env
CLOUDFLARE_API_KEY=your_api_key_here
CLOUDFLARE_ZONE_ID=your_zone_id_here
```

**Application:**
```env
APP_ENV=production
APP_URL=https://offmetabg.com
APP_KEY=generate_a_32_char_random_string_here
```

---

## 🗄️ Step 2: PostgreSQL Database Migration

### A. Create site_settings Table

Run the updated schema:

```bash
# SSH into your server
ssh user@your-server.com

# Run the migration
psql -h your-db-host -U your-db-user -d your-db-name -f migrations/postgresql-schema.sql
```

Or through Render dashboard:
1. Go to your PostgreSQL database
2. Click "Connect" → "PSQL Command"
3. Copy content from `migrations/postgresql-schema.sql`
4. Paste and execute

### B. Insert Default Settings

```bash
psql -h your-db-host -U your-db-user -d your-db-name -f migrations/insert-default-site-settings.sql
```

This creates ~60 default settings across 9 categories:
- ✅ General (site name, contact info)
- ✅ Email (SMTP settings)
- ✅ Social Media (URLs)
- ✅ API Keys (encrypted storage)
- ✅ Appearance (theme, logo)
- ✅ Commerce (currency, payments)
- ✅ SEO (meta tags)
- ✅ Footer (copyright, links)
- ✅ Maintenance (mode, cache)

---

## ⚙️ Step 3: Configure Settings via Admin Panel

### Access Admin Settings

1. Login to admin panel: `https://offmetabg.com/admin`
2. Navigate to **Settings** section
3. You'll see tabbed interface with all settings

### Settings Categories

#### 📌 General Settings
- Site Name: `OffMeta BG`
- Tagline: `Your Premium Digital Products Store`
- Contact Email: `contact@offmetabg.com`
- Support Email: `support@offmetabg.com`

#### 📧 Email Settings
- SMTP Host, Port, Username, Password
- From Email/Name
- Admin notification email

#### 🌐 Social Media
- Facebook, Instagram, Twitter URLs
- Discord, Telegram invite links

#### 🔑 API Keys (Encrypted)
- Cloudflare API Key & Zone ID
- Google Analytics ID
- reCAPTCHA keys
- Payment gateway keys

#### 🎨 Appearance
- Logo URL
- Default theme
- Products per page
- Enable dark mode

#### 💰 Commerce
- Currency: BGN
- Currency Symbol: лв
- Tax Rate: 20%
- Free shipping threshold
- Payment methods (JSON)

---

## 🔄 Step 4: Migration Checklist

### Files Updated ✅

**CSS:**
- ✅ `assets/css/home.css` - All 80+ hardcoded colors replaced with CSS variables

**PHP Configuration:**
- ✅ `includes/database.php` - Now uses .env for credentials
- ✅ `config/email-config.php` - Loads from environment variables
- ✅ `includes/env-loader.php` - New environment variable loader
- ✅ `includes/site-settings.php` - Database settings helper functions

**Admin Panel:**
- ✅ `admin/sections/settings.php` - Complete rewrite with tabbed UI

**Database:**
- ✅ `migrations/postgresql-schema.sql` - Added site_settings table
- ✅ `migrations/insert-default-site-settings.sql` - Default values

**Documentation:**
- ✅ `.env.example` - Environment template
- ✅ This migration guide

---

## 🚀 Step 5: Deployment Process

### Local Development

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Configure your local database
# Edit .env with local PostgreSQL credentials

# 3. Run migrations
php migrations/run-migrations.php

# 4. Start local server
php -S localhost:8000 router.php
```

### Production Deployment (Render/VPS)

```bash
# 1. Set environment variables in Render dashboard
#    Or upload .env file to VPS (chmod 600 .env)

# 2. SSH and run migrations
ssh user@server
cd /var/www/offmetabg
psql $DATABASE_URL -f migrations/postgresql-schema.sql
psql $DATABASE_URL -f migrations/insert-default-site-settings.sql

# 3. Verify .env is NOT in git
echo ".env" >> .gitignore
git rm --cached .env  # If accidentally committed

# 4. Deploy
git add .
git commit -m "Migrated to database-driven settings with env variables"
git push origin main
```

### Cloudflare Configuration

Update your deployment script to use environment variables:

```powershell
# deploy-config.local.ps1
$CLOUDFLARE_API_KEY = $env:CLOUDFLARE_API_KEY
$CLOUDFLARE_ZONE_ID = $env:CLOUDFLARE_ZONE_ID
```

---

## 🧪 Step 6: Testing

### Verify Environment Variables

Create test file: `test-env.php`
```php
<?php
require 'includes/env-loader.php';

echo "DB Host: " . env('DB_HOST') . "\n";
echo "DB Name: " . env('DB_NAME') . "\n";
echo "Mail Host: " . env('MAIL_HOST') . "\n";
echo "Cloudflare Zone: " . env('CLOUDFLARE_ZONE_ID') . "\n";
```

Delete after testing!

### Verify Database Settings

```php
<?php
require 'includes/site-settings.php';

$siteName = get_site_setting('site_name', 'default', 'general');
echo "Site Name: " . $siteName;

$currency = get_site_setting('currency', 'BGN', 'commerce');
echo "Currency: " . $currency;
```

### Verify Admin UI

1. Login to admin panel
2. Go to Settings
3. Test editing a setting
4. Save and verify database update

---

## 🔒 Security Best Practices

### ✅ DO:
- ✅ Keep .env file outside web root or chmod 600
- ✅ Add .env to .gitignore
- ✅ Use strong passwords (32+ characters)
- ✅ Rotate API keys regularly
- ✅ Enable HTTPS everywhere
- ✅ Use encrypted connections to database

### ❌ DON'T:
- ❌ Commit .env to version control
- ❌ Share credentials in Slack/Discord
- ❌ Use same password across services
- ❌ Hardcode credentials in PHP files
- ❌ Expose .env through web server

---

## 📊 Database Schema: site_settings

```sql
CREATE TABLE site_settings (
    id SERIAL PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'text',
    is_encrypted BOOLEAN DEFAULT false,
    is_public BOOLEAN DEFAULT true,
    label VARCHAR(255),
    description TEXT,
    default_value TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(category, setting_key)
);
```

### Setting Types:
- `text` - String values
- `number` - Numeric values
- `boolean` - true/false
- `json` - JSON objects/arrays
- `email` - Email addresses
- `url` - URLs
- `password` - Encrypted values

---

## 🛠️ Helper Functions

### Get Settings in PHP

```php
// Get single setting
$siteName = get_site_setting('site_name', 'OffMeta BG', 'general');

// Get all settings in category
$emailSettings = get_settings_by_category('email');

// Get all public settings (for frontend)
$publicSettings = get_public_settings();

// Update setting
set_site_setting('site_name', 'New Name', 'general');

// Check feature flag
if (is_feature_enabled('enable_dark_mode', 'appearance')) {
    // Dark mode is enabled
}
```

### Using in Templates

```php
<?php
$settings = get_public_settings();
$siteName = $settings['general']['site_name'] ?? 'OffMeta BG';
$currency = $settings['commerce']['currency_symbol'] ?? 'лв';
?>

<h1><?= htmlspecialchars($siteName) ?></h1>
<span><?= htmlspecialchars($currency) ?></span>
```

---

## 🐛 Troubleshooting

### .env file not loading
- Check file exists: `ls -la .env`
- Verify permissions: `chmod 600 .env`
- Check PHP has read access

### Database connection fails
- Verify DATABASE_URL format
- Test connection: `psql $DATABASE_URL`
- Check firewall rules

### Settings not appearing in admin
- Verify migrations ran: `SELECT COUNT(*) FROM site_settings;`
- Check error logs: `tail -f logs/error.log`
- Clear cache: `rm -rf cache/*`

### Changes not saving
- Check form submission in browser dev tools
- Verify PDO update statement
- Check database user permissions

---

## 📞 Support

- **Documentation:** See `HARDCODED-ANALYSIS.md`
- **Issues:** Check `logs/error.log`
- **Database:** View `migrations/postgresql-schema.sql`

---

## ✅ Migration Complete!

**Before Migration:**
- ❌ 80+ hardcoded colors in CSS
- ❌ Credentials in config files
- ❌ No centralized settings management
- ❌ Manual edits required for changes

**After Migration:**
- ✅ All colors use CSS variables
- ✅ Credentials in .env (secure)
- ✅ Database-driven settings
- ✅ Beautiful admin UI for configuration
- ✅ 60+ configurable settings
- ✅ Encrypted sensitive data
- ✅ Public/private setting flags
- ✅ Easy to maintain and update

🎉 **Your site is now fully production-ready!**
