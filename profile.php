<?php
/**
 * User Profile Management
 * Handles profile viewing, editing, password changes, and profile pictures
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
$is_logged_in = isset($_SESSION['customer_id']) && isset($_SESSION['customer_user']);
if (!$is_logged_in) {
    header('Location: /auth.php?action=login');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$db = Database::getInstance();
$pdo = $db->getPDO();

// Get customer data
$customer = db_table('customers')->find('id', $customer_id);
if (!$customer) {
    header('Location: /auth.php?logout=1');
    exit;
}

$success = '';
$error = '';
$active_tab = $_GET['tab'] ?? 'profile';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update Profile Info
    if ($action === 'update_profile') {
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $address_notes = trim($_POST['address_notes'] ?? '');
        
        if (empty($email)) {
            $error = __('auth.invalid_email');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('auth.invalid_email');
        } else {
            // Check if email is taken by another user
            $existing = db_table('customers')->all();
            $email_taken = false;
            foreach ($existing as $c) {
                if ($c['id'] !== $customer_id && strtolower($c['email']) === strtolower($email)) {
                    $email_taken = true;
                    break;
                }
            }
            
            if ($email_taken) {
                $error = __('profile.email_already_taken');
            } else {
                db_table('customers')->update($customer_id, [
                    'email' => $email,
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'city' => $city,
                    'address' => $address,
                    'postal_code' => $postal_code,
                    'region' => $region,
                    'address_notes' => $address_notes,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $customer = db_table('customers')->find('id', $customer_id);
                $success = __('profile.updated_successfully');
            }
        }
    }
    
    // Change Password
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = __('profile.all_password_fields_required');
        } elseif (!password_verify($current_password, $customer['password'])) {
            $error = __('profile.current_password_incorrect');
        } elseif (strlen($new_password) < 6) {
            $error = __('auth.password_min_6');
        } elseif ($new_password !== $confirm_password) {
            $error = __('auth.passwords_no_match');
        } else {
            db_table('customers')->update($customer_id, [
                'password' => password_hash($new_password, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $success = __('profile.password_changed_successfully');
            $active_tab = 'security';
        }
    }
    
    // Upload Profile Picture
    elseif ($action === 'upload_picture') {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = __('profile.invalid_image_type');
            } elseif ($file['size'] > $max_size) {
                $error = __('profile.image_too_large');
            } else {
                $upload_dir = CMS_ROOT . '/uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $customer_id . '_' . time() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Delete old profile picture if exists
                    if (!empty($customer['profile_picture'])) {
                        $old_file = CMS_ROOT . '/' . $customer['profile_picture'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    $profile_picture_path = 'uploads/profiles/' . $filename;
                    db_table('customers')->update($customer_id, [
                        'profile_picture' => $profile_picture_path,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $customer = db_table('customers')->find('id', $customer_id);
                    $success = __('profile.picture_uploaded_successfully');
                    $_SESSION['profile_picture'] = $profile_picture_path;
                } else {
                    $error = __('profile.upload_failed');
                }
            }
        } else {
            $error = __('profile.no_file_uploaded');
        }
    }
    
    // Remove Profile Picture
    elseif ($action === 'remove_picture') {
        if (!empty($customer['profile_picture'])) {
            $old_file = CMS_ROOT . '/' . $customer['profile_picture'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        db_table('customers')->update($customer_id, [
            'profile_picture' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $customer = db_table('customers')->find('id', $customer_id);
        $success = __('profile.picture_removed_successfully');
        unset($_SESSION['profile_picture']);
    }
}

// Get user's wishlist
$wishlist_items = [];
try {
    $stmt = $pdo->prepare("SELECT product_id, added_at FROM customer_wishlist WHERE customer_id = ? ORDER BY added_at DESC");
    $stmt->execute([$customer_id]);
    $wishlist_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $products = get_products_data();
    foreach ($wishlist_rows as $row) {
        foreach ($products as $product) {
            if ($product['id'] === $row['product_id']) {
                $wishlist_items[] = array_merge($product, ['added_at' => $row['added_at']]);
                break;
            }
        }
    }
} catch (Exception $e) {
    // Wishlist table might not exist
}

// Get user's orders (if orders system exists)
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Orders table might not exist
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('profile.my_profile'); ?> - <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></title>
    <script>
        // Apply theme immediately from localStorage to prevent flash
        (function() {
            const storedTheme = localStorage.getItem('offmeta_theme');
            if (storedTheme) {
                document.documentElement.setAttribute('data-theme', storedTheme);
            }
        })();
    </script>
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <div class="profile-container">
        <header class="profile-header">
            <div class="header-content">
                <a href="/" class="back-link"><?php echo __('back_to_shop'); ?></a>
                <h1><?php echo __('profile.my_profile'); ?></h1>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="profile-tabs">
            <a href="?tab=profile" class="tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
                <?php echo icon_user(20); ?> <?php echo __('profile.personal_info'); ?>
            </a>
            <a href="?tab=security" class="tab <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
                <?php echo icon_lock(20); ?> <?php echo __('profile.security'); ?>
            </a>
            <a href="?tab=wishlist" class="tab <?php echo $active_tab === 'wishlist' ? 'active' : ''; ?>">
                <?php echo icon_heart(20); ?> <?php echo __('profile.wishlist'); ?>
                <?php if (count($wishlist_items) > 0): ?>
                    <span class="badge"><?php echo count($wishlist_items); ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=orders" class="tab <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
                <?php echo icon_shopping_bag(20); ?> <?php echo __('profile.orders'); ?>
            </a>
        </div>

        <div class="profile-content">
            <?php if ($active_tab === 'profile'): ?>
                <!-- Personal Information Tab -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2><?php echo __('profile.personal_info'); ?></h2>
                    </div>

                    <div class="profile-picture-section">
                        <div class="current-picture">
                            <?php if (!empty($customer['profile_picture'])): ?>
                                <img src="/<?php echo htmlspecialchars($customer['profile_picture']); ?>" alt="Profile Picture" class="profile-pic">
                            <?php else: ?>
                                <div class="profile-pic-placeholder">
                                    <?php echo icon_user(64); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="picture-actions">
                            <form method="POST" enctype="multipart/form-data" class="upload-form">
                                <input type="hidden" name="action" value="upload_picture">
                                <label for="profile_picture" class="btn btn-secondary">
                                    <?php echo icon_upload(16); ?> <?php echo __('profile.upload_picture'); ?>
                                </label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display: none;" onchange="this.form.submit()">
                            </form>
                            <?php if (!empty($customer['profile_picture'])): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_picture">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('<?php echo __('profile.confirm_remove_picture'); ?>')">
                                        <?php echo icon_trash(16); ?> <?php echo __('profile.remove'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" class="profile-form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="username"><?php echo __('auth.username'); ?></label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($customer['username']); ?>" disabled>
                            <small><?php echo __('profile.username_cannot_change'); ?></small>
                        </div>

                        <div class="form-group">
                            <label for="email"><?php echo __('auth.email'); ?> *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="full_name"><?php echo __('profile.full_name'); ?></label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($customer['full_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone"><?php echo __('profile.phone'); ?></label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                        </div>

                        <div class="section-divider">
                            <h3><?php echo __('profile.delivery_info'); ?></h3>
                            <p class="section-description"><?php echo __('profile.delivery_info_description'); ?></p>
                        </div>

                        <div class="form-group">
                            <label for="city"><?php echo __('profile.city'); ?></label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>" placeholder="<?php echo __('profile.city_placeholder'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="address"><?php echo __('profile.address'); ?></label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" placeholder="<?php echo __('profile.address_placeholder'); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="postal_code"><?php echo __('profile.postal_code'); ?></label>
                                <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($customer['postal_code'] ?? ''); ?>" placeholder="<?php echo __('profile.postal_code_placeholder'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="region"><?php echo __('profile.region'); ?></label>
                                <input type="text" id="region" name="region" value="<?php echo htmlspecialchars($customer['region'] ?? ''); ?>" placeholder="<?php echo __('profile.region_placeholder'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address_notes"><?php echo __('profile.address_notes'); ?></label>
                            <textarea id="address_notes" name="address_notes" rows="3" placeholder="<?php echo __('profile.address_notes_placeholder'); ?>"><?php echo htmlspecialchars($customer['address_notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><?php echo __('profile.member_since'); ?></label>
                            <input type="text" value="<?php echo date('F j, Y', strtotime($customer['created_at'] ?? 'now')); ?>" disabled>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo icon_save(16); ?> <?php echo __('profile.save_changes'); ?>
                        </button>
                    </form>
                </div>

            <?php elseif ($active_tab === 'security'): ?>
                <!-- Security Tab -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2><?php echo __('profile.change_password'); ?></h2>
                    </div>

                    <form method="POST" class="profile-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password"><?php echo __('profile.current_password'); ?> *</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password"><?php echo __('profile.new_password'); ?> *</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                            <small><?php echo __('auth.password_min_6'); ?></small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password"><?php echo __('profile.confirm_new_password'); ?> *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo icon_lock(16); ?> <?php echo __('profile.update_password'); ?>
                        </button>
                    </form>
                </div>

            <?php elseif ($active_tab === 'wishlist'): ?>
                <!-- Wishlist Tab -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2><?php echo __('profile.my_wishlist'); ?></h2>
                        <p><?php echo count($wishlist_items); ?> <?php echo __('profile.items'); ?></p>
                    </div>

                    <?php if (empty($wishlist_items)): ?>
                        <div class="empty-state">
                            <?php echo icon_heart(64); ?>
                            <h3><?php echo __('profile.wishlist_empty'); ?></h3>
                            <p><?php echo __('profile.wishlist_empty_text'); ?></p>
                            <a href="/" class="btn btn-primary"><?php echo __('profile.browse_products'); ?></a>
                        </div>
                    <?php else: ?>
                        <div class="wishlist-grid">
                            <?php foreach ($wishlist_items as $item): ?>
                                <div class="wishlist-item" data-product-id="<?php echo htmlspecialchars($item['id']); ?>">
                                    <div class="item-image">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                        <?php else: ?>
                                            <div class="no-image"><?php echo icon_image(48); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <h3><a href="/product/<?php echo htmlspecialchars($item['slug']); ?>"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                                        <p class="item-price"><?php echo number_format($item['price'], 2); ?> <?php echo __('currency'); ?></p>
                                        <p class="item-added"><?php echo __('profile.added'); ?>: <?php echo date('M j, Y', strtotime($item['added_at'])); ?></p>
                                        <div class="item-actions">
                                            <a href="/product/<?php echo htmlspecialchars($item['slug']); ?>" class="btn btn-primary btn-sm"><?php echo __('profile.view_product'); ?></a>
                                            <button class="btn btn-danger btn-sm remove-wishlist" data-product-id="<?php echo htmlspecialchars($item['id']); ?>">
                                                <?php echo icon_trash(14); ?> <?php echo __('profile.remove'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($active_tab === 'orders'): ?>
                <!-- Orders Tab -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2><?php echo __('profile.order_history'); ?></h2>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <?php echo icon_shopping_bag(64); ?>
                            <h3><?php echo __('profile.no_orders'); ?></h3>
                            <p><?php echo __('profile.no_orders_text'); ?></p>
                            <a href="/" class="btn btn-primary"><?php echo __('profile.start_shopping'); ?></a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-item">
                                    <div class="order-header">
                                        <span class="order-id"><?php echo __('profile.order'); ?> #<?php echo htmlspecialchars($order['id']); ?></span>
                                        <span class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <div class="order-details">
                                        <span class="order-status status-<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($order['status'] ?? 'pending')); ?>
                                        </span>
                                        <span class="order-total"><?php echo number_format($order['total'] ?? 0, 2); ?> <?php echo __('currency'); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/theme-manager.js"></script>
    <script>
        // Remove from wishlist functionality
        document.querySelectorAll('.remove-wishlist').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!confirm('<?php echo __('profile.confirm_remove_wishlist'); ?>')) return;
                
                const productId = this.dataset.productId;
                const itemEl = this.closest('.wishlist-item');
                
                try {
                    const response = await fetch('/wishlist.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=remove&product_id=${productId}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        itemEl.style.opacity = '0';
                        setTimeout(() => {
                            itemEl.remove();
                            // Reload if no items left
                            if (document.querySelectorAll('.wishlist-item').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    } else {
                        alert(data.message || '<?php echo __('profile.error_removing'); ?>');
                    }
                } catch (error) {
                    alert('<?php echo __('profile.error_occurred'); ?>');
                }
            });
        });
    </script>
</body>
</html>
