<?php
define('CMS_ROOT', __DIR__);

session_start();
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/language.php';
require_once 'includes/icons.php';

$lang = current_lang();
$message = '';
$error = '';
$show_form = false;
$token = '';
$user_id = '';

if (db_enabled()) {
    ensure_db_schema();
}

// Check if token is provided in URL (support both query param and URL path)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = sanitize($_GET['token']);
} else {
    // Check URL path for /reset/TOKEN format
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/reset/([a-zA-Z0-9]+)#', $uri, $matches)) {
        $token = sanitize($matches[1]);
    }
}

// If token is provided, verify it
if (!empty($token)) {
    // Verify token exists and is not expired
    $user = null;
    
    if (db_enabled()) {
        $rows = db_table('customers')->all();
        foreach ($rows as $row) {
            if (isset($row['reset_token']) &&
                $row['reset_token'] === $token &&
                isset($row['reset_expires']) &&
                strtotime($row['reset_expires']) > time()) {
                $user = $row;
                $user_id = $row['id'] ?? '';
                break;
            }
        }
    } else {
        $customers = load_json('storage/customers.json');
        foreach ($customers as $id => $customer) {
            if (isset($customer['reset_token']) &&
                $customer['reset_token'] === $token &&
                isset($customer['reset_token_expires']) &&
                strtotime($customer['reset_token_expires']) > time()) {
                $user = $customer;
                $user_id = $id;
                break;
            }
        }
    }
    
    if ($user) {
        $show_form = true;
    } else {
        $error = __('password_reset.invalid_link');
    }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = sanitize($_POST['token']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $error = __('password_reset.enter_new_password');
    } elseif (strlen($new_password) < 6) {
        $error = __('password_reset.password_min_length');
    } elseif ($new_password !== $confirm_password) {
        $error = __('password_reset.passwords_no_match');
    } else {
        // Verify token again and update password
        $found = false;

        if (db_enabled()) {
            $rows = db_table('customers')->all();
            foreach ($rows as $row) {
                if (isset($row['reset_token']) &&
                    $row['reset_token'] === $token &&
                    isset($row['reset_expires']) &&
                    strtotime($row['reset_expires']) > time()) {
                    db_table('customers')->update($row['id'], [
                        'password' => password_hash($new_password, PASSWORD_DEFAULT),
                        'reset_token' => null,
                        'reset_expires' => null,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $message = __('password_reset.password_changed');
                    $show_form = false;
                    $found = true;
                    break;
                }
            }
        } else {
            $customers = load_json('storage/customers.json');
            foreach ($customers as $id => $customer) {
                if (isset($customer['reset_token']) &&
                    $customer['reset_token'] === $token &&
                    isset($customer['reset_token_expires']) &&
                    strtotime($customer['reset_token_expires']) > time()) {
                    
                    // Update password and clear reset token
                    $customers[$id]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                    unset($customers[$id]['reset_token']);
                    unset($customers[$id]['reset_token_expires']);
                    
                    save_json('storage/customers.json', $customers);
                    
                    $message = __('password_reset.password_changed');
                    
                    $show_form = false;
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            $error = __('password_reset.invalid_link');
        }
    }
}

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = sanitize($_POST['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('password_reset.invalid_email');
    } else {
        // Check if user exists
        $user = null;
        $user_id = null;

        if (db_enabled()) {
            $rows = db_table('customers')->all();
            foreach ($rows as $row) {
                if (($row['email'] ?? '') === $email) {
                    $user = $row;
                    $user_id = $row['id'] ?? '';
                    break;
                }
            }
        } else {
            $customers = load_json('storage/customers.json');
            foreach ($customers as $id => $customer) {
                if ($customer['email'] === $email) {
                    $user = $customer;
                    $user_id = $id;
                    break;
                }
            }
        }
        
        if ($user) {
            // Generate shorter, user-friendly reset token (16 characters)
            $reset_token = bin2hex(random_bytes(8));
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token to customer data
            if (db_enabled()) {
                db_table('customers')->update($user_id, [
                    'reset_token' => $reset_token,
                    'reset_expires' => $reset_expires,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                $customers[$user_id]['reset_token'] = $reset_token;
                $customers[$user_id]['reset_token_expires'] = $reset_expires;
                save_json('storage/customers.json', $customers);
            }
            
            // Send reset email
            require_once 'includes/email.php';
            $emailSender = get_email_sender();
            $result = $emailSender->sendPasswordResetEmail($email, $user['username'], $reset_token, $lang);
            
            if ($result['success']) {
                $message = __('password_reset.reset_link_sent');
            } else {
                $error = __('password_reset.email_send_error');
            }
        } else {
            // Don't reveal if email exists for security
            $message = __('password_reset.email_if_exists');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('password_reset.title'); ?> - OffMeta</title>
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="stylesheet" href="assets/css/password-reset.css">
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <div class="auth-container">
        <div class="auth-box">
            <div class="theme-toggle">
                <button onclick="toggleTheme()" class="theme-btn" id="theme-btn">
                    <span class="theme-icon"><?php echo icon_moon(18); ?></span>
                </button>
                <a href="?lang=<?= opposite_lang() ?><?= isset($_GET['token']) ? '&token=' . htmlspecialchars($_GET['token']) : '' ?>" class="lang-btn">
                    <span class="lang-flag"><?= lang_flag(opposite_lang()) ?></span>
                    <span class="lang-text"><?= strtoupper(opposite_lang()) ?></span>
                </a>
            </div>
            
            <h2><?php echo __('password_reset.title'); ?></h2>
            
            <?php if ($message): ?>
                <div class="success-message">
                    <?= htmlspecialchars($message) ?>
                    <br><br>
                    <a href="auth.php?action=login" class="btn-link">
                        <?php echo __('password_reset.go_to_login'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($show_form): ?>
                <!-- Reset Password Form -->
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-group">
                        <label><?php echo __('password_reset.new_password'); ?>:</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('password_reset.confirm_password'); ?>:</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn-primary">
                        <?php echo __('password_reset.change_password'); ?>
                    </button>
                </form>
            <?php elseif (!$message): ?>
                <!-- Request Reset Form -->
                <p class="reset-instructions">
                    <?php echo __('password_reset.enter_email_instructions'); ?>
                </p>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label><?php echo __('password_reset.email_address'); ?>:</label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <button type="submit" name="request_reset" class="btn-primary">
                        <?php echo __('password_reset.send_reset_link'); ?>
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="back-link-container">
                <a href="auth.php?action=login" class="btn-link">
                    <?php echo __('back_to_shop'); ?>
                </a>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-manager.js"></script>
</body>
</html>

