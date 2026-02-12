<?php
/**
 * Shopping Cart
 */

define('CMS_ROOT', __DIR__);
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/language.php';
require_once CMS_ROOT . '/includes/icons.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize discount
if (!isset($_SESSION['applied_discount'])) {
    $_SESSION['applied_discount'] = null;
}

// Check if showing success page
$show_success = isset($_GET['action']) && $_GET['action'] === 'success' && isset($_SESSION['last_order_id']);

if ($show_success) {
    $orders = load_json('storage/orders.json');
    $order = $orders[$_SESSION['last_order_id']] ?? null;
}

$message = '';
$products = load_json('storage/products.json');

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $product_id = $_POST['product_id'] ?? '';
            if (isset($products[$product_id])) {
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] === $product_id) {
                        $item['quantity']++;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'id' => $product_id,
                        'name' => $products[$product_id]['name'],
                        'price' => $products[$product_id]['price'],
                        'quantity' => 1
                    ];
                }
                $message = __('message.product_added');
            }
            break;
            
        case 'update':
            $product_id = $_POST['product_id'] ?? '';
            $quantity = max(0, intval($_POST['quantity'] ?? 0));
            
            foreach ($_SESSION['cart'] as $key => &$item) {
                if ($item['id'] === $product_id) {
                    if ($quantity == 0) {
                        unset($_SESSION['cart'][$key]);
                    } else {
                        $item['quantity'] = $quantity;
                    }
                    break;
                }
            }
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $message = __('message.cart_updated');
            break;
            
        case 'remove':
            $product_id = $_POST['product_id'] ?? '';
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id'] === $product_id) {
                    unset($_SESSION['cart'][$key]);
                    break;
                }
            }
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $message = __('message.item_removed');
            break;
            
        case 'apply_discount':
            $discount_code = strtoupper(trim($_POST['discount_code'] ?? ''));
            if (empty($discount_code)) {
                $message = icon_x(16, '#ef4444') . ' Моля, въведете промокод!';
                break;
            }
            
            $discounts = load_json('storage/discounts.json');
            $found_discount = null;
            
            // Find discount by code
            foreach ($discounts as $discount) {
                if (strtoupper($discount['code']) === $discount_code) {
                    $found_discount = $discount;
                    break;
                }
            }
            
            if (!$found_discount) {
                $message = icon_x(16, '#ef4444') . ' Невалиден промокод!';
                break;
            }
            
            // Validate discount
            if (!$found_discount['active']) {
                $message = icon_x(16, '#ef4444') . ' Този промокод вече не е активен!';
                break;
            }
            
            // Check dates
            $now = time();
            if (!empty($found_discount['start_date'])) {
                $start = strtotime($found_discount['start_date']);
                if ($now < $start) {
                    $message = icon_x(16, '#ef4444') . ' Този промокод още не е активен!';
                    break;
                }
            }
            if (!empty($found_discount['end_date'])) {
                $end = strtotime($found_discount['end_date']);
                if ($now > $end) {
                    $message = icon_x(16, '#ef4444') . ' Този промокод е изтекъл!';
                    break;
                }
            }
            
            // Check minimum purchase
            $subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $_SESSION['cart']));
            if ($found_discount['min_purchase'] > 0 && $subtotal < $found_discount['min_purchase']) {
                $message = icon_x(16, '#ef4444') . ' Минимална поръчка за този промокод: €' . number_format($found_discount['min_purchase'], 2);
                break;
            }
            
            // Check max uses
            if ($found_discount['max_uses'] > 0 && $found_discount['used_count'] >= $found_discount['max_uses']) {
                $message = icon_x(16, '#ef4444') . ' Този промокод е достигнал максималния брой използвания!';
                break;
            }
            
            // Check first purchase only (if user system is implemented)
            if ($found_discount['first_purchase_only']) {
                // TODO: Check if user has previous orders
                // For now, allow it
            }
            
            // Apply discount
            $_SESSION['applied_discount'] = $found_discount;
            $message = icon_check(16, '#10b981') . ' Промокод приложен успешно: ' . $found_discount['code'];
            break;
            
        case 'remove_discount':
            $_SESSION['applied_discount'] = null;
            $message = icon_check(16, '#10b981') . ' Промокодът е премахнат!';
            break;
            
        case 'checkout':
            if (!isset($_SESSION['customer_user']) && !isset($_SESSION['admin_user'])) {
                header('Location: ' . url('auth.php?action=login'));
                exit;
            }
            
            // Get shipping and payment details from POST
            $first_name = sanitize($_POST['first_name'] ?? '');
            $last_name = sanitize($_POST['last_name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $postal_code = sanitize($_POST['postal_code'] ?? '');
            $courier = sanitize($_POST['courier'] ?? 'econt');
            $payment_method = sanitize($_POST['payment_method'] ?? 'cod');
            $receiver_name = sanitize($_POST['receiver_name'] ?? '');
            $receiver_phone = sanitize($_POST['receiver_phone'] ?? '');
            $delivery_notes = sanitize($_POST['delivery_notes'] ?? '');
            
            // Validate required fields
            if (empty($first_name) || empty($last_name) || empty($phone) || empty($address) || empty($city)) {
                $message = 'Please fill in all required fields!';
                break;
            }
            
            // Save order
            $orders = load_json('storage/orders.json');
            $order_id = uniqid('order_');
            $orders[$order_id] = [
                'id' => $order_id,
                'customer' => [
                    'user' => $_SESSION['customer_user'] ?? $_SESSION['admin_user'],
                    'name' => $first_name . ' ' . $last_name,
                    'email' => $email,
                    'phone' => $phone
                ],
                'shipping' => [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'address' => $address,
                    'city' => $city,
                    'postal_code' => $postal_code,
                    'receiver_name' => $receiver_name ?: ($first_name . ' ' . $last_name),
                    'receiver_phone' => $receiver_phone ?: $phone,
                    'courier' => $courier,
                    'delivery_notes' => $delivery_notes
                ],
                'payment' => [
                    'method' => $payment_method,
                    'cod' => ($payment_method === 'cod'),
                    'status' => 'pending'
                ],
                'items' => $_SESSION['cart'],
                'subtotal' => array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $_SESSION['cart'])),
                'discount' => $_SESSION['applied_discount'] ?? null,
                'discount_amount' => 0,
                'total' => array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $_SESSION['cart'])),
                'status' => 'pending',
                'created' => date('Y-m-d H:i:s')
            ];
            // Calculate and apply discount
            if (isset($_SESSION['applied_discount']) && $_SESSION['applied_discount']) {
                $discount = $_SESSION['applied_discount'];
                $discount_amount = 0;
                
                switch ($discount['type']) {
                    case 'percentage':
                        $discount_amount = $orders[$order_id]['subtotal'] * ($discount['value'] / 100);
                        break;
                    case 'fixed':
                        $discount_amount = min($discount['value'], $orders[$order_id]['subtotal']);
                        break;
                    case 'free_shipping':
                        // Shipping cost would be added here in real implementation
                        $discount_amount = 0; // Placeholder
                        break;
                }
                
                $orders[$order_id]['discount_amount'] = $discount_amount;
                $orders[$order_id]['total'] -= $discount_amount;
                
                // Increment used count
                $discounts = load_json('storage/discounts.json');
                if (isset($discounts[$discount['id']])) {
                    $discounts[$discount['id']]['used_count']++;
                    save_json('storage/discounts.json', $discounts);
                }
            }
            
            save_json('storage/orders.json', $orders);
            
            // Clear applied discount
            $_SESSION['applied_discount'] = null;
            
            // Send order confirmation email (if email system is configured)
            try {
                require_once CMS_ROOT . '/includes/email.php';
                require_once CMS_ROOT . '/config/email-config.php';
                
                $emailSender = new MailerSend\EmailSender(MAILERSEND_API_KEY);
                $emailSender->sendOrderConfirmationEmail(
                    $email,
                    $first_name . ' ' . $last_name,
                    $orders[$order_id],
                    current_lang()
                );
            } catch (Exception $e) {
                // Email sending failed, but order is saved
            }
            
            $_SESSION['cart'] = [];
            $_SESSION['last_order_id'] = $order_id;
            header('Location: cart.php?action=success');
            exit;
            break;
    }
}

// Calculate totals
$subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $_SESSION['cart']));
$cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

// Calculate discount
$discount_amount = 0;
if (isset($_SESSION['applied_discount']) && $_SESSION['applied_discount']) {
    $discount = $_SESSION['applied_discount'];
    switch ($discount['type']) {
        case 'percentage':
            $discount_amount = $subtotal * ($discount['value'] / 100);
            break;
        case 'fixed':
            $discount_amount = min($discount['value'], $subtotal);
            break;
        case 'free_shipping':
            $discount_amount = 0; // Would affect shipping cost
            break;
    }
}

$total = $subtotal - $discount_amount;

$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('cart_page.title'); ?></title>
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-primary, #f9fafb);
            color: var(--text-primary, #1f2937);
        }
        header {
            background: var(--bg-secondary, white);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px 0;
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo h1 {
            font-size: 28px;
            color: var(--primary, #667eea);
        }
        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-secondary {
            background: var(--bg-secondary, #f5f5f5);
            color: var(--text-primary, #333);
            border: 2px solid var(--border-color, #e0e0e0);
        }
        .btn-secondary:hover {
            border-color: var(--primary, #667eea);
            color: var(--primary, #667eea);
        }
        .theme-toggle,
        .lang-toggle {
            background: var(--bg-secondary, white);
            color: var(--primary, #667eea);
            border: 2px solid var(--primary, #667eea);
            padding: 8px 12px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .theme-toggle:hover,
        .lang-toggle:hover {
            background: var(--primary, #667eea);
            color: white;
            transform: scale(1.05);
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .message {
            background: var(--success-bg, #d4edda);
            border: 1px solid var(--success-border, #c3e6cb);
            color: var(--success-text, #155724);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .cart-items {
            background: var(--bg-secondary, white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h2 {
            color: var(--text-primary, #1f2937);
            margin-bottom: 20px;
        }
        h3 {
            color: var(--text-primary, #374151);
            margin: 25px 0 15px;
            font-size: 16px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-info {
            flex: 1;
        }
        .item-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .item-price {
            color: var(--primary, #667eea);
            font-weight: 600;
        }
        .item-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .quantity-input {
            width: 80px;
            padding: 8px;
            border: 1px solid var(--border-color, #d1d5db);
            border-radius: 6px;
            text-align: center;
            background: var(--bg-secondary, white);
            color: var(--text-primary, #333);
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-update {
            background: var(--primary, #667eea);
            color: white;
        }
        .btn-update:hover {
            background: var(--primary-dark, #5568d3);
        }
        .btn-remove {
            background: var(--danger, #ef4444);
            color: white;
        }
        .btn-remove:hover {
            background: var(--danger-dark, #dc2626);
        }
        .cart-summary {
            background: var(--bg-secondary, white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .summary-row.discount {
            color: var(--success, #10b981);
            font-weight: 600;
        }
        .summary-total {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary, #667eea);
            border-top: 2px solid var(--border-color, #e5e7eb);
            padding-top: 15px;
            margin-top: 15px;
        }
        .btn-checkout {
            width: 100%;
            padding: 15px;
            background: var(--success, #10b981);
            color: white;
            font-size: 18px;
            margin-top: 20px;
        }
        .btn-checkout:hover {
            background: var(--success-dark, #059669);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-secondary, white);
            color: var(--text-primary, #333);
            text-decoration: none;
            border: 2px solid var(--border-color, #e0e0e0);
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: var(--bg-primary, #f5f5f5);
            border-color: var(--primary, #667eea);
            color: var(--primary, #667eea);
            transform: translateY(-2px);
        }
        .btn-continue-shopping {\n            display: inline-block;\n            padding: 15px 30px;\n            text-decoration: none;\n            border-radius: 8px;\n            background: var(--primary, #667eea);\n            color: white;\n            font-weight: 600;\n            transition: all 0.3s ease;\n        }\n        .btn-continue-shopping:hover {\n            background: var(--primary-dark, #5568d3);\n            transform: translateY(-2px);\n            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);\n        }\n        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-secondary, white);
            border-radius: 12px;
        }
        .empty-cart h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--text-secondary, #6b7280);
        }
        
        /* Promo Code */
        .promo-code-box {
            background: var(--bg-primary, #f9fafb);
            border: 2px dashed var(--border-color, #d1d5db);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .promo-code-applied {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--success-bg, #d1fae5);
            border: 1px solid var(--success, #10b981);
            padding: 12px 15px;
            border-radius: 6px;
        }
        .promo-code-applied strong {
            font-weight: 700;
            color: var(--success-dark, #065f46);
            font-size: 15px;
        }
        .promo-code-applied p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: var(--success, #059669);
        }
        .promo-code-form {
            display: flex;
            gap: 10px;
        }
        .promo-code-form input {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--border-color, #d1d5db);
            border-radius: 6px;
            text-transform: uppercase;
            font-weight: 600;
            background: var(--bg-secondary, white);
            color: var(--text-primary, #333);
        }
        .btn-apply-promo {
            background: var(--success, #10b981);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-remove-promo {
            background: transparent;
            border: none;
            color: var(--danger, #dc2626);
            cursor: pointer;
            font-size: 20px;
            padding: 5px;
        }
        
        /* Order Success */
        .order-success {
            text-align: center;
            padding: 50px 30px;
        }
        .order-success h2 {
            color: var(--success, #10b981);
            margin-bottom: 15px;
        }
        .order-success p {
            font-size: 18px;
            color: var(--text-secondary, #6b7280);
            margin-bottom: 30px;
        }
        .order-success strong {
            color: var(--primary, #667eea);
        }
        .order-details-box {
            background: var(--bg-primary, #f9fafb);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        .order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .order-grid p {
            color: var(--text-secondary, #6b7280);
            font-size: 14px;
            margin-bottom: 5px;
        }
        .order-grid strong {
            font-weight: 600;
            color: var(--text-primary, #1f2937);
        }
        .order-divider {
            border-top: 1px solid var(--border-color, #e5e7eb);
            padding-top: 15px;
            margin-top: 15px;
        }
        .order-divider p strong.total-amount {\n            color: var(--primary, #667eea);\n            font-size: 18px;\n        }\n        .info-box {
            background: var(--warning-bg, #fef3c7);
            border: 1px solid var(--warning, #fbbf24);
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            text-align: left;
        }
        .info-box p {
            color: var(--warning-dark, #92400e);
            font-size: 14px;
        }
        .info-box strong {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        /* Checkout Form */
        .checkout-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color, #e5e7eb);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-grid-2-1 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-primary, #374151);
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color, #d1d5db);
            border-radius: 6px;
            background: var(--bg-secondary, white);
            color: var(--text-primary, #333);
            font-family: inherit;
        }
        .form-hint {
            margin-top: 5px;
            font-size: 13px;
            color: var(--text-secondary, #6b7280);
        }
        .form-terms {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: var(--text-secondary, #6b7280);
        }
        .form-terms a {
            color: var(--primary, #667eea);
        }
    </style>
</head>
<body>
    <?php if ($show_success && $order): ?>
        <!-- Order Success Page -->
        <header>
            <div class="header-container">
                <div class="logo">
                    <h1><?php echo icon_check_circle(32, '#10b981'); ?> Order Confirmed!</h1>
                </div>
                <div class="nav-buttons">
                    <button type="button" id="themeToggle" class="btn theme-toggle" title="<?php echo __('theme.switch_to_dark'); ?>"><?php echo icon_moon(18); ?></button>
                    <a href="?lang=<?php echo opposite_lang(); ?>" class="btn lang-toggle" title="Switch Language">
                        <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
                    </a>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="cart-items order-success">
                <div style="font-size: 64px; margin-bottom: 20px;"><?php echo icon_check_circle(64, '#10b981'); ?></div>
                <h2><?php echo __('order.order_placed'); ?></h2>
                <p>
                    <?php echo __('order.thank_you'); ?>. <?php echo __('order.order_number'); ?>: <strong><?php echo htmlspecialchars($order['id']); ?></strong>
                </p>
                
                <div class="order-details-box">
                    <h3><?php echo icon_package(24); ?> <?php echo __('order.order_details'); ?></h3>
                    
                    <div class="order-grid">
                        <div>
                            <p><?php echo __('order.delivery_to'); ?>:</p>
                            <strong><?php echo htmlspecialchars($order['shipping']['receiver_name']); ?></strong>
                            <p><?php echo htmlspecialchars($order['shipping']['address']); ?></p>
                            <p><?php echo htmlspecialchars($order['shipping']['city']); ?> <?php echo htmlspecialchars($order['shipping']['postal_code'] ?? ''); ?></p>
                        </div>
                        
                        <div>
                            <p><?php echo __('order.contact'); ?>:</p>
                            <strong><?php echo icon_phone(16); ?> <?php echo htmlspecialchars($order['shipping']['receiver_phone']); ?></strong>
                            <?php if (!empty($order['customer']['email'])): ?>
                                <p><?php echo icon_mail(16); ?> <?php echo htmlspecialchars($order['customer']['email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="order-divider">
                        <p><?php echo icon_truck(16); ?> <?php echo __('order.courier'); ?>: <strong><?php echo strtoupper($order['shipping']['courier']); ?></strong></p>
                        <p><?php echo icon_dollar(16); ?> <?php echo __('order.payment'); ?>: <strong><?php echo $order['payment']['cod'] ? __('payment.cod') : ucfirst($order['payment']['method']); ?></strong></p>
                        <p><?php echo icon_dollar(16); ?> <?php echo __('cart_page.total'); ?>: <strong class="total-amount">€<?php echo number_format($order['total'], 2); ?></strong></p>
                    </div>
                </div>
                
                <div class="info-box">
                    <p><strong>⏱️ <?php echo __('order.what_next'); ?></strong></p>
                    <p>
                        <?php echo __('order.what_next_text'); ?> <?php echo strtoupper($order['shipping']['courier']); ?> <?php echo __('cart_page.courier_delivery_time'); ?>.
                    </p>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="/" class="btn-continue-shopping">
                        ← <?php echo __('cart_page.continue_shopping'); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Regular Cart Page -->
    <header>
        <div class="header-container">
            <div class="logo">
                <h1><?php echo icon_cart(24); ?> <?php echo __('cart_page.title'); ?></h1>
            </div>
            <div class="nav-buttons">
                <!-- Theme Toggle -->
                <button type="button" id="themeToggle" class="btn theme-toggle" title="<?php echo __('theme.switch_to_dark'); ?>"><?php echo icon_moon(18); ?></button>
                
                <!-- Language Toggle -->
                <a href="?lang=<?php echo opposite_lang(); ?>" class="btn lang-toggle" title="Switch Language">
                    <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
                </a>
                
                <!-- Inquiries -->
                <a href="inquiries.php" class="btn btn-secondary"><?php echo icon_mail(18); ?> <?php echo __('inquiry.title'); ?></a>
                
                <!-- Home -->
                <a href="index.php" class="btn btn-secondary">← <?php echo __('home'); ?></a>
                
                <?php if ($is_logged_in): ?>
                    <a href="auth.php?logout=1" class="btn btn-secondary"><?php echo __('logout'); ?></a>
                <?php else: ?>
                    <a href="auth.php?action=login" class="btn btn-secondary"><?php echo __('login'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="/" class="btn-back">← <?php echo __('cart_page.continue_shopping'); ?></a>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <h3><?php echo __('cart_page.empty'); ?></h3>
                <p><?php echo __('cart_page.empty_message'); ?></p>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <h2><?php echo __('cart_page.cart_items'); ?> (<?php echo $cart_count; ?>)</h2>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="cart-item">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">€<?php echo number_format($item['price'], 2); ?> <?php echo __('cart_page.each'); ?></div>
                        </div>
                        <div class="item-actions">
                            <form method="POST" style="display: inline-flex; gap: 10px; align-items: center;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" class="quantity-input">
                                <button type="submit" class="btn btn-update"><?php echo __('cart_page.update'); ?></button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                <button type="submit" class="btn btn-remove"><?php echo __('cart_page.remove'); ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h2><?php echo __('order.order_summary'); ?></h2>
                
                <!-- Promo Code Section -->
                <div class="promo-code-box">
                    <h3><?php echo icon_gift(24); ?> Имате ли промокод?</h3>
                    <?php if (isset($_SESSION['applied_discount']) && $_SESSION['applied_discount']): ?>
                        <div class="promo-code-applied">
                            <div>
                                <strong><?php echo icon_check(16, '#10b981'); ?> <?php echo htmlspecialchars($_SESSION['applied_discount']['code']); ?></strong>
                                <p><?php echo htmlspecialchars($_SESSION['applied_discount']['description']); ?></p>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="remove_discount">
                                <button type="submit" class="btn-remove-promo" title="Премахни промокод"><?php echo icon_trash(18, '#ef4444'); ?></button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="promo-code-form">
                            <input type="hidden" name="action" value="apply_discount">
                            <input type="text" name="discount_code" placeholder="Въведете промокод" required>
                            <button type="submit" class="btn btn-apply-promo">Приложи</button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="summary-row">
                    <span><?php echo __('cart_page.subtotal'); ?>:</span>
                    <span>€<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <?php if ($discount_amount > 0): ?>
                    <div class="summary-row discount">
                        <span><?php echo icon_dollar(16, '#10b981'); ?> Отстъпка (<?php echo htmlspecialchars($_SESSION['applied_discount']['code']); ?>):</span>
                        <span>-€<?php echo number_format($discount_amount, 2); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row summary-total">
                    <span><?php echo __('cart_page.total'); ?>:</span>
                    <span>€<?php echo number_format($total, 2); ?></span>
                </div>
                
                <?php if ($is_logged_in): ?>
                    <!-- Comprehensive Checkout Form -->
                    <h2 class="checkout-section"><?php echo __('checkout.shipping_details'); ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="checkout">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><?php echo __('checkout.first_name'); ?> *</label>
                                <input type="text" name="first_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo __('checkout.last_name'); ?> *</label>
                                <input type="text" name="last_name" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.phone'); ?> *</label>
                            <input type="tel" name="phone" class="form-input" required placeholder="+359 888 123 456">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.email'); ?></label>
                            <input type="email" name="email" class="form-input" placeholder="your@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.address'); ?> *</label>
                            <input type="text" name="address" class="form-input" required placeholder="<?php echo __('checkout.address_placeholder'); ?>">
                        </div>
                        
                        <div class="form-grid-2-1">
                            <div class="form-group">
                                <label class="form-label"><?php echo __('checkout.city'); ?> *</label>
                                <input type="text" name="city" class="form-input" required placeholder="<?php echo __('checkout.city_placeholder'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo __('checkout.postal_code'); ?></label>
                                <input type="text" name="postal_code" class="form-input" placeholder="1000">
                            </div>
                        </div>
                        
                        <h3><?php echo __('checkout.receiver_info'); ?> (<?php echo __('checkout.if_different'); ?>)</h3>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.receiver_name'); ?></label>
                            <input type="text" name="receiver_name" class="form-input" placeholder="<?php echo __('checkout.receiver_name_placeholder'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.receiver_phone'); ?></label>
                            <input type="tel" name="receiver_phone" class="form-input" placeholder="<?php echo __('checkout.receiver_phone_placeholder'); ?>">
                        </div>
                        
                        <h3><?php echo icon_truck(24); ?> <?php echo __('checkout.delivery_payment'); ?></h3>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.courier_service'); ?> *</label>
                            <select name="courier" class="form-select" required>
                                <option value="econt"><?php echo icon_package(16); ?> <?php echo __('courier.econt'); ?> (<?php echo __('courier.econt_description'); ?>)</option>
                                <option value="speedy"><?php echo icon_zap(16); ?> <?php echo __('courier.speedy'); ?> (<?php echo __('courier.speedy_description'); ?>)</option>
                            </select>
                            <p class="form-hint"><?php echo __('courier.info'); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.payment_method'); ?> *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cod"><?php echo icon_dollar(16); ?> <?php echo __('payment.cod'); ?></option>
                                <option value="bank_transfer"><?php echo icon_bank(16); ?> <?php echo __('payment.bank_transfer'); ?></option>
                                <option value="card"><?php echo icon_credit_card(16); ?> <?php echo __('payment.card'); ?></option>
                            </select>
                            <p class="form-hint"><?php echo __('payment.cod_info'); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo __('checkout.delivery_notes'); ?></label>
                            <textarea name="delivery_notes" class="form-textarea" rows="3" placeholder="<?php echo __('checkout.delivery_notes_placeholder'); ?>"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-checkout">
                            <?php echo __('cart_page.complete_order'); ?> - €<?php echo number_format($total, 2); ?>
                        </button>
                        
                        <p class="form-terms">
                            <?php echo __('cart_page.terms_agreement'); ?>
                        </p>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn btn-checkout" disabled>
                        <?php echo __('cart_page.login_to_checkout'); ?>
                    </button>
                    <p class="form-terms">
                        <?php echo __('cart_page.must_login'); ?>. <a href="auth.php?action=login"><?php echo __('cart_page.login_here'); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; // End cart vs success page ?>

    <script src="assets/js/theme.js"></script>
</body>
</html>
