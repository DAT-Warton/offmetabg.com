<?php
/**
 * CMS Admin Panel
 * Central dashboard for website management
 */

define('CMS_ROOT', dirname(__DIR__));

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

    // Check admins.json first
    $admins = load_json('storage/admins.json');
    foreach ($admins as $admin) {
        if ($admin['username'] === $username && password_verify($password, $admin['password'])) {
            $_SESSION['admin_user'] = $admin['username'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['admin'] = true;
            $_SESSION['user_id'] = $admin['id'];
            redirect('admin/dashboard');
            break;
        }
    }

    // Fallback: hardcoded admin credentials
    if ($username === 'Warton' && $password === 'Warton2026') {
        $_SESSION['admin_user'] = $username;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['admin'] = true;
        redirect('admin/dashboard');
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
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                padding: 40px;
                width: 100%;
                max-width: 400px;
            }
            h1 { font-size: 28px; margin-bottom: 10px; text-align: center; color: #333; }
            .subtitle { text-align: center; color: #999; margin-bottom: 30px; font-size: 14px; }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 8px;
                color: #555;
                font-weight: 500;
            }
            input {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.3s;
            }
            input:focus {
                outline: none;
                border-color: #3498db;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            }
            .error { color: #dc3545; margin-bottom: 20px; padding: 10px; background: #f8d7da; border-radius: 6px; }
            .note {
                margin-top: 20px;
                padding: 12px;
                background: #f0f4ff;
                border-left: 4px solid #3498db;
                color: #555;
                font-size: 13px;
            }
        </style>
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
                    <input type="text" id="username" name="username" required autofocus value="admin">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required value="admin">
                </div>
                <button type="submit" name="login" value="1">Login to Dashboard</button>
            </form>

            <div class="note">
                <?php echo icon_edit(18); ?> Default credentials: admin / admin
                <br><strong>Change this after first login!</strong>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If logged in, load dashboard
require_once 'dashboard.php';

