<?php
/**
 * CMS Admin Panel
 * Central dashboard for website management
 */

// Enable error logging for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/admin-errors.log');

// Only display errors in development
if (getenv('CMS_ENV') === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
}

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

// Define CMS constants
if (!defined('CMS_VERSION')) {
    define('CMS_VERSION', '1.0.0');
}
if (!defined('CMS_ENV')) {
    define('CMS_ENV', getenv('CMS_ENV') ?: 'production');
}

// Load CMS functions with error handling
try {
    // @phpstan-ignore-next-line
    require_once CMS_ROOT . '/includes/functions.php';
    // @phpstan-ignore-next-line
    require_once CMS_ROOT . '/includes/database.php';
    // @phpstan-ignore-next-line
    require_once CMS_ROOT . '/includes/language.php';
    // @phpstan-ignore-next-line
    require_once CMS_ROOT . '/includes/icons.php';
} catch (Exception $e) {
    error_log('Admin index.php include error: ' . $e->getMessage());
    http_response_code(500);
    die('System error. Please check configuration.');
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
$isLoggedIn = isset($_SESSION['admin_user']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$isLoggedIn && isset($_POST['login'])) {
    $usernameOrEmail = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $loginSuccess = false;

    // Check database for admin credentials
    if (db_enabled()) {
        $adminRecord = null;
        
        // Try to find by username first
        $adminRecord = db_table('admins')->find('username', $usernameOrEmail);
        
        // If not found, try by email
        if (!$adminRecord) {
            $adminRecord = db_table('admins')->find('email', $usernameOrEmail);
        }
        
        if ($adminRecord && password_verify($password, $adminRecord['password'])) {
            $_SESSION['admin_user'] = $adminRecord['username'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['admin'] = true;
            $_SESSION['user_id'] = $adminRecord['id'];
            $loginSuccess = true;
        }

        // If not found, check customers with admin role
        if (!$loginSuccess) {
            $customerRecord = db_table('customers')->find('username', $usernameOrEmail);
            
            // Try by email if not found by username
            if (!$customerRecord) {
                $customerRecord = db_table('customers')->find('email', $usernameOrEmail);
            }
            
            if ($customerRecord && 
                password_verify($password, $customerRecord['password']) && 
                ($customerRecord['role'] ?? 'customer') === 'admin') {
                $_SESSION['admin_user'] = $customerRecord['username'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['admin'] = true;
                $_SESSION['user_id'] = $customerRecord['id'];
                $loginSuccess = true;
            }
        }
    }

    if ($loginSuccess) {
        redirect('dashboard');
    } else {
        $error = 'Invalid admin credentials. Only administrators can access this area.';
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    // Build absolute URL for reliable redirect from admin section
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    header('Location: ' . $protocol . '://' . $host . '/');
    exit;
}

// If not logged in, show login page
if (!$isLoggedIn) {
    $site_title = htmlspecialchars(get_option('site_title', 'OffMeta'));
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo current_lang(); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo __('admin_panel'); ?> - <?php echo $site_title; ?></title>
        
        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        
        <script>
            // Apply theme immediately from localStorage to prevent flash
            (function() {
                const storedTheme = localStorage.getItem('offmeta_theme');
                if (storedTheme) {
                    document.documentElement.setAttribute('data-theme', storedTheme);
                }
            })();
        </script>
        
        <!-- Critical CSS inlined for instant render - OPTIMIZED FOR CLS -->
        <style>
            :root{--color-white:#fff;--primary:#9f7aea;--primary-hover:#b794f4;--bg-body:linear-gradient(135deg,#0f0a1a 0%,#1a1625 50%,#2d1b4e 100%);--bg-primary:#1b1430;--bg-secondary:#52456d;--text-primary:#fff;--text-secondary:#e8e4f0;--border-color:#2d1b4e;--shadow-xl:rgba(0,0,0,0.5);--danger-bg:#3d1e1e;--danger-border:#783030;--danger-text:#fca5a5}
            *{margin:0;padding:0;box-sizing:border-box}
            body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:var(--bg-body);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:10px;position:relative}
            /* NAV-ACTIONS - Fixed position to prevent layout shift */
            .nav-actions{position:absolute;top:20px;right:20px;z-index:1000;opacity:1}
            .lang-toggle{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--bg-primary);color:var(--text-secondary);text-decoration:none;border-radius:6px;font-size:14px;font-weight:500;border:1px solid var(--border-color);transition:all 0.2s ease}
            .lang-toggle:hover{background:var(--bg-secondary);color:var(--text-primary);border-color:var(--primary)}
            /* CONTAINER - Fixed min-height to prevent resize */
            .container{background:var(--bg-primary);padding:30px;border-radius:8px;box-shadow:0 20px 60px var(--shadow-xl);width:100%;max-width:420px;min-height:480px}
            /* H1 with icon - Reserve space for SVG */
            h1{color:var(--primary);margin-bottom:12px;text-align:center;font-size:28px;line-height:1.2;min-height:40px;display:flex;align-items:center;justify-content:center;gap:8px}
            h1 svg{width:28px;height:28px;flex-shrink:0}
            .subtitle{color:var(--text-secondary);text-align:center;margin-bottom:30px;font-size:14px;line-height:1.5;min-height:42px}
            .form-group{margin-bottom:20px}
            label{display:block;margin-bottom:8px;color:var(--text-primary);font-weight:600;font-size:14px}
            input{width:100%;padding:12px 14px;border:1px solid var(--border-color);border-radius:6px;font-size:14px;background:var(--bg-secondary);color:var(--text-primary);transition:border-color 0.2s ease}
            input:focus{outline:none;border-color:var(--primary)}
            button{width:100%;padding:14px;background:var(--primary);color:var(--color-white);border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.2s ease;margin-top:8px}
            button:hover{transform:translateY(-2px);background:var(--primary-hover)}
            .error{background:var(--danger-bg);border:1px solid var(--danger-border);color:var(--danger-text);padding:14px;border-radius:6px;margin-bottom:20px;font-size:14px;line-height:1.5}
            .switch{text-align:center;margin-top:24px;padding:16px;background:rgba(255,255,255,0.03);border-radius:6px;color:var(--text-secondary);font-size:13px;line-height:1.5;min-height:52px}
            .switch svg{display:inline-block;vertical-align:middle;margin-right:4px}
            .back-home{text-align:center;margin-top:20px;min-height:40px}
            .btn-link{color:var(--primary);text-decoration:none;font-size:14px;font-weight:500;transition:color 0.2s ease}
            .btn-link:hover{color:var(--primary-hover)}
        </style>
        
        <!-- Load additional CSS asynchronously (non-critical) -->
        <link rel="preload" href="/assets/css/themes.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link rel="stylesheet" href="/assets/css/themes.min.css">
        </noscript>
        <?php echo get_custom_theme_css(); ?>
    </head>
    <body class="auth-page" data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
        <div class="nav-actions">
            <a href="?lang=<?php echo opposite_lang(); ?>" class="lang-toggle" title="Switch Language">
                <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
            </a>
        </div>

        <div class="container">
            <h1><?php echo icon_lock(28); ?> <?php echo __('admin_panel'); ?></h1>
            <p class="subtitle"><?php echo get_site_setting('site_description', 'Complete Website Management System', 'general'); ?></p>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" placeholder="Enter username or email" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password"><?php echo __('auth.password'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login"><?php echo __('auth.login_button'); ?></button>
            </form>

            <div class="switch">
                <?php echo icon_edit(18); ?> <?php echo __('auth.contact_admin_credentials'); ?>
            </div>
            
            <div class="back-home">
                <a href="/index.php" class="btn-link"><?php echo __('back_to_shop'); ?></a>
            </div>
        </div>

        <script src="/assets/js/theme-manager.min.js"defer></script>
    </body>
    </html>
    <?php
    exit;
}

// If logged in, load dashboard
require_once 'dashboard.php';

