# VPS Deployment Steps - Theme CSS & Logo Management

## Date: 2024
## Changes: Theme CSS Variables + Logo Management Feature

---

## What Changed

### 1. **CSS Theme Variables**
- **File**: `assets/css/home.css`
- **Changes**: Replaced hardcoded `color: white` with `var(--text-primary)` in:
  - `.logo h1` - Logo text
  - `.section-title` - Section headings
  - `footer` - Footer text
- **Impact**: All text now responds to custom theme colors

### 2. **Logo Management System**
- **Files Modified**:
  - `admin/sections/settings.php` - Added image upload, logo positioning, size controls
  - `includes/functions.php` - Added `get_site_setting()` function
  - `templates/home.php` - Dynamic logo rendering
  - `assets/css/home.css` - Logo container styling with flexbox

- **Database Changes**: New logo-related settings in `site_settings` table:
  - `logo_position` - left/center/right alignment
  - `logo_max_height` - Maximum logo height in pixels
  - `logo_max_width` - Maximum logo width in pixels
  - `show_site_name` - Toggle site name display

---

## Deployment Instructions

### Step 1: Connect to VPS
```bash
ssh your-vps-user@your-vps-ip
cd /var/www/offmetabg  # or wherever your site is hosted
```

### Step 2: Pull Latest Changes
```bash
git pull origin master
```

Expected output:
```
Updating acd28ce..8f89ab5
Fast-forward
 admin/sections/settings.php      | 105 ++++++++++++++++++++++++++-
 assets/css/home.css               |  29 ++++++--
 includes/functions.php            |  30 ++++++++
 migrations/add-logo-settings.sql  |  22 ++++++
 templates/home.php                |  18 ++++-
 5 files changed, 188 insertions(+), 6 deletions(-)
 create mode 100644 migrations/add-logo-settings.sql
```

### Step 3: Run Database Migration
```bash
# Connect to PostgreSQL
psql -U offmetabg_user -d offmetabg_db

# Run the migration
\i /var/www/offmetabg/migrations/add-logo-settings.sql

# Verify settings were added
SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'logo%';

# Expected output:
# logo_url          | /assets/img/logo.png
# logo_position     | left
# logo_max_height   | 50
# logo_max_width    | 200

# Exit psql
\q
```

### Step 4: Create Uploads Directory (if not exists)
```bash
mkdir -p /var/www/offmetabg/uploads/settings
chmod 755 /var/www/offmetabg/uploads/settings
chown www-data:www-data /var/www/offmetabg/uploads/settings
```

### Step 5: Clear Cache (if applicable)
```bash
# If you have PHP OPcache
sudo service php8.1-fpm reload

# If using Cloudflare
# Purge cache via Cloudflare dashboard or API
```

### Step 6: Restart Nginx (if needed)
```bash
sudo nginx -t           # Test configuration
sudo systemctl reload nginx
```

---

## Verification Steps

### 1. Check Frontend
Open: `https://offmetabg.com`

**Expected:**
- Logo text uses theme colors (changes with theme)
- Section titles use theme colors
- Footer text uses theme colors
- All elements respond to custom theme switching

### 2. Check Admin Panel
Open: `https://offmetabg.com/admin/index.php?section=settings`

**Expected:**
- Navigate to **Appearance** tab
- See new fields:
  - Logo URL (with file upload and preview)
  - Logo Position (dropdown: left/center/right)
  - Logo Max Height (number input)
  - Logo Max Width (number input)
  - Show Site Name (checkbox)

### 3. Test Logo Upload
1. Go to Admin → Settings → Appearance
2. Click "Choose File" under Logo URL
3. Upload an image (PNG, JPG, SVG)
4. Click "Save All Settings"
5. Refresh homepage
6. Verify logo appears in header

### 4. Test Logo Positioning
1. Change "Logo Position" to "center"
2. Save settings
3. Refresh homepage
4. Verify logo is centered

### 5. Test Theme Switching
1. Go to Admin → Themes
2. Change to custom theme (e.g., "Custom Preview")
3. Check homepage
4. Verify:
   - Logo text color changes
   - Section titles change color
   - Footer text changes color

---

## Rollback Instructions (if needed)

```bash
cd /var/www/offmetabg
git revert 8f89ab5
git push origin master

# Revert database changes
psql -U offmetabg_user -d offmetabg_db
DELETE FROM site_settings WHERE setting_key IN ('logo_position', 'logo_max_height', 'logo_max_width', 'show_site_name');
UPDATE site_settings SET setting_type = 'text' WHERE setting_key = 'logo_url';
\q
```

---

## Troubleshooting

### Issue: Logo Not Displaying
**Check:**
```bash
# Verify file permissions
ls -la /var/www/offmetabg/uploads/settings/

# Fix permissions if needed
chmod 644 /var/www/offmetabg/uploads/settings/*
```

### Issue: Theme Colors Not Applying
**Check:**
- Browser cache (Ctrl+Shift+R to hard refresh)
- Database: `SELECT * FROM options WHERE option_key = 'active_theme';`
- Console errors in browser DevTools

### Issue: Upload Fails
**Check:**
```bash
# Check PHP upload limits
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Increase if needed (edit php.ini)
upload_max_filesize = 8M
post_max_size = 8M

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## Summary

✅ **CSS Variables**: All hardcoded colors replaced with theme variables  
✅ **Logo Management**: Full admin interface for logo upload and positioning  
✅ **Database Migration**: New logo settings added to `site_settings` table  
✅ **Dynamic Rendering**: Logo displays from database configuration  

**Commit**: `8f89ab5` - "Fix theme CSS variables and add logo management"

---

**Next Steps:**
- Test theme switching on live site
- Upload actual logo via admin panel
- Customize logo position/size as needed
