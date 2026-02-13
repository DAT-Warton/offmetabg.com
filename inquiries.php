<?php
/**
 * Inquiries Page - Contact/Support Form
 * Accessible only to registered and activated users
 */

define('CMS_ROOT', __DIR__);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/icons.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);

if (!$is_logged_in) {
    header('Location: auth.php?action=login&redirect=inquiries.php');
    exit;
}

$message = '';
$error = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inquiry'])) {
    $subject = sanitize($_POST['subject'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $message_text = sanitize($_POST['message'] ?? '');
    
    if (empty($subject) || empty($category) || empty($message_text)) {
        $error = __('inquiry.all_fields_required');
    } else {
        // Create new inquiry
        $inquiryId = uniqid('inq_');
        save_inquiry_data([
            'id' => $inquiryId,
            'user_id' => $_SESSION['customer_id'] ?? $_SESSION['admin_user'],
            'username' => $_SESSION['customer_user'] ?? $_SESSION['admin_user'],
            'subject' => $subject,
            'category' => $category,
            'message' => $message_text,
            'status' => 'pending',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ]);
        
        // Send email notification to admin (optional)
        require_once __DIR__ . '/includes/email.php';
        $emailSender = get_email_sender();
        
        $success = true;
        $message = __('inquiry.success_message');
        
        // Clear form
        $_POST = [];
    }
}

// Get user's previous inquiries
$user_inquiries = [];
$all_inquiries = get_inquiries_data();
$current_user = $_SESSION['customer_user'] ?? $_SESSION['admin_user'];

foreach ($all_inquiries as $inquiry) {
    if ($inquiry['username'] === $current_user) {
        $user_inquiries[] = $inquiry;
    }
}

// Sort by date (newest first)
usort($user_inquiries, function($a, $b) {
    return strtotime($b['created']) - strtotime($a['created']);
});

$categories = [
    'general' => __('inquiry.category_general'),
    'order' => __('inquiry.category_order'),
    'product' => __('inquiry.category_product'),
    'payment' => __('inquiry.category_payment'),
    'delivery' => __('inquiry.category_delivery'),
    'technical' => __('inquiry.category_technical'),
    'complaint' => __('inquiry.category_complaint'),
    'other' => __('inquiry.category_other')
];

$status_labels = [
    'pending' => ['label' => __('inquiry.pending'), 'color' => '#fbbf24'],
    'in_progress' => ['label' => __('inquiry.in_progress'), 'color' => '#3b82f6'],
    'resolved' => ['label' => __('inquiry.resolved'), 'color' => '#27ae60'],
    'closed' => ['label' => __('inquiry.closed'), 'color' => '#6b7280']
];
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('inquiry.title'); ?> - <?php echo htmlspecialchars(get_option('site_title', __('site_name'))); ?></title>
    <link rel="stylesheet" href="admin/assets/css/admin.css">
    <link rel="stylesheet" href="admin/assets/css/admin-dashboard-section.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1><?php echo icon_mail(24); ?> <?php echo __('inquiry.title'); ?></h1>
            </div>
            <div class="nav-buttons">
                <a href="index.php" class="btn btn-secondary">← Към началната страница</a>
                <?php if (isset($_SESSION['admin_user'])): ?>
                    <a href="admin/index.php" class="btn btn-primary"><?php echo icon_settings(18); ?> Администрация</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h1><?php echo icon_mail(24); ?> <?php echo __('inquiry.send_inquiry'); ?></h1>
        
        <?php if ($success): ?>
            <div class="message"><?php echo icon_check(16, '#27ae60'); ?> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo icon_x(16, '#ef4444'); ?> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <form method="POST">
                <div class="form-group">
                    <label for="subject"><?php echo __('inquiry.subject'); ?> *</label>
                    <input type="text" id="subject" name="subject" required placeholder="<?php echo __('inquiry.subject'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="category"><?php echo __('inquiry.category'); ?> *</label>
                    <select id="category" name="category" required>
                        <option value=""><?php echo __('inquiry.category'); ?></option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message"><?php echo __('inquiry.message'); ?> *</label>
                    <textarea id="message" name="message" required placeholder="<?php echo __('inquiry.message'); ?>..."></textarea>
                </div>
                
                <button type="submit" name="submit_inquiry" class="btn btn-primary"><?php echo __('inquiry.send_inquiry'); ?></button>
            </form>
        </div>
        
        <h2 style="margin-top: 40px; margin-bottom: 20px;"><?php echo icon_clipboard(24); ?> <?php echo __('inquiry.your_inquiries'); ?></h2>
        
        <div class="inquiries-list">
            <?php if (empty($user_inquiries)): ?>
                <div class="empty-state">
                    <h3><?php echo __('inquiry.no_inquiries_sent'); ?></h3>
                    <p><?php echo __('inquiry.no_inquiries'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($user_inquiries as $inquiry): ?>
                    <?php 
                        $status = $inquiry['status'] ?? 'pending';
                        $status_info = $status_labels[$status] ?? $status_labels['pending'];
                    ?>
                    <div class="inquiry-card">
                        <div class="inquiry-header">
                            <div class="inquiry-title"><?php echo htmlspecialchars($inquiry['subject']); ?></div>
                            <div class="inquiry-status" style="background: <?php echo $status_info['color']; ?>">
                                <?php echo htmlspecialchars($status_info['label']); ?>
                            </div>
                        </div>
                        <div class="inquiry-meta">
                            <?php echo icon_folder(16); ?> <?php echo htmlspecialchars($categories[$inquiry['category']] ?? __('inquiry.category_other')); ?> •
                            <?php echo icon_calendar(16); ?> <?php echo date('d.m.Y H:i', strtotime($inquiry['created'])); ?>
                        </div>
                        <div class="inquiry-message">
                            <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/theme.js"></script>
</body>
</html>

