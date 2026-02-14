<?php
/**
 * CMS Admin Panel
 * Central dashboard for website management
 */

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

// Load CMS functions
// @phpstan-ignore-next-line
require_once CMS_ROOT . '/includes/functions.php';
// @phpstan-ignore-next-line
require_once CMS_ROOT . '/includes/database.php';
// @phpstan-ignore-next-line
require_once CMS_ROOT . '/includes/language.php';
// @phpstan-ignore-next-line
require_once CMS_ROOT . '/includes/icons.php';

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
$isLoggedIn = isset($_SESSION['admin_user']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$isLoggedIn && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $loginSuccess = false;

    // Check database for admin credentials
    if (db_enabled()) {
        // Check admins table first
        $adminRecord = db_table('admins')->find('username', $username);
        if ($adminRecord && password_verify($password, $adminRecord['password'])) {
            $_SESSION['admin_user'] = $adminRecord['username'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['admin'] = true;
            $_SESSION['user_id'] = $adminRecord['id'];
            $loginSuccess = true;
        }

        // If not found, check customers with admin role
        if (!$loginSuccess) {
            $customerRecord = db_table('customers')->find('username', $username);
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CMS Admin Login</title>
        <link rel="stylesheet" href="assets/css/admin-login.css">
    </head>
    <body>
        <div class="login-container">
            <h1><?php echo icon_lock(28); ?> CMS Admin</h1>
            <p class="subtitle">Complete Website Management System</p>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login" value="1">Login to Dashboard</button>
            </form>

            <div class="note">
                <?php echo icon_edit(18); ?> Contact administrator for credentials
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If logged in, load dashboard
require_once 'dashboard.php';

