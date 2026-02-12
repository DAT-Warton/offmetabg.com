<?php
/**
 * Email Test Interface
 * Admin only - Test email configuration
 */

session_start();
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';

// Check if admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../auth.php?action=login');
    exit;
}

$message = '';
$error = '';
$emailSender = get_email_sender();

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $test_email = sanitize($_POST['test_email']);
    $test_type = $_POST['test_type'];
    $test_lang = $_POST['test_lang'];
    
    if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        switch ($test_type) {
            case 'welcome':
                $result = $emailSender->sendWelcomeEmail(
                    $test_email, 
                    'Test User', 
                    $test_lang
                );
                break;
                
            case 'password_reset':
                $result = $emailSender->sendPasswordResetEmail(
                    $test_email,
                    'Test User',
                    'test_token_123456789',
                    $test_lang
                );
                break;
                
            case 'order':
                $order_data = [
                    'id' => 'TEST123',
                    'items' => [
                        [
                            'name' => 'Test Product 1',
                            'quantity' => 2,
                            'price' => 29.99
                        ],
                        [
                            'name' => 'Test Product 2',
                            'quantity' => 1,
                            'price' => 49.99
                        ]
                    ],
                    'total' => 109.97,
                    'created' => date('Y-m-d H:i:s')
                ];
                $result = $emailSender->sendOrderConfirmationEmail(
                    $test_email,
                    'Test User',
                    $order_data,
                    $test_lang
                );
                break;
                
            default:
                $result = $emailSender->testConnection($test_email);
        }
        
        if ($result['success']) {
            $message = 'Email sent successfully! Check your inbox.';
        } else {
            $error = 'Failed to send email: ' . $result['message'];
        }
    }
}

// Load email config
$config = require '../config/email-config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Admin</title>
    <link rel="stylesheet" href="assets/css/admin-test-email.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Admin Dashboard</a>
        
        <h1>Email System Test</h1>
        <p class="subtitle">Test your email configuration and send sample emails</p>
        
        <?php if ($config['from_email'] === 'noreply@yourdomain.com'): ?>
        <div class="warning">
            <strong><?php echo icon_alert(18, '#f59e0b'); ?> Configuration Required:</strong> You need to update the sender email in 
            <code>config/email-config.php</code> with your verified domain from MailerSend.
        </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="config-info">
            <h3>Current Configuration</h3>
            <div class="config-row">
                <div class="config-label">API Token:</div>
                <div class="config-value"><?= substr($config['api_token'], 0, 20) ?>...</div>
            </div>
            <div class="config-row">
                <div class="config-label">From Email:</div>
                <div class="config-value"><?= htmlspecialchars($config['from_email']) ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">From Name:</div>
                <div class="config-value"><?= htmlspecialchars($config['from_name']) ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">Site URL:</div>
                <div class="config-value"><?= htmlspecialchars($config['site_url']) ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">Email Verification:</div>
                <div class="config-value"><?= $config['enable_email_verification'] ? icon_check(16, '#27ae60') . ' Enabled' : icon_x(16, '#ef4444') . ' Disabled' ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">Password Reset:</div>
                <div class="config-value"><?= $config['enable_password_reset'] ? icon_check(16, '#27ae60') . ' Enabled' : icon_x(16, '#ef4444') . ' Disabled' ?></div>
            </div>
            <div class="config-row">
                <div class="config-label">Order Confirmation:</div>
                <div class="config-value"><?= $config['enable_order_confirmation'] ? icon_check(16, '#27ae60') . ' Enabled' : icon_x(16, '#ef4444') . ' Disabled' ?></div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Send Test Email</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Test Email Address:</label>
                    <input type="email" name="test_email" required placeholder="your-email@example.com">
                    <div class="note">Enter the email address where you want to receive the test email</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Type:</label>
                        <select name="test_type" required>
                            <option value="basic">Basic Test</option>
                            <option value="welcome">Welcome Email</option>
                            <option value="password_reset">Password Reset</option>
                            <option value="order">Order Confirmation</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Language:</label>
                        <select name="test_lang" required>
                            <option value="bg">Bulgarian (Български)</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="send_test" class="btn">Send Test Email</button>
            </form>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f0f7ff; border-radius: 8px;">
            <h3 style="margin-bottom: 10px;"><?php echo icon_clipboard(20); ?> Setup Instructions:</h3>
            <ol style="line-height: 1.8; padding-left: 20px;">
                <li>Verify your domain in MailerSend dashboard</li>
                <li>Add DNS records (SPF, DKIM, DMARC) provided by MailerSend</li>
                <li>Update <code>from_email</code> in <code>config/email-config.php</code></li>
                <li>Test email delivery using this page</li>
                <li>Check spam folder if email doesn't arrive</li>
            </ol>
        </div>
    </div>
</body>
</html>

