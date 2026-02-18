# OffMeta E-Commerce Platform

> **⚠️ ВАЖНО:** Това е proprietary software. Вижте [LICENSE](LICENSE) за повече информация.

Modern Bulgarian e-commerce platform built with PHP and PostgreSQL.

## 🚀 Features

### Core Functionality
- 🛍️ **Product Management** - Full-featured product catalog with categories
- 🛒 **Shopping Cart** - Advanced cart system with real-time updates
- 📦 **Order Processing** - Complete order management and tracking
- 👥 **User Authentication** - Secure customer registration and login
- 🎫 **Promotions & Discounts** - Flexible discount and promotion system
- 📧 **Email Notifications** - Automated order confirmations and updates
- 📱 **Responsive Design** - Mobile-first approach for all devices

### Admin Panel
- 📊 **Dashboard** - Real-time analytics and statistics
- 📝 **Content Management** - Manage pages, posts, and blog content
- 🖼️ **Media Library** - Image and file management system
- 👤 **User Management** - Customer and admin account control
- 🎨 **Settings** - Comprehensive site configuration
- 🔧 **Tools** - Database backup, system maintenance

### Technical Features
- 🌐 **Multilingual Support** - Bulgarian and English
- 🗄️ **PostgreSQL Database** - Robust and scalable data storage
- 🎨 **Modern UI/UX** - Clean and intuitive interface
- 🔒 **Security** - Password hashing, CSRF protection, secure sessions
- 📧 **Email Integration** - MailerSend API for transactional emails
- 🚚 **Courier Integration** - Automated shipping with Speedy/Econt

## 🛠️ Technology Stack

- **Backend:** PHP 8.1+
- **Database:** PostgreSQL 14+
- **Frontend:** Vanilla JavaScript, CSS3
- **Server:** Nginx
- **Email:** MailerSend API
- **Deployment:** Docker, VPS hosting

## 📋 Requirements

- PHP 8.1 or higher
- PostgreSQL 14 or higher
- Nginx or Apache web server
- Composer (for dependency management)
- MailerSend account (for email functionality)

## 🔧 Installation

**Note:** This is proprietary software. Installation instructions are provided for authorized users only.

1. Clone the repository (authorized users only)
2. Copy configuration examples:
   ```bash
   cp config/database.json.example config/database.json
   cp config/email-config.example.php config/email-config.php
   ```
3. Configure your database connection in `config/database.json`
4. Configure email settings in `config/email-config.php`
5. Run database migrations:
   ```bash
   php internal-tools/migrations/postgresql-schema.sql
   ```
6. Access the admin panel to complete setup

## 📂 Project Structure

```
offmetabg/
├── admin/              # Admin panel files
├── api/                # API endpoints
├── assets/             # CSS, JS, images
├── config/             # Configuration files (not in Git)
├── email-templates/    # Email templates (BG/EN)
├── includes/           # Core PHP includes
├── internal-tools/     # Development & migration tools (not in Git)
├── lang/               # Language files
├── templates/          # Frontend templates
├── storage/            # Data storage (not in Git)
├── uploads/            # User uploads (not in Git)
└── index.php           # Entry point
```

## 🔒 Security

This project implements multiple security measures:
- Password hashing with bcrypt
- CSRF token protection
- Secure session management
- SQL injection prevention (prepared statements)
- XSS protection
- Input validation and sanitization

**Important:** 
- Never commit sensitive configuration files
- Use strong passwords for admin accounts
- Keep your database credentials secure
- Regularly update dependencies

## 🌐 Deployment

This application can be deployed to:
- VPS (Hetzner, DigitalOcean, etc.)
- Docker containers
- Traditional shared hosting (with PHP 8.1+)

**Requirements for production:**
- SSL certificate (HTTPS)
- Configured email service
- PostgreSQL database
- Proper file permissions

## 📧 Email Configuration

The platform uses MailerSend for transactional emails. Configure in `config/email-config.php`:
- Account activation emails
- Password reset emails
- Order confirmation emails
- Welcome emails

## 🌍 Localization

Currently supports:
- 🇧🇬 Bulgarian (default)
- 🇬🇧 English

Language files located in `/lang/` directory.

## 📝 License

Copyright © 2024-2026 Warton. All Rights Reserved.

This is proprietary software. See [LICENSE](LICENSE) file for full terms and conditions.

**NO USAGE PERMISSION IS GRANTED** - This code is provided for viewing purposes only.

## 👤 Author

**Warton**

## 🔗 Links

- Production Site: [offmetabg.bg](https://offmetabg.bg)
- Domain: offmetabg.com

---

**⚠️ NOTICE:** This is a proprietary commercial project. Unauthorized use, copying, modification, or distribution is strictly prohibited and may result in legal action. See LICENSE file for details.
