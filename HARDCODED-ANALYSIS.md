# 🔍 Hardcoded Values Analysis - offmetabg.com

**Generated:** 2026-02-14
**Purpose:** Identify ALL hardcoded values that should be moved to database

---

## 📋 CRITICAL FINDINGS

### 1. CSS FILES - Color Values (100+ instances)

#### **home.css** - 80+ hardcoded colors
- Body/Hero backgrounds: `#e8ddf5`, `#5a2fb1`, `#3b2a7c`, `#1c123c` ✅ FIXED (partially)
- Category card gradients: `#f093fb`, `#f5576c`, `#4facfe`, `#00f2fe`, `#43e97b` ❌ NOT FIXED
- Button colors: `#c084fc`, `#a855f7`, `#ef4444` ❌ NOT FIXED
- Text colors: `#1b1430`, `#2c3e50`, `#6b7280`, `#1f2937` ❌ NOT FIXED
- Footer: `#1a1625`, `#9ca3af`, `#374151` ❌ NOT FIXED
- Dark theme overrides: 40+ hardcoded colors ❌ NOT FIXED

#### **themes.css** - Built-in theme definitions
- All 6 themes have hardcoded values (expected) ✅ OK
- Should remain as DEFAULT fallbacks

#### **Other CSS files** (not checked yet):
- auth.css
- cart.css
- blog.css
- post.css
- category.css
- page.css
- admin.css (admin panel)
- admin-*.css files

---

### 2. PHP FILES - Configuration Values

#### **Database Configuration**
Location: `config/database.json`, `includes/database.php`
- ❌ Database credentials NOT in environment variables
- ❌ Connection settings hardcoded

#### **Email Configuration** 
Location: `config/email-config.php`
- ❌ Email API keys hardcoded
- ❌ SMTP settings hardcoded

#### **Email Templates**
Location: `email-templates/*.php`
- ❌ Email content hardcoded (should be in DB for easy editing)
- ❌ Email styling hardcoded

#### **Site Settings**
Scattered across multiple files:
- ❌ Site name/logo not in database
- ❌ Footer content hardcoded
- ❌ Navigation links hardcoded
- ❌ Social media links missing

---

### 3. JAVASCRIPT FILES - API Endpoints & Constants

#### **theme-manager.js**
- API endpoint: `/api/handler.php` ✅ OK (centralized)
- Theme handling ✅ OK (loads from backend)

#### **admin scripts**
- Need to check for hardcoded admin settings

---

## 🎯 PRIORITY FIXES

### **Priority 1 - BLOCKING CUSTOM THEMES** 🔥
1. ✅ **home.css body background** - FIXED
2. ✅ **home.css hero section** - FIXED  
3. ✅ **home.css primary/secondary colors** - FIXED
4. ❌ **home.css category card colors** - TODO
5. ❌ **home.css button badges (#c084fc, #a855f7, #ef4444)** - TODO
6. ❌ **home.css text colors** - TODO
7. ❌ **home.css dark theme overrides** - TODO

### **Priority 2 - USER EXPERIENCE**
1. ❌ Move email templates to database (admin editable)
2. ❌ Add site settings table (site name, logo, footer, etc.)
3. ❌ Move navigation to database (editable menu)
4. ❌ Social media links in settings

### **Priority 3 - SECURITY & MAINTENANCE**
1. ❌ Environment variables for credentials
2. ❌ API keys out of code
3. ❌ Database credentials in environment
4. ❌ Email config in database

---

## 📊 DATABASE SCHEMA NEEDED

### **site_settings** table
```sql
CREATE TABLE site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    setting_type VARCHAR(50), -- text, color, url, json, etc.
    category VARCHAR(50), -- general, theme, email, social, etc.
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### **email_templates** table
```sql
CREATE TABLE email_templates (
    id SERIAL PRIMARY KEY,
    template_key VARCHAR(100) UNIQUE,
    subject VARCHAR(255),
    body TEXT,
    language VARCHAR(5) DEFAULT 'en',
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### **navigation_menus** table
```sql
CREATE TABLE navigation_menus (
    id SERIAL PRIMARY KEY,
    menu_location VARCHAR(50), -- header, footer, sidebar
    label VARCHAR(100),
    url VARCHAR(255),
    parent_id INT REFERENCES navigation_menus(id),
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true
);
```

---

## 🔧 REFACTORING STRATEGY

### **Phase 1 - Critical CSS** (NOW)
- Replace ALL hex colors in home.css with CSS variables
- Fix category card gradients
- Fix button/badge colors
- Fix dark theme

### **Phase 2 - Admin Settings UI** (NEXT)
- Create settings section in admin panel
- Add forms for site name, logo, footer
- Add theme color picker (override CSS variables)

### **Phase 3 - Database Migration** (THEN)
- Create new database tables
- Migrate hardcoded values to DB
- Update PHP code to load from DB

### **Phase 4 - Environment Variables** (FINAL)
- Move credentials to .env
- Update deployment scripts
- Document configuration

---

## 🚀 IMMEDIATE ACTION ITEMS

1. **Finish home.css refactoring** (remove remaining 40+ hardcoded colors)
2. **Create settings table** in PostgreSQL
3. **Build admin settings UI** for dynamic site configuration
4. **Migrate critical settings** to database

---

## 📈 PROGRESS TRACKING

**CSS Refactoring:**
- home.css: 40% complete (body, hero, products section done)
- admin.css: 0% complete
- themes.css: N/A (intentionally hardcoded as defaults)
- Other CSS: 0% complete

**Database Tables:**
- options: ✅ EXISTS (for theme storage)
- site_settings: ❌ NEEDED
- email_templates: ❌ NEEDED
- navigation_menus: ❌ NEEDED

**Admin UI:**
- Theme customizer: ✅ COMPLETE
- Site settings: ❌ NEEDED
- Email templates editor: ❌ NEEDED
- Navigation editor: ❌ NEEDED
