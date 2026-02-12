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
            $dsn = "pgsql:host={$_POST['db_host']};port=" . ($_POST['db_port'] ?? '5432');
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
            
            $message = '✅ Database configured and tables created successfully! MySQL is now active.';
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
    <p style="margin-bottom: 20px; color: #666;">Switch between JSON file storage and MySQL database for cPanel hosting.</p>

    <?php if (isset($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (isset($testMessage)): ?>
        <div class="message" style="background: <?php echo $testSuccess ? 'var(--success-bg, #d4edda)' : '#f8d7da'; ?>; border: 1px solid <?php echo $testSuccess ? 'var(--success-border, #c3e6cb)' : '#f5c6cb'; ?>; color: <?php echo $testSuccess ? 'var(--success-text, #155724)' : '#721c24'; ?>; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo $testMessage; ?>
        </div>
    <?php endif; ?>

    <div style="background: var(--bg-primary, #f8f9fa); padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <strong>Current Mode:</strong> 
        <?php if ($usingEnvDatabase): ?>
            <span style="color: #9333ea; font-weight: bold;">🐘 PostgreSQL (DATABASE_URL)</span>
            <div style="margin-top: 10px; padding: 10px; background: #f3e8ff; border-left: 3px solid #9333ea; font-size: 13px;">
                ℹ️ Using DATABASE_URL from environment. PostgreSQL is active and will override config below.
            </div>
        <?php else: ?>
            <span style="color: <?php echo $currentDriver === 'mysql' ? '#28a745' : ($currentDriver === 'pgsql' ? '#9333ea' : '#3498db'); ?>; font-weight: bold;">
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
            <select name="driver" id="driver" onchange="toggleMySQLFields()" style="padding: 10px; background: var(--bg-secondary, white); color: var(--text-primary, #333); border: 1px solid var(--border-color, #ddd);">
                <option value="json" <?php echo $currentDriver === 'json' ? 'selected' : ''; ?>>📄 JSON File Storage (Default)</option>
                <option value="mysql" <?php echo $currentDriver === 'mysql' ? 'selected' : ''; ?>>🗄️ MySQL Database</option>
                <option value="pgsql" <?php echo $currentDriver === 'pgsql' ? 'selected' : ''; ?>>🐘 PostgreSQL Database</option>
            </select>
            <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);">JSON is simpler. PostgreSQL/MySQL for high-traffic sites.</small>
            <?php if ($usingEnvDatabase): ?>
                <small style="display: block; margin-top: 5px; color: #9333ea; font-weight: 600;">⚠️ DATABASE_URL is set - using PostgreSQL automatically</small>
            <?php endif; ?>
        </div>

        <div id="mysql-fields" style="display: <?php echo ($currentDriver === 'mysql' || $currentDriver === 'pgsql') ? 'block' : 'none'; ?>;">
            <h3 style="margin: 20px 0 15px; color: var(--primary, #3498db);">
                <span id="db-type-label"><?php echo $currentDriver === 'pgsql' ? 'PostgreSQL' : 'MySQL'; ?></span> Connection Details
            </h3>
            <p style="color: var(--text-secondary, #666); margin-bottom: 15px;">📋 Get credentials from your hosting provider</p>

            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="<?php echo htmlspecialchars($config['host'] ?? 'localhost'); ?>" placeholder="localhost">
                <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);">Usually "localhost" on cPanel shared hosting</small>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="<?php echo htmlspecialchars($config['database'] ?? ''); ?>" placeholder="username_dbname">
                <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);">Format: username_dbname (create in cPanel first)</small>
            </div>

            <div class="form-group">
                <label>Database Username</label>
                <input type="text" name="db_user" value="<?php echo htmlspecialchars($config['user'] ?? ''); ?>" placeholder="username_dbuser">
                <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);">MySQL username from cPanel</small>
            </div>

            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_password" value="<?php echo htmlspecialchars($config['password'] ?? ''); ?>" placeholder="Enter password">
                <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);">MySQL password (keep it secure!)</small>
            </div>

            <div class="form-group">
                <label>Port (Optional)</label>
                <input type="text" name="db_port" id="db_port" value="<?php echo htmlspecialchars($config['port'] ?? '3306'); ?>" placeholder="3306">
                <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);">
                    <span id="port-hint">Default: 3306 for MySQL, 5432 for PostgreSQL</span>
                </small>
            </div>

            <div style="margin: 20px 0;">
                <button type="submit" name="test_connection" style="background: var(--warning, #ffc107); color: #000; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">🔍 Test Connection</button>
                <small style="display: block; margin-top: 10px; color: var(--text-secondary, #666);">Test before saving to verify credentials are correct</small>
            </div>
        </div>

        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color, #ddd);">
            <button type="submit" name="save_database_config">💾 Save Configuration</button>
        </div>
    </form>

    <div style="margin-top: 30px; padding: 15px; background: var(--warning-bg, #fff3cd); border: 1px solid var(--warning, #ffc107); border-radius: 6px;">
        <h3 style="color: var(--text-primary, #856404); margin-bottom: 10px;">📖 How to Setup MySQL in cPanel</h3>
        <ol style="color: var(--text-primary, #856404); line-height: 1.8;">
            <li>Login to cPanel</li>
            <li>Go to <strong>MySQL Databases</strong></li>
            <li>Create a new database (e.g., "username_cms")</li>
            <li>Create a new MySQL user</li>
            <li>Add user to database with ALL PRIVILEGES</li>
            <li>Copy the credentials and paste them here</li>
            <li>Click "Test Connection" to verify</li>
            <li>Click "Save Configuration" to activate MySQL</li>
        </ol>
        <p style="color: var(--text-primary, #856404); margin-top: 10px;"><strong>Note:</strong> Tables will be created automatically when you save!</p>
    </div>
</div>

<script src="assets/js/database.js"></script>

