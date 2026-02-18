<?php
/**
 * Database Configuration Section
 * Simple MySQL setup for cPanel hosting
 */

$configFile = CMS_ROOT . '/config/database.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
$currentDriver = $config['driver'] ?? 'json';

// Check for DATABASE_URL environment variable
$databaseUrl = getenv('DATABASE_URL');
$usingEnvDatabase = !empty($databaseUrl);

// Handle form submission
if (isset($_POST['test_connection'])) {
    $driver = $_POST['driver'] ?? 'mysql';
    try {
        if ($driver === 'pgsql' || $driver === 'postgresql') {
            $dsn = "pgsql:host={$_POST['db_host']};port=". ($_POST['db_port'] ?? '5432');
            $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_password']);
        } else {
            $dsn = "mysql:host={$_POST['db_host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_password']);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $testMessage = '✅ Connection successful!';
        $testSuccess = true;
    } catch (PDOException $e) {
        $testMessage = '❌ Connection failed: ' . $e->getMessage();
        $testSuccess = false;
    }
}

if (isset($_POST['save_database_config'])) {
    $newConfig = [
        'driver' => $_POST['driver'],
        'host' => $_POST['db_host'],
        'database' => $_POST['db_name'],
        'user' => $_POST['db_user'],
        'password' => $_POST['db_password'],
        'port' => $_POST['db_port'] ?? '3306'
    ];
    
    // Create config directory if needed
    $configDir = CMS_ROOT . '/config';
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    // Save configuration
    file_put_contents($configFile, json_encode($newConfig, JSON_PRETTY_PRINT));
    
    // If switching to MySQL, create tables
    if ($newConfig['driver'] === 'mysql') {
        try {
            $dsn = "mysql:host={$newConfig['host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $newConfig['user'], $newConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$newConfig['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$newConfig['database']}`");
            
            // Create tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS `pages` (
                `id` VARCHAR(50) PRIMARY KEY,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `title` VARCHAR(255) NOT NULL,
                `content` LONGTEXT,
                `meta_description` TEXT,
                `status` VARCHAR(20) DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_slug` (`slug`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `posts` (
                `id` VARCHAR(50) PRIMARY KEY,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `title` VARCHAR(255) NOT NULL,
                `content` LONGTEXT,
                `excerpt` TEXT,
                `meta_description` TEXT,
                `featured_image` VARCHAR(255),
                `category` VARCHAR(100),
                `status` VARCHAR(20) DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_slug` (`slug`),
                INDEX `idx_status` (`status`),
                INDEX `idx_category` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `options` (
                `option_key` VARCHAR(100) PRIMARY KEY,
                `option_value` LONGTEXT,
                `updated_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` VARCHAR(50) PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `email` VARCHAR(255) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(50) DEFAULT 'user',
                `created_at` DATETIME NOT NULL,
                INDEX `idx_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // E-Commerce Tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
                `id` VARCHAR(50) PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `description` LONGTEXT,
                `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `old_price` DECIMAL(10,2),
                `category` VARCHAR(100),
                `image` VARCHAR(255),
                `stock` INT DEFAULT 0,
                `status` VARCHAR(20) DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_category` (`category`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
                `id` VARCHAR(50) PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `description` TEXT,
                `image` VARCHAR(255),
                `parent_id` VARCHAR(50),
                `order_index` INT DEFAULT 0,
                `status` VARCHAR(20) DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_slug` (`slug`),
                INDEX `idx_parent` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
                `id` VARCHAR(50) PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(50) DEFAULT 'customer',
                `first_name` VARCHAR(100),
                `last_name` VARCHAR(100),
                `phone` VARCHAR(50),
                `address` TEXT,
                `city` VARCHAR(100),
                `postal_code` VARCHAR(20),
                `country` VARCHAR(100),
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_email` (`email`),
                INDEX `idx_role` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
                `id` VARCHAR(50) PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `email` VARCHAR(255) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(50) DEFAULT 'admin',
                `created_at` DATETIME NOT NULL,
                INDEX `idx_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
                `id` VARCHAR(50) PRIMARY KEY,
                `customer_id` VARCHAR(50),
                `status` VARCHAR(50) DEFAULT 'pending',
                `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `items` LONGTEXT,
                `shipping` TEXT,
                `payment` TEXT,
                `notes` TEXT,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_customer` (`customer_id`),
                INDEX `idx_status` (`status`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `inquiries` (
                `id` VARCHAR(50) PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `subject` VARCHAR(255),
                `message` LONGTEXT NOT NULL,
                `status` VARCHAR(50) DEFAULT 'pending',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_status` (`status`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Marketing Tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS `discounts` (
                `id` VARCHAR(50) PRIMARY KEY,
                `code` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `type` VARCHAR(20) DEFAULT 'percentage',
                `value` DECIMAL(10,2) NOT NULL,
                `min_purchase` DECIMAL(10,2) DEFAULT 0,
                `max_uses` INT DEFAULT 0,
                `uses_count` INT DEFAULT 0,
                `valid_from` DATETIME,
                `valid_until` DATETIME,
                `status` VARCHAR(20) DEFAULT 'active',
                `first_purchase_only` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_code` (`code`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `promotions` (
                `id` VARCHAR(50) PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `applies_to` VARCHAR(50) DEFAULT 'banner',
                `discount_percentage` DECIMAL(5,2) DEFAULT 0,
                `target_products` TEXT,
                `target_categories` TEXT,
                `banner_image` VARCHAR(255),
                `popup_enabled` TINYINT(1) DEFAULT 0,
                `valid_from` DATETIME,
                `valid_until` DATETIME,
                `status` VARCHAR(20) DEFAULT 'active',
                `data` LONGTEXT,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Analytics & Business Intelligence Tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS `analytics_daily` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `date` DATE NOT NULL UNIQUE,
                `total_visits` INT DEFAULT 0,
                `unique_visitors` INT DEFAULT 0,
                `page_views` INT DEFAULT 0,
                `bounce_rate` DECIMAL(5,2) DEFAULT 0,
                `traffic_sources` TEXT,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_date` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS `financial_data` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `period_start` DATE NOT NULL,
                `period_end` DATE NOT NULL,
                `total_expenses` DECIMAL(10,2) DEFAULT 0,
                `hosting_costs` DECIMAL(10,2) DEFAULT 0,
                `marketing_costs` DECIMAL(10,2) DEFAULT 0,
                `courier_costs` DECIMAL(10,2) DEFAULT 0,
                `other_costs` DECIMAL(10,2) DEFAULT 0,
                `tax_rate` DECIMAL(5,2) DEFAULT 20,
                `notes` TEXT,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_period` (`period_start`, `period_end`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $message = '✅ Database configured and all CRM tables created successfully! MySQL is now active.';
        } catch (PDOException $e) {
            $message = '❌ Error setting up database: ' . $e->getMessage();
        }
    } else {
        $message = '✅ Configuration saved. Using JSON file storage.';
    }
    
    // Reload config
    $config = $newConfig;
    $currentDriver = $config['driver'];
}
?>

<div>
    <h2>🗄️ Database Configuration</h2>
    <p class="header-note">Switch between JSON file storage and MySQL database for cPanel hosting.</p>

    <?php if (isset($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (isset($testMessage)): ?>
        <div class="message <?php echo $testSuccess ? 'message-success' : 'message-error'; ?>">
            <?php echo $testMessage; ?>
        </div>
    <?php endif; ?>

    <div class="status-panel">
        <strong>Current Mode:</strong> 
        <?php if ($usingEnvDatabase): ?>
            <span class="db-mode-label db-mode-pgsql">🐘 PostgreSQL (DATABASE_URL)</span>
            <div class="status-note">
                ℹ️ Using DATABASE_URL from environment. PostgreSQL is active and will override config below.
            </div>
        <?php else: ?>
            <span class="db-mode-label db-mode-<?php echo $currentDriver === 'pgsql' ? 'pgsql' : ($currentDriver === 'mysql' ? 'mysql' : 'json'); ?>">
                <?php 
                    if ($currentDriver === 'mysql') echo '🗄️ MySQL Database';
                    elseif ($currentDriver === 'pgsql') echo '🐘 PostgreSQL Database';
                    else echo '📄 JSON File Storage';
                ?>
            </span>
        <?php endif; ?>
    </div>

    <form method="POST">
        <div class="form-group">
            <label>Storage Mode</label>
            <select name="driver"id="driver"onchange="toggleMySQLFields()"class="select-plain">
                <option value="json"<?php echo $currentDriver === 'json' ? 'selected' : ''; ?>>📄 JSON File Storage (Default)</option>
                <option value="mysql"<?php echo $currentDriver === 'mysql' ? 'selected' : ''; ?>>🗄️ MySQL Database</option>
                <option value="pgsql"<?php echo $currentDriver === 'pgsql' ? 'selected' : ''; ?>>🐘 PostgreSQL Database</option>
            </select>
            <small class="hint">JSON is simpler. PostgreSQL/MySQL for high-traffic sites.</small>
            <?php if ($usingEnvDatabase): ?>
                <small class="hint hint-warning">⚠️ DATABASE_URL is set - using PostgreSQL automatically</small>
            <?php endif; ?>
        </div>

        <div id="mysql-fields"class="mysql-fields <?php echo ($currentDriver === 'mysql' || $currentDriver === 'pgsql') ? 'is-visible' : ''; ?>">
            <h3 class="section-subtitle mt-20">
                <span id="db-type-label"><?php echo $currentDriver === 'pgsql' ? 'PostgreSQL' : 'MySQL'; ?></span> Connection Details
            </h3>
            <p class="text-muted mb-15">📋 Get credentials from your hosting provider</p>

            <div class="form-group">
                <label>Database Host</label>
                <input type="text"name="db_host"value="<?php echo htmlspecialchars($config['host'] ?? 'localhost'); ?>"placeholder="localhost">
                <small class="hint">Usually "localhost"on cPanel shared hosting</small>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text"name="db_name"value="<?php echo htmlspecialchars($config['database'] ?? ''); ?>"placeholder="username_dbname">
                <small class="hint">Format: username_dbname (create in cPanel first)</small>
            </div>

            <div class="form-group">
                <label>Database Username</label>
                <input type="text"name="db_user"value="<?php echo htmlspecialchars($config['user'] ?? ''); ?>"placeholder="username_dbuser">
                <small class="hint">MySQL username from cPanel</small>
            </div>

            <div class="form-group">
                <label>Database Password</label>
                <input type="password"name="db_password"value="<?php echo htmlspecialchars($config['password'] ?? ''); ?>"placeholder="Enter password">
                <small class="hint">MySQL password (keep it secure!)</small>
            </div>

            <div class="form-group">
                <label>Port (Optional)</label>
                <input type="text"name="db_port"id="db_port"value="<?php echo htmlspecialchars($config['port'] ?? '3306'); ?>"placeholder="3306">
                <small class="hint">
                    <span id="port-hint">Default: 3306 for MySQL, 5432 for PostgreSQL</span>
                </small>
            </div>

            <div class="my-20">
                <button type="submit"name="test_connection"class="btn btn-warning">🔍 Test Connection</button>
                <small class="hint form-note">Test before saving to verify credentials are correct</small>
            </div>
        </div>

        <div class="mt-20 pt-20 border-top">
            <button type="submit"name="save_database_config"class="btn">💾 Save Configuration</button>
        </div>
    </form>

    <div class="card card-note mt-30">
        <h3 class="mb-10">📖 How to Setup MySQL in cPanel</h3>
        <ol>
            <li>Login to cPanel</li>
            <li>Go to <strong>MySQL Databases</strong></li>
            <li>Create a new database (e.g., "username_cms")</li>
            <li>Create a new MySQL user</li>
            <li>Add user to database with ALL PRIVILEGES</li>
            <li>Copy the credentials and paste them here</li>
            <li>Click "Test Connection"to verify</li>
            <li>Click "Save Configuration"to activate MySQL</li>
        </ol>
        <p class="mt-10"><strong>Note:</strong> Tables will be created automatically when you save!</p>
    </div>
</div>

<script src="assets/js/database.js"defer></script>

