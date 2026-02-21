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
require_once __DIR__ . '/includes/discount-engine.php'; // Professional Discount Engine

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$error = '';
$show_success = false;
$order = null;

$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
$current_user = $_SESSION['customer_user'] ?? $_SESSION['admin_user'] ?? '';

// Get customer data for discount calculations
$customer = null;
if (isset($_SESSION['customer_id'])) {
    $customers = get_customers_data();
    $customer = $customers[$_SESSION['customer_id']] ?? null;
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
        
        // Use professional discount engine for validation
        $validation = validate_discount_code($code, $cart, $customer);
        
        if ($validation['valid']) {
            $_SESSION['applied_discount_code'] = $code;
            $message = 'Промо кодът е приложен успешно!';
        } else {
            $error = $validation['message'];
        }
    }

    if ($action === 'remove_discount') {
        unset($_SESSION['applied_discount_code']);
        $message = 'Промо кодът е премахнат';
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
                
                // Use professional discount engine
                $discount_code = $_SESSION['applied_discount_code'] ?? null;
                $discount_result = calculate_cart_discounts($items, $discount_code, $customer);
                
                $subtotal = $discount_result['subtotal'];
                $discountAmount = $discount_result['total_discount'];
                $total = $discount_result['final_total'];
                $applied_discounts = $discount_result['applied_discounts'];

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
                    'applied_discounts' => $applied_discounts, // Store all applied discounts
                    'total' => $total,
                    'status' => 'pending',
                    'created' => date('Y-m-d H:i:s')
                ];

                save_order_data($orderData);

                // Update usage count for all applied discounts
                foreach ($applied_discounts as $discount) {
                    if (!empty($discount['id'])) {
                        update_discount_usage($discount['id']);
                    }
                }

                // Clear cart and discount code
                $_SESSION['cart'] = [];
                unset($_SESSION['applied_discount_code']);

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
// Calculate totals using professional discount engine
$cart = $_SESSION['cart'] ?? [];
$cart_count = array_sum(array_column($cart, 'quantity'));

$discount_code = $_SESSION['applied_discount_code'] ?? null;
$discount_result = calculate_cart_discounts($cart, $discount_code, $customer);

$subtotal = $discount_result['subtotal'];
$discount_amount = $discount_result['total_discount'];
$total = $discount_result['final_total'];
$applied_discounts = $discount_result['applied_discounts'];

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title><?php echo __('cart_page.title'); ?> - <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></title>
    <script>
        // Apply theme immediately from localStorage to prevent flash
        (function() {
            const storedTheme = localStorage.getItem('offmeta_theme');
            if (storedTheme) {
                document.documentElement.setAttribute('data-theme', storedTheme);
            }
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/themes.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>">
    <?php echo get_custom_theme_css(); ?>
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
<?php if ($show_success && $order): ?>
        <!-- Order Success Page -->
        <header>
            <div class="header-container">
                <div class="logo">
                    <h1><?php echo icon_check_circle(32, '#10b981'); ?> Order Confirmed!</h1>
                </div>
                <div class="nav-buttons">
                    <a href="?lang=<?php echo opposite_lang(); ?>"class="btn lang-toggle"title="Switch Language">
                        <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
                    </a>
                    <a href="inquiries.php"class="btn btn-secondary"><?php echo icon_mail(18); ?> <?php echo __('inquiry.title'); ?></a>
                    <a href="/"class="btn btn-secondary"><?php echo __('home'); ?></a>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="cart-items order-success">
                <div class="success-icon-container"><?php echo icon_check_circle(64, '#10b981'); ?></div>
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
                
                <div class="order-actions-container">
                    <a href="/"class="btn-continue-shopping">
                        <?php echo __('cart_page.continue_shopping'); ?>
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
                <!-- Language Toggle -->
                <a href="?lang=<?php echo opposite_lang(); ?>"class="btn lang-toggle"title="Switch Language">
                    <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
                </a>
                
                <!-- Inquiries -->
                <a href="inquiries.php"class="btn btn-secondary"><?php echo icon_mail(18); ?> <?php echo __('inquiry.title'); ?></a>
                
                <!-- Home -->
                <a href="/"class="btn btn-secondary"><?php echo __('home'); ?></a>
                
                <?php if ($is_logged_in): ?>
                    <a href="/auth.php?logout=1"class="btn btn-secondary"><?php echo __('logout'); ?></a>
                <?php else: ?>
                    <a href="/auth.php?action=login"class="btn btn-secondary"><?php echo __('login'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="/"class="btn-back"><?php echo __('cart_page.continue_shopping'); ?></a>
        
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
                            <form method="POST"class="quantity-update-form">
                                <input type="hidden"name="action"value="update">
                                <input type="hidden"name="product_id"value="<?php echo htmlspecialchars($item['id']); ?>">
                                <input type="number"name="quantity"value="<?php echo $item['quantity']; ?>"min="0"class="quantity-input">
                                <button type="submit"class="btn btn-update"><?php echo __('cart_page.update'); ?></button>
                            </form>
                            <form method="POST"class="remove-item-form">
                                <input type="hidden"name="action"value="remove">
                                <input type="hidden"name="product_id"value="<?php echo htmlspecialchars($item['id']); ?>">
                                <button type="submit"class="btn btn-remove"><?php echo __('cart_page.remove'); ?></button>
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
                    <?php if (isset($_SESSION['applied_discount_code']) && $_SESSION['applied_discount_code']): ?>
                        <div class="promo-code-applied">
                            <?php if (!empty($applied_discounts)): ?>
                                <?php foreach ($applied_discounts as $applied_disc): ?>
                                    <div style="margin-bottom: 8px;">
                                        <strong><?php echo icon_check(16, '#10b981'); ?> <?php echo htmlspecialchars($applied_disc['code']); ?></strong>
                                        <?php if (!empty($applied_disc['description'])): ?>
                                            <p style="margin: 2px 0 0 0; font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($applied_disc['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <form method="POST"class="remove-promo-form">
                                <input type="hidden"name="action"value="remove_discount">
                                <button type="submit"class="btn-remove-promo"title="Премахни промокод"><?php echo icon_trash(18, '#ef4444'); ?></button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST"class="promo-code-form">
                            <input type="hidden"name="action"value="apply_discount">
                            <input type="text"name="discount_code"placeholder="Въведете промокод"required>
                            <button type="submit"class="btn btn-apply-promo">Приложи</button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="summary-row">
                    <span><?php echo __('cart_page.subtotal'); ?>:</span>
                    <span>€<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <?php if (!empty($applied_discounts)): ?>
                    <?php foreach ($applied_discounts as $applied_disc): ?>
                        <div class="summary-row discount">
                            <span><?php echo icon_dollar(16, '#10b981'); ?> <?php echo htmlspecialchars($applied_disc['code']); ?> (-<?php echo $applied_disc['type'] === 'percentage' ? $applied_disc['value'] . '%' : '€' . number_format($applied_disc['value'], 2); ?>):</span>
                            <span>-€<?php echo number_format($applied_disc['discount_amount'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="summary-row summary-total">
                    <span><?php echo __('cart_page.total'); ?>:</span>
                    <span>€<?php echo number_format($total, 2); ?></span>
                </div>
                
                <?php if ($is_logged_in): ?>
                    <!-- Comprehensive Checkout Form -->
                    <h2 class="checkout-section"><?php echo __('checkout.shipping_details'); ?></h2>
                    <form method="POST">
                        <input type="hidden"name="action"value="checkout">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"for="checkout_first_name"><?php echo __('checkout.first_name'); ?> *</label>
                                <input type="text"id="checkout_first_name"name="first_name"class="form-input"required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"for="checkout_last_name"><?php echo __('checkout.last_name'); ?> *</label>
                                <input type="text"id="checkout_last_name"name="last_name"class="form-input"required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_phone"><?php echo __('checkout.phone'); ?> *</label>
                            <input type="tel"id="checkout_phone"name="phone"class="form-input"required placeholder="+359 888 123 456">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_email"><?php echo __('checkout.email'); ?></label>
                            <input type="email"id="checkout_email"name="email"class="form-input"placeholder="your@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_address"><?php echo __('checkout.address'); ?> *</label>
                            <input type="text"id="checkout_address"name="address"class="form-input"required placeholder="<?php echo __('checkout.address_placeholder'); ?>">
                        </div>
                        
                        <div class="form-grid-2-1">
                            <div class="form-group">
                                <label class="form-label"for="checkout_city"><?php echo __('checkout.city'); ?> *</label>
                                <input type="text"id="checkout_city"name="city"class="form-input"required placeholder="<?php echo __('checkout.city_placeholder'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"for="checkout_postal"><?php echo __('checkout.postal_code'); ?></label>
                                <input type="text"id="checkout_postal"name="postal_code"class="form-input"placeholder="1000">
                            </div>
                        </div>
                        
                        <h3><?php echo __('checkout.receiver_info'); ?> (<?php echo __('checkout.if_different'); ?>)</h3>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_receiver"><?php echo __('checkout.receiver_name'); ?></label>
                            <input type="text"id="checkout_receiver"name="receiver_name"class="form-input"placeholder="<?php echo __('checkout.receiver_name_placeholder'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_receiver_phone"><?php echo __('checkout.receiver_phone'); ?></label>
                            <input type="tel"id="checkout_receiver_phone"name="receiver_phone"class="form-input"placeholder="<?php echo __('checkout.receiver_phone_placeholder'); ?>">
                        </div>
                        
                        <h3><?php echo icon_truck(24); ?> <?php echo __('checkout.delivery_payment'); ?></h3>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_courier"><?php echo __('checkout.courier_service'); ?> *</label>
                            <select id="checkout_courier"name="courier"class="form-select"required>
                                <option value="econt"><?php echo icon_package(16); ?> <?php echo __('courier.econt'); ?> (<?php echo __('courier.econt_description'); ?>)</option>
                                <option value="speedy"><?php echo icon_zap(16); ?> <?php echo __('courier.speedy'); ?> (<?php echo __('courier.speedy_description'); ?>)</option>
                            </select>
                            <p class="form-hint"><?php echo __('courier.info'); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_payment"><?php echo __('checkout.payment_method'); ?> *</label>
                            <select id="checkout_payment"name="payment_method"class="form-select"required>
                                <option value="cod"><?php echo icon_dollar(16); ?> <?php echo __('payment.cod'); ?></option>
                                <option value="bank_transfer"><?php echo icon_bank(16); ?> <?php echo __('payment.bank_transfer'); ?></option>
                                <option value="card"><?php echo icon_credit_card(16); ?> <?php echo __('payment.card'); ?></option>
                            </select>
                            <p class="form-hint"><?php echo __('payment.cod_info'); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"for="checkout_notes"><?php echo __('checkout.delivery_notes'); ?></label>
                            <textarea id="checkout_notes"name="delivery_notes"class="form-textarea"rows="3"placeholder="<?php echo __('checkout.delivery_notes_placeholder'); ?>"></textarea>
                        </div>
                        
                        <button type="submit"class="btn btn-checkout">
                            <?php echo __('cart_page.complete_order'); ?> - €<?php echo number_format($total, 2); ?>
                        </button>
                        
                        <p class="form-terms">
                            <?php echo __('cart_page.terms_agreement'); ?>
                        </p>
                    </form>
                <?php else: ?>
                    <button type="button"class="btn btn-checkout"disabled>
                        <?php echo __('cart_page.login_to_checkout'); ?>
                    </button>
                    <p class="form-terms">
                        <?php echo __('cart_page.must_login'); ?>. <a href="/auth.php?action=login"><?php echo __('cart_page.login_here'); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; // End cart vs success page ?>

    <script src="assets/js/theme-manager.js"></script>
</body>
</html>

