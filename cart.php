<?php
/**
 * Shopping Cart
 */

define('CMS_ROOT', __DIR__);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$error = '';
$show_success = false;
$order = null;

$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
$current_user = $_SESSION['customer_user'] ?? $_SESSION['admin_user'] ?? '';

function is_discount_usable($discount, $subtotal, $current_user) {
    if (!$discount || !($discount['active'] ?? false)) {
        return false;
    }

    $now = time();
    if (!empty($discount['start_date']) && strtotime($discount['start_date']) > $now) {
        return false;
    }
    if (!empty($discount['end_date']) && strtotime($discount['end_date']) < $now) {
        return false;
    }

    if (!empty($discount['min_purchase']) && $subtotal < (float)$discount['min_purchase']) {
        return false;
    }

    $maxUses = (int)($discount['max_uses'] ?? 0);
    $usedCount = (int)($discount['used_count'] ?? 0);
    if ($maxUses > 0 && $usedCount >= $maxUses) {
        return false;
    }

    if (!empty($discount['first_purchase_only']) && $current_user !== '') {
        if (db_enabled()) {
            $rows = db_table('orders')->all();
            foreach ($rows as $row) {
                $notes = parse_json_field($row['notes'] ?? '') ?? [];
                $orderUser = $notes['customer']['user'] ?? '';
                if ($orderUser === $current_user) {
                    return false;
                }
            }
        } else {
            $orders = load_json('storage/orders.json');
            foreach ($orders as $order) {
                $orderUser = $order['customer']['user'] ?? '';
                if ($orderUser === $current_user) {
                    return false;
                }
            }
        }
    }

    return true;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = $_SESSION['cart'];

// Handle order success display
if (!empty($_GET['success']) && !empty($_SESSION['last_order_id'])) {
    $orders = get_orders_data();
    $orderId = $_SESSION['last_order_id'];
    if (isset($orders[$orderId])) {
        $order = $orders[$orderId];
        $show_success = true;
    }
    unset($_SESSION['last_order_id']);
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $products = get_products_data();

    if ($action === 'add') {
        $productId = sanitize($_POST['product_id'] ?? '');
        $product = $products[$productId] ?? null;

        if (!$product || ($product['status'] ?? 'published') !== 'published') {
            $error = __('product.not_found') ?? 'Product not found.';
        } else {
            $stock = (int)($product['stock'] ?? 0);
            $currentQty = (int)($cart[$productId]['quantity'] ?? 0);

            if ($stock > 0 && $currentQty < $stock) {
                $cart[$productId] = [
                    'id' => $productId,
                    'name' => $product['name'] ?? '',
                    'price' => (float)($product['price'] ?? 0),
                    'quantity' => $currentQty + 1
                ];
                $_SESSION['cart'] = $cart;
                $message = __('cart_page.added_to_cart');
            } else {
                $error = __('product.out_of_stock') ?? 'Out of stock.';
            }
        }
    }

    if ($action === 'update') {
        $productId = sanitize($_POST['product_id'] ?? '');
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));

        if (isset($cart[$productId])) {
            if ($quantity === 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['quantity'] = $quantity;
            }
            $_SESSION['cart'] = $cart;
        }
    }

    if ($action === 'remove') {
        $productId = sanitize($_POST['product_id'] ?? '');
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $_SESSION['cart'] = $cart;
        }
    }

    if ($action === 'apply_discount') {
        $code = strtoupper(trim($_POST['discount_code'] ?? ''));
        $discounts = get_discounts_data();
        $discount = null;
        foreach ($discounts as $disc) {
            if (strtoupper($disc['code'] ?? '') === $code) {
                $discount = $disc;
                break;
            }
        }

        $currentSubtotal = 0;
        foreach ($cart as $item) {
            $currentSubtotal += ((float)$item['price']) * (int)$item['quantity'];
        }

        if (!is_discount_usable($discount, $currentSubtotal, $current_user)) {
            $error = __('discount.invalid_code') ?? 'Invalid promo code.';
        } else {
            $_SESSION['applied_discount'] = $discount;
        }
    }

    if ($action === 'remove_discount') {
        unset($_SESSION['applied_discount']);
    }

    if ($action === 'checkout') {
        if (!$is_logged_in) {
            $error = __('cart_page.must_login');
        } elseif (empty($cart)) {
            $error = __('cart_page.empty');
        } else {
            $firstName = sanitize($_POST['first_name'] ?? '');
            $lastName = sanitize($_POST['last_name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $postalCode = sanitize($_POST['postal_code'] ?? '');
            $receiverName = sanitize($_POST['receiver_name'] ?? '');
            $receiverPhone = sanitize($_POST['receiver_phone'] ?? '');
            $courier = sanitize($_POST['courier'] ?? '');
            $paymentMethod = sanitize($_POST['payment_method'] ?? '');
            $deliveryNotes = sanitize($_POST['delivery_notes'] ?? '');

            if ($firstName === '' || $lastName === '' || $phone === '' || $address === '' || $city === '' || $courier === '' || $paymentMethod === '') {
                $error = __('auth.all_fields_required');
            } else {
                if ($receiverName === '') {
                    $receiverName = $firstName . ' ' . $lastName;
                }
                if ($receiverPhone === '') {
                    $receiverPhone = $phone;
                }

                $items = array_values($cart);
                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += ((float)$item['price']) * (int)$item['quantity'];
                }

                $discountAmount = 0;
                $discount = $_SESSION['applied_discount'] ?? null;
                if (is_discount_usable($discount, $subtotal, $current_user)) {
                    $type = $discount['type'] ?? 'percentage';
                    $value = (float)($discount['value'] ?? 0);

                    if ($type === 'percentage') {
                        $discountAmount = $subtotal * ($value / 100);
                    } elseif ($type === 'fixed') {
                        $discountAmount = $value;
                    } else {
                        $discountAmount = 0;
                    }

                } else {
                    $discount = null;
                    $discountAmount = 0;
                }

                $total = max(0, $subtotal - $discountAmount);

                $orderId = uniqid('order_');
                $orderData = [
                    'id' => $orderId,
                    'customer' => [
                        'user' => $current_user,
                        'name' => trim($firstName . ' ' . $lastName),
                        'email' => $email,
                        'phone' => $phone
                    ],
                    'shipping' => [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'address' => $address,
                        'city' => $city,
                        'postal_code' => $postalCode,
                        'receiver_name' => $receiverName,
                        'receiver_phone' => $receiverPhone,
                        'courier' => $courier,
                        'delivery_notes' => $deliveryNotes
                    ],
                    'payment' => [
                        'method' => $paymentMethod,
                        'cod' => $paymentMethod === 'cod',
                        'status' => 'pending'
                    ],
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                    'status' => 'pending',
                    'created' => date('Y-m-d H:i:s')
                ];

                save_order_data($orderData);

                if ($discount) {
                    if (!empty($discount['id'])) {
                        update_discount_usage($discount['id']);
                    }
                }

                $_SESSION['cart'] = [];
                unset($_SESSION['applied_discount']);

                if (!empty($email)) {
                    try {
                        $emailSender = get_email_sender();
                        $emailSender->sendOrderConfirmationEmail($email, $firstName, $orderData, current_lang());
                    } catch (Exception $e) {
                        error_log('Order email failed: ' . $e->getMessage());
                    }
                }

                $_SESSION['last_order_id'] = $orderId;
                header('Location: cart.php?success=1');
                exit;
            }
        }
    }
}

// Calculate totals
$cart = $_SESSION['cart'] ?? [];
$cart_count = array_sum(array_column($cart, 'quantity'));
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += ((float)$item['price']) * (int)$item['quantity'];
}

$discount_amount = 0;
$applied_discount = $_SESSION['applied_discount'] ?? null;
if (is_discount_usable($applied_discount, $subtotal, $current_user)) {
    $type = $applied_discount['type'] ?? 'percentage';
    $value = (float)($applied_discount['value'] ?? 0);
    if ($type === 'percentage') {
        $discount_amount = $subtotal * ($value / 100);
    } elseif ($type === 'fixed') {
        $discount_amount = $value;
    }
} else {
    unset($_SESSION['applied_discount']);
}

$total = max(0, $subtotal - $discount_amount);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('cart_page.title'); ?> - <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></title>
    <link rel="stylesheet" href="assets/css/dark-theme.css" id="dark-theme-style" disabled>
    <link rel="stylesheet" href="assets/css/cart.css">
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
                    <?php
                        $receiverName = $order['shipping']['receiver_name'] ?? ($order['shipping']['first_name'] ?? '');
                        $receiverPhone = $order['shipping']['receiver_phone'] ?? ($order['shipping']['phone'] ?? '');
                        $courier = $order['shipping']['courier'] ?? '';
                    ?>
                    
                    <div class="order-grid">
                        <div>
                            <p><?php echo __('order.delivery_to'); ?>:</p>
                            <strong><?php echo htmlspecialchars($receiverName); ?></strong>
                            <p><?php echo htmlspecialchars($order['shipping']['address']); ?></p>
                            <p><?php echo htmlspecialchars($order['shipping']['city']); ?> <?php echo htmlspecialchars($order['shipping']['postal_code'] ?? ''); ?></p>
                        </div>
                        
                        <div>
                            <p><?php echo __('order.contact'); ?>:</p>
                            <strong><?php echo icon_phone(16); ?> <?php echo htmlspecialchars($receiverPhone); ?></strong>
                            <?php $customerEmail = $order['customer']['email'] ?? ($order['email'] ?? ''); ?>
                            <?php if (!empty($customerEmail)): ?>
                                <p><?php echo icon_mail(16); ?> <?php echo htmlspecialchars($customerEmail); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="order-divider">
                        <p><?php echo icon_truck(16); ?> <?php echo __('order.courier'); ?>: <strong><?php echo strtoupper($courier); ?></strong></p>
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

