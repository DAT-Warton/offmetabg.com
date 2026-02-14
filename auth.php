<?php
/**
 * Customer Authentication
 */

define('CMS_ROOT', __DIR__);

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/icons.php';

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
                db_table('customers')->insert([
                    'id' => $customerId,
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'customer',
                    'status' => 'pending',
                    'activated' => false,
                    'email_verified' => false,
                    'activation_token' => $activationToken,
                    'activation_token_expires' => $activationExpires,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Try to send activation email
            $emailSent = false;
            try {
                require_once __DIR__ . '/includes/email.php';
                $emailSender = get_email_sender();
                $lang = $_SESSION['lang'] ?? 'bg';
                $emailResult = $emailSender->sendActivationEmail($email, $username, $activationToken, $lang);
                $emailSent = $emailResult['success'] ?? false;
            } catch (Exception $e) {
                // Log error but continue with registration
                error_log("Email sending failed: " . $e->getMessage());
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
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = __('auth.username_password_required');
    } else {
        $found = false;
        
        // Check administrators first (from database only)
        if (db_enabled()) {
            $adminRecord = db_table('admins')->find('username', $username);
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
            $customer = db_table('customers')->find('username', $username);
            if ($customer && password_verify($password, $customer['password'])) {
                $isActivated = true;
                if (isset($customer['activated'])) {
                    $isActivated = (bool)$customer['activated'];
                } elseif (isset($customer['email_verified'])) {
                    $isActivated = (bool)$customer['email_verified'];
                } elseif (isset($customer['status'])) {
                    $isActivated = $customer['status'] !== 'pending';
                }

                if (!$isActivated) {
                    $error = __('auth.activation_required') . '. ' . __('auth.check_email_activation') . '.';
                } else {
                    $_SESSION['customer_id'] = $customer['id'];
                    $_SESSION['customer_user'] = $customer['username'];
                    $_SESSION['user_role'] = $customer['role'] ?? 'customer';
                    $found = true;
                    header('Location: index.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - OffMeta</title>
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <?php echo get_custom_theme_css(); ?>
</head>
<body class="auth-page" data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <div class="nav-actions">
        <button type="button" id="themeToggle" class="theme-toggle" title="<?php echo __('theme.switch_to_dark'); ?>"><?php echo icon_moon(18); ?></button>
        <a href="?action=<?php echo $action; ?>&lang=<?php echo opposite_lang(); ?>" class="lang-toggle" title="Switch Language">
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
                <p class="info-text warning">
                    <?php echo icon_alert(18, '#f59e0b'); ?> Имейлът не може да бъде изпратен автоматично.
                </p>
                <?php if ($activationToken): ?>
                    <p class="info-text">
                        Моля, използвайте този линк за активация:
                    </p>
                    <div class="activation-link-box">
                        <a href="<?php echo $site_url ?? 'http://localhost:8000'; ?>/activate/<?php echo htmlspecialchars($activationToken); ?>">
                            <?php echo $site_url ?? 'http://localhost:8000'; ?>/activate/<?php echo htmlspecialchars($activationToken); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif ($action === 'register'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="reg_username"><?php echo __('auth.username'); ?></label>
                    <input type="text" id="reg_username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="reg_email"><?php echo __('auth.email'); ?></label>
                    <input type="email" id="reg_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="reg_password"><?php echo __('auth.password'); ?></label>
                    <input type="password" id="reg_password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="reg_confirm"><?php echo __('auth.confirm_password'); ?></label>
                    <input type="password" id="reg_confirm" name="confirm_password" required>
                </div>
                
                <button type="submit" name="register"><?php echo __('auth.register_button'); ?></button>
            </form>
            
            <div class="switch">
                <?php echo __('auth.already_have_account'); ?> <a href="?action=login"><?php echo __('auth.login_here'); ?></a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="login_username"><?php echo __('auth.username'); ?></label>
                    <input type="text" id="login_username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="login_password"><?php echo __('auth.password'); ?></label>
                    <input type="password" id="login_password" name="password" required>
                    <div class="forgot-password-link">
                        <a href="password-reset.php">
                            <?php echo __('auth.forgot_password'); ?>
                        </a>
                    </div>
                </div>
                
                <button type="submit" name="login"><?php echo __('auth.login_button'); ?></button>
            </form>
            
            <div class="switch">
                <?php echo __('auth.dont_have_account'); ?> <a href="?action=register"><?php echo __('auth.register_here'); ?></a>
            </div>
        <?php endif; ?>
        
        <div class="back-home">
            <a href="index.php" class="btn-link"><?php echo __('back_to_shop'); ?></a>
        </div>
    </div>

    <script src="assets/js/theme-manager.js"></script>
</body>
</html>

