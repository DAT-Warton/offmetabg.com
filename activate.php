<?php
/**
 * Account Activation Page
 */

define('CMS_ROOT', __DIR__);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/icons.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = false;
$error = '';
$message = '';

// Check if token is provided (support both query param and URL path)
$token = $_GET['token'] ?? '';

// If no query token, check URL path for /activate/TOKEN format
if (empty($token)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    // Match /activate/TOKEN pattern
    if (preg_match('#/activate/([a-zA-Z0-9]+)#', $uri, $matches)) {
        $token = $matches[1];
    }
}

if (empty($token)) {
    $error = __('auth.invalid_activation_link');
} else {
    if (db_enabled()) {
        ensure_db_schema();
        $customer = db_table('customers')->find('activation_token', $token);
        if ($customer) {
            $expires = $customer['activation_token_expires'] ?? null;
            if ($expires && strtotime($expires) < time()) {
                $error = __('auth.activation_link_expired');
            } elseif (!empty($customer['activated']) || !empty($customer['email_verified'])) {
                $message = __('auth.already_activated');
                $success = true;
            } else {
                $pdo = Database::getInstance()->getPDO();
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                $boolTrue = ($driver === 'pgsql') ? 't' : 1;
                
                db_table('customers')->update($customer['id'], [
                    'activated' => $boolTrue,
                    'email_verified' => $boolTrue,
                    'activated_at' => date('Y-m-d H:i:s'),
                    'activation_token' => null,
                    'activation_token_expires' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $message = __('auth.activated_successfully');
                $success = true;
            }
        } else {
            $error = __('auth.activation_token_used');
        }
    } else {
        // Load customers
        $customers = load_json('storage/customers.json');
        $found = false;
        
        // Find customer with this token
        foreach ($customers as $id => &$customer) {
            if (isset($customer['activation_token']) && $customer['activation_token'] === $token) {
                $found = true;
                
                // Check if token is expired
                if (isset($customer['activation_token_expires'])) {
                    $expires = strtotime($customer['activation_token_expires']);
                    if ($expires < time()) {
                        $error = __('auth.activation_link_expired');
                        break;
                    }
                }
                
                // Check if already activated
                if (isset($customer['activated']) && $customer['activated'] === true) {
                    $message = __('auth.already_activated');
                    $success = true;
                    break;
                }
                
                // Activate the account
                $customer['activated'] = true;
                $customer['activated_at'] = date('Y-m-d H:i:s');
                unset($customer['activation_token']);
                unset($customer['activation_token_expires']);
                
                save_json('storage/customers.json', $customers);
                
                $message = __('auth.activated_successfully');
                $success = true;
                break;
            }
        }
        
        // If not found, token has been used (deleted after successful activation) or is invalid
        if (!$found) {
            $error = __('auth.activation_token_used');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? __('auth.activation_success_title') : __('auth.activation_title'); ?> - OffMeta</title>
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/activate.css">
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <div class="container">
        <?php if ($success): ?>
            <div class="icon"><?php echo icon_check_circle(64, '#27ae60'); ?></div>
            <h1><?php echo __('auth.activation_success'); ?></h1>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <p><?php echo __('auth.you_can_login_now'); ?></p>
            <a href="/auth.php?action=login" class="btn"><?php echo __('auth.login_button'); ?></a>
            <a href="/index.php" class="btn btn-secondary"><?php echo __('back_to_shop'); ?></a>
        <?php else: ?>
            <div class="icon"><?php echo icon_x_circle(64, '#ef4444'); ?></div>
            <h1><?php echo __('auth.activation_error'); ?></h1>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <p><?php echo __('auth.check_email_for_activation'); ?></p>
            <a href="/auth.php?action=register" class="btn"><?php echo __('auth.register_button'); ?></a>
            <a href="/index.php" class="btn btn-secondary"><?php echo __('back_to_shop'); ?></a>
        <?php endif; ?>
    </div>

    <script src="/assets/js/theme-manager.js"></script>
</body>
</html>

