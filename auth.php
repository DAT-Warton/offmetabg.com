<?php
/**
 * Customer Authentication
 */

define('CMS_ROOT', __DIR__);
define('CMS_ENV', getenv('CMS_ENV') ?: 'production');

// Error handling - respect environment
if (CMS_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/oauth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? 'login';
$message = '';
$error = '';

// Handle Registration
if (isset($_POST['register'])) {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = __('auth.all_fields_required');
    } elseif (!validate_email($email)) {
        $error = __('auth.invalid_email');
    } elseif ($password !== $confirm_password) {
        $error = __('auth.passwords_no_match');
    } elseif (strlen($password) < 6) {
        $error = __('auth.password_min_6');
    } else {
        // Check if user exists (database only)
        $userExists = false;
        
        if (db_enabled()) {
            ensure_db_schema();
            $table = db_table('customers');
            $userExists = !empty($table->find('username', $username)) || !empty($table->find('email', $email));
        } else {
            $error = __('auth.database_required');
            $userExists = true; // Prevent registration if DB not available
        }
        
        if ($userExists) {
            $error = __('auth.user_exists');
        } else {
            // Create new customer (in database)
            $customerId = uniqid('cust_');
            // Generate shorter, user-friendly activation token (16 characters)
            $activationToken = bin2hex(random_bytes(8));
            
            $activationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            if (db_enabled()) {
                ensure_db_schema();
                $pdo = Database::getInstance()->getPDO();
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                
                // PostgreSQL requires 'f' for false, MySQL/SQLite use 0
                $boolFalse = ($driver === 'pgsql') ? 'f' : 0;
                
                db_table('customers')->insert([
                    'id' => $customerId,
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'customer',
                    'status' => 'pending',
                    'activated' => $boolFalse,
                    'email_verified' => $boolFalse,
                    'activation_token' => $activationToken,
                    'activation_token_expires' => $activationExpires,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Try to send activation email
            $emailSent = false;
            $emailError = '';
            try {
                require_once __DIR__ . '/includes/email.php';
                $emailSender = get_email_sender();
                $lang = $_SESSION['lang'] ?? 'bg';
                $emailResult = $emailSender->sendActivationEmail($email, $username, $activationToken, $lang);
                $emailSent = $emailResult['success'] ?? false;
                if (!$emailSent) {
                    $emailError = $emailResult['message'] ?? 'Unknown error';
                    error_log("Email sending failed: ". $emailError);
                }
            } catch (Exception $e) {
                // Log error but continue with registration
                $emailError = $e->getMessage();
                error_log("Email sending exception: ". $emailError);
            }
            
            // Store registration info in session
            $_SESSION['registration_email'] = $email;
            $_SESSION['registration_username'] = $username;
            $_SESSION['activation_token'] = $activationToken;
            $_SESSION['email_sent'] = $emailSent;
            
            // Redirect to avoid form resubmission
            header('Location: auth.php?action=registered');
            exit;
        }
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $usernameOrEmail = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usernameOrEmail) || empty($password)) {
        $error = __('auth.username_password_required');
    } else {
        $found = false;
        
        // Check administrators first (from database only)
        if (db_enabled()) {
            // Try to find admin by username first
            $adminRecord = db_table('admins')->find('username', $usernameOrEmail);
            
            // If not found by username, try email
            if (!$adminRecord && filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
                $adminRecord = db_table('admins')->find('email', $usernameOrEmail);
            }
            
            if ($adminRecord && password_verify($password, $adminRecord['password'])) {
                $_SESSION['admin_user'] = $adminRecord['username'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['admin'] = true;
                $_SESSION['user_id'] = $adminRecord['id'];
                header('Location: admin/index.php');
                exit;
            }
        }
        
        // Then check customers (from database only)
        if (!$found && db_enabled()) {
            // Try to find customer by username first
            $customer = db_table('customers')->find('username', $usernameOrEmail);
            
            // If not found by username, try email
            if (!$customer && filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
                $customer = db_table('customers')->find('email', $usernameOrEmail);
            }
            
            if ($customer && password_verify($password, $customer['password'])) {
                // Check if account is activated (default to false for security)
                $isActivated = false;
                
                if (isset($customer['activated'])) {
                    $isActivated = (bool)$customer['activated'];
                } elseif (isset($customer['email_verified'])) {
                    $isActivated = (bool)$customer['email_verified'];
                } elseif (isset($customer['status'])) {
                    $isActivated = ($customer['status'] === 'active');
                }

                if (!$isActivated) {
                    $error = __('auth.activation_required') . '. ' . __('auth.check_email_activation') . '.';
                } else {
                    $_SESSION['customer_id'] = $customer['id'];
                    $_SESSION['customer_user'] = $customer['username'];
                    $_SESSION['user_role'] = $customer['role'] ?? 'customer';
                    $found = true;
                    header('Location: /');
                    exit;
                }
            }
        }
        
        if (!$found) {
            $error = __('auth.invalid_credentials');
        }
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

$pageTitle = $action === 'register' ? __('auth.register_title') : __('auth.login_title');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - OffMeta</title>
    <script>
        // Apply theme immediately from localStorage to prevent flash
        (function() {
            const storedTheme = localStorage.getItem('offmeta_theme');
            if (storedTheme) {
                document.documentElement.setAttribute('data-theme', storedTheme);
            }
        })();
    </script>
    <link rel="stylesheet"href="/assets/css/themes.css">
    <link rel="stylesheet"href="/assets/css/auth.css">
    <?php echo get_custom_theme_css(); ?>
</head>
<body class="auth-page"data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <div class="nav-actions">
        <a href="?action=<?php echo $action; ?>&lang=<?php echo opposite_lang(); ?>"class="lang-toggle"title="Switch Language">
            <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
        </a>
    </div>

    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        <p class="subtitle"><?php echo $action === 'register' ? __('auth.register_title') : ($action === 'registered' ? __('auth.activation_success_title') : __('auth.welcome_back')); ?></p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'registered'): ?>
            <?php 
            $registrationEmail = $_SESSION['registration_email'] ?? '';
            $registrationUsername = $_SESSION['registration_username'] ?? '';
            $activationToken = $_SESSION['activation_token'] ?? '';
            $emailSent = $_SESSION['email_sent'] ?? false;
            
            // Clear session data after displaying
            unset($_SESSION['registration_email'], $_SESSION['registration_username'], $_SESSION['activation_token'], $_SESSION['email_sent']);
            ?>
            <div class="message">
                <?php echo __('auth.registration_success'); ?>
            </div>
            
            <?php if ($emailSent): ?>
                <p class="info-text">
                    <?php echo __('auth.check_email_for_activation'); ?>
                    <?php if ($registrationEmail): ?>
                        (<strong><?php echo htmlspecialchars($registrationEmail); ?></strong>)
                    <?php endif; ?>
                </p>
                <p class="info-text">
                    Не виждате имейла? Проверете папката за спам.
                </p>
            <?php else: ?>
                <p class="info-text error"style="color: #ef4444; padding: 15px; background: rgba(239, 68, 68, 0.1); border-radius: 8px; border: 1px solid #ef4444;">
                    <?php echo icon_alert(18, '#ef4444'); ?> <strong>Грешка при регистрация</strong><br>
                    Имейл системата не е достъпна в момента. Моля, опитайте отново по-късно или се свържете с поддръжката.
                </p>
                <p class="info-text"style="margin-top: 15px;">
                    За съжаление, вашият акаунт не може да бъде активиран автоматично. <br>
                    Моля, свържете се с поддръжката на: <strong><?php echo get_contact_email(); ?></strong>
                </p>
            <?php endif; ?>
        <?php elseif ($action === 'register'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="reg_username"><?php echo __('auth.username'); ?></label>
                    <input type="text"id="reg_username"name="username"required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="reg_email"><?php echo __('auth.email'); ?></label>
                    <input type="email"id="reg_email"name="email"required>
                </div>
                
                <div class="form-group">
                    <label for="reg_password"><?php echo __('auth.password'); ?></label>
                    <input type="password"id="reg_password"name="password"required>
                </div>
                
                <div class="form-group">
                    <label for="reg_confirm"><?php echo __('auth.confirm_password'); ?></label>
                    <input type="password"id="reg_confirm"name="confirm_password"required>
                </div>
                
                <button type="submit"name="register"><?php echo __('auth.register_button'); ?></button>
            </form>
            
            <div class="social-divider">
                <span><?php echo current_lang() === 'bg' ? 'или се регистрирайте с' : 'or sign up with'; ?></span>
            </div>
            
            <div class="social-login">
                <a href="auth-callback.php?provider=google"class="social-btn google"title="Continue with Google">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853"d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05"d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335"d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    <span>Google</span>
                </a>
                
                <a href="auth-callback.php?provider=facebook"class="social-btn facebook"title="Continue with Facebook">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    <span>Facebook</span>
                </a>
                
                <a href="auth-callback.php?provider=instagram"class="social-btn instagram"title="Continue with Instagram">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    <span>Instagram</span>
                </a>
                
                <a href="auth-callback.php?provider=tiktok"class="social-btn tiktok"title="Continue with TikTok">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                    <span>TikTok</span>
                </a>
            </div>
            
            <div class="social-divider">
                <span><?php echo current_lang() === 'bg' ? 'или влезте с' : 'or continue with'; ?></span>
            </div>
            
            <div class="social-login">
                <a href="auth-callback.php?provider=google"class="social-btn google"title="Continue with Google">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853"d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05"d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335"d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    <span>Google</span>
                </a>
                
                <a href="auth-callback.php?provider=facebook"class="social-btn facebook"title="Continue with Facebook">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    <span>Facebook</span>
                </a>
                
                <a href="auth-callback.php?provider=instagram"class="social-btn instagram"title="Continue with Instagram">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    <span>Instagram</span>
                </a>
                
                <a href="auth-callback.php?provider=tiktok"class="social-btn tiktok"title="Continue with TikTok">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                    <span>TikTok</span>
                </a>
                
                <a href="auth-callback.php?provider=discord"class="social-btn discord"title="Continue with Discord">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z"/></svg>
                    <span>Discord</span>
                </a>
                
                <a href="auth-callback.php?provider=twitch"class="social-btn twitch"title="Continue with Twitch">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/></svg>
                    <span>Twitch</span>
                </a>
                
                <a href="auth-callback.php?provider=kick"class="social-btn kick"title="Continue with Kick">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span>Kick</span>
                </a>
            </div>
            
            <div class="s
                
                <a href="auth-callback.php?provider=discord"class="social-btn discord"title="Continue with Discord">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z"/></svg>
                    <span>Discord</span>
                </a>
                
                <a href="auth-callback.php?provider=twitch"class="social-btn twitch"title="Continue with Twitch">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/></svg>
                    <span>Twitch</span>
                </a>
                
                <a href="auth-callback.php?provider=kick"class="social-btn kick"title="Continue with Kick">
                    <svg width="20"height="20"viewBox="0 0 24 24"><path fill="currentColor"d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span>Kick</span>
                </a>
            </div>
            
            <div class="switch">
                <?php echo __('auth.already_have_account'); ?> <a href="?action=login"><?php echo __('auth.login_here'); ?></a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="login_username"><?php echo __('auth.username_or_email'); ?></label>
                    <input type="text"id="login_username"name="username"placeholder="<?php echo __('auth.username_or_email'); ?>"required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="login_password"><?php echo __('auth.password'); ?></label>
                    <input type="password"id="login_password"name="password"required>
                    <div class="forgot-password-link">
                        <a href="/password-reset.php">
                            <?php echo __('auth.forgot_password'); ?>
                        </a>
                    </div>
                </div>
                
                <button type="submit"name="login"><?php echo __('auth.login_button'); ?></button>
            </form>
            
            <div class="switch">
                <?php echo __('auth.dont_have_account'); ?> <a href="?action=register"><?php echo __('auth.register_here'); ?></a>
            </div>
        <?php endif; ?>
        
        <div class="back-home">
            <a href="/"class="btn-link"><?php echo __('back_to_shop'); ?></a>
        </div>
    </div>

    <script src="/assets/js/theme-manager.js"></script>
</body>
</html>

