<?php
/**
 * CMS Admin Dashboard
 */

// Get query parameter for current section
$section = $_GET['section'] ?? 'dashboard';
$action = $_GET['action'] ?? '';

// Map section names to translation keys
$sectionTitles = [
    'dashboard' => __('menu.dashboard'),
    'products' => __('menu.products'),
    'import-products' => 'Импорт на продукти',
    'categories' => __('admin.categories'),
    'orders' => __('menu.orders'),
    'promotions' => __('admin.promotions'),
    'discounts' => __('admin.discounts'),
    'pages' => __('menu.pages'),
    'posts' => __('menu.blog_posts'),
    'media' => __('menu.media'),
    'themes' => 'Themes',
    'inquiries' => __('inquiry.title'),
    'users' => __('menu.users'),
    'database' => __('menu.database'),
    'settings' => __('menu.settings'),
    'tools' => __('menu.tools'),
];

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'save_page':
            save_page($_POST['slug'], [
                'title' => $_POST['title'],
                'content' => $_POST['content'],
                'meta_description' => $_POST['meta_description'] ?? '',
                'status' => $_POST['status'] ?? 'published',
            ]);
            $message = __('admin.page_saved');
            break;

        case 'delete_page':
            delete_page($_POST['slug']);
            $message = __('admin.page_deleted');
            break;

        case 'save_post':
            save_post($_POST['slug'], [
                'title' => $_POST['title'],
                'content' => $_POST['content'],
                'excerpt' => $_POST['excerpt'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'status' => $_POST['status'] ?? 'published',
                'category' => $_POST['category'] ?? 'uncategorized',
            ]);
            $message = __('admin.post_saved');
            break;

        case 'delete_post':
            delete_post($_POST['slug']);
            $message = __('admin.post_deleted');
            break;

        case 'update_settings':
            update_option('site_title', $_POST['site_title']);
            update_option('site_description', $_POST['site_description']);
            update_option('site_email', $_POST['site_email']);
            $message = __('admin.settings_updated');
            break;

        case 'upload_media':
            if (!isset($_FILES['media']) || empty($_FILES['media']['name'][0])) {
                $message = __('admin.no_file_selected');
                break;
            }

            $uploadDir = CMS_ROOT . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadedFiles = [];
            $errors = [];
            $fileCount = count($_FILES['media']['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['media']['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = $_FILES['media']['name'][$i] . ': ' . __('admin.upload_error');
                    continue;
                }

                $originalName = $_FILES['media']['name'][$i] ?? 'upload';
                $tmpName = $_FILES['media']['tmp_name'][$i];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $heicExtensions = ['heic', 'heif'];

                if (in_array($extension, $heicExtensions, true)) {
                    if (class_exists('\Imagick')) {
                        $fileName = 'media_' . uniqid() . '.jpg';
                        $targetPath = $uploadDir . $fileName;
                        try {
                            $image = new \Imagick($tmpName);
                            $image->setImageFormat('jpeg');
                            $image->setImageCompressionQuality(90);
                            $image->stripImage();
                            $image->writeImage($targetPath);
                            $image->clear();
                            $image->destroy();
                            $uploadedFiles[] = $fileName;
                        } catch (Exception $e) {
                            $errors[] = $originalName . ': Грешка при конвертиране';
                        }
                    } else {
                        $errors[] = $originalName . ': HEIC/HEIF не се поддържат';
                    }
                    continue;
                }

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = $originalName . ': Неподдържан формат';
                    continue;
                }

                $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
                $baseName = trim($baseName, '-');
                if ($baseName === '') {
                    $baseName = 'media';
                }

                $fileName = $baseName . '_' . time() . '_' . uniqid() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $fileName;
                } else {
                    $errors[] = $originalName . ': Грешка при качване';
                }
            }

            if (!empty($uploadedFiles)) {
                $message = 'Качени ' . count($uploadedFiles) . ' файл(а): ' . implode(', ', array_map('htmlspecialchars', $uploadedFiles));
                if (!empty($errors)) {
                    $message .= '<br>Грешки: ' . implode(', ', array_map('htmlspecialchars', $errors));
                }
            } else {
                $message = 'Няма качени файлове. ' . (!empty($errors) ? 'Грешки: ' . implode(', ', array_map('htmlspecialchars', $errors)) : '');
            }
            break;
        case 'save_product':
            $product_id = $_POST['product_id'] ?: uniqid('prod_');
            
            // Handle image upload
            $imagePath = sanitize($_POST['image'] ?? '');
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = CMS_ROOT . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($fileExtension, $allowedExtensions) && $_FILES['product_image']['size'] <= 5242880) {
                    $fileName = 'product_' . $product_id . '_' . time() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
                        $imagePath = '/uploads/' . $fileName;
                    }
                }
            }
            
            save_product_data([
                'id' => $product_id,
                'name' => sanitize($_POST['name']),
                'description' => sanitize($_POST['description']),
                'price' => floatval($_POST['price']),
                'image' => $imagePath,
                'category' => sanitize($_POST['category'] ?? 'general'),
                'stock' => intval($_POST['stock'] ?? 0),
                'status' => $_POST['status'] ?? 'published',
                'videos' => [
                    'youtube' => sanitize($_POST['video_youtube'] ?? ''),
                    'tiktok' => sanitize($_POST['video_tiktok'] ?? ''),
                    'instagram' => sanitize($_POST['video_instagram'] ?? ''),
                ],
            ]);
            
            $message = __('admin.product_saved');
            break;

        case 'delete_product':
            $product_id = $_POST['product_id'] ?? '';
            delete_product_data($product_id);
            
            $message = __('admin.product_deleted');
            break;

        case 'save_customer':
            $customer_id = $_POST['customer_id'] ?: uniqid('cust_');
            $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
            save_customer_data([
                'id' => $customer_id,
                'username' => sanitize($_POST['username']),
                'email' => sanitize($_POST['email']),
                'password' => $password,
                'role' => sanitize($_POST['role'] ?? 'customer'),
                'permissions' => $_POST['permissions'] ?? ['view_products', 'place_orders'],
            ]);
            $message = __('admin.customer_saved');
            break;

        case 'delete_customer':
            $customer_id = $_POST['customer_id'] ?? '';
            delete_customer_data($customer_id);
            $message = __('admin.customer_deleted');
            break;

        case 'update_order_status':
            $order_id = $_POST['order_id'] ?? '';
            update_order_status_data($order_id, $_POST['status'] ?? 'pending');
            $message = __('admin.order_status_updated');
            break;

        case 'delete_order':
            $order_id = $_POST['order_id'] ?? '';
            delete_order_data($order_id);
            $message = __('admin.order_deleted');
            break;

        // Categories Management
        case 'save_category':
            $category_id = $_POST['category_id'] ?: uniqid('cat_');
            save_category_data([
                'id' => $category_id,
                'name' => sanitize($_POST['name']),
                'slug' => sanitize($_POST['slug']),
                'description' => sanitize($_POST['description'] ?? ''),
                'parent_id' => sanitize($_POST['parent_id'] ?? ''),
                'icon' => sanitize($_POST['icon'] ?? ''),
                'order' => intval($_POST['order'] ?? 0),
                'active' => isset($_POST['active']),
            ]);
            
            $message = 'Категорията е запазена успешно';
            break;

        case 'delete_category':
            $category_id = $_POST['category_id'] ?? '';
            delete_category_data($category_id);
            
            $message = 'Категорията е изтрита';
            break;

        // Promotions Management
        case 'save_promotion':
            $promotion_id = $_POST['promotion_id'] ?: uniqid('promo_');
            
            // Base fields
            $promotion = [
                'id' => $promotion_id,
                'title' => sanitize($_POST['title']),
                'description' => sanitize($_POST['description'] ?? ''),
                'type' => sanitize($_POST['type']),
                'start_date' => sanitize($_POST['start_date'] ?? ''),
                'end_date' => sanitize($_POST['end_date'] ?? ''),
                'order' => intval($_POST['order'] ?? 0),
                'active' => isset($_POST['active']),
                'updated' => date('Y-m-d H:i:s'),
            ];
            
            // Visual promotion fields (banner, popup, notification, homepage)
            if (in_array($promotion['type'], ['banner', 'popup', 'notification', 'homepage'])) {
                $promotion['image'] = sanitize($_POST['image'] ?? '');
                $promotion['link'] = sanitize($_POST['link'] ?? '');
            }
            
            // Sales promotion fields (bundle, buy_x_get_y, product_discount, category_discount, cart_discount)
            if (in_array($promotion['type'], ['bundle', 'buy_x_get_y', 'product_discount', 'category_discount', 'cart_discount'])) {
                $promotion['discount_type'] = sanitize($_POST['discount_type'] ?? 'percentage');
                $promotion['discount_value'] = floatval($_POST['discount_value'] ?? 0);
                $promotion['min_purchase'] = floatval($_POST['min_purchase'] ?? 0);
                
                // Product selection
                if (in_array($promotion['type'], ['bundle', 'product_discount', 'buy_x_get_y'])) {
                    $promotion['product_ids'] = $_POST['product_ids'] ?? [];
                }
                
                // Category selection
                if ($promotion['type'] === 'category_discount') {
                    $promotion['category_id'] = sanitize($_POST['category_id'] ?? '');
                }
                
                // Buy X Get Y quantities
                if ($promotion['type'] === 'buy_x_get_y') {
                    $promotion['buy_quantity'] = intval($_POST['buy_quantity'] ?? 2);
                    $promotion['get_quantity'] = intval($_POST['get_quantity'] ?? 1);
                }
            }
            
            save_promotion_data($promotion);
            $message = 'Промоцията е запазена успешно';
            break;

        case 'delete_promotion':
            $promotion_id = $_POST['promotion_id'] ?? '';
            delete_promotion_data($promotion_id);
            $message = 'Промоцията е изтрита';
            break;

        // Discounts Management
        case 'save_discount':
            $discounts = get_discounts_data();
            $discount_id = $_POST['discount_id'] ?: uniqid('disc_');
            $existing = $discounts[$discount_id] ?? [];
            save_discount_data([
                'id' => $discount_id,
                'code' => strtoupper(sanitize($_POST['code'])),
                'description' => sanitize($_POST['description'] ?? ''),
                'type' => sanitize($_POST['type']),
                'value' => floatval($_POST['value']),
                'min_purchase' => floatval($_POST['min_purchase'] ?? 0),
                'max_uses' => intval($_POST['max_uses'] ?? 0),
                'used_count' => $existing['used_count'] ?? 0,
                'start_date' => sanitize($_POST['start_date'] ?? ''),
                'end_date' => sanitize($_POST['end_date'] ?? ''),
                'active' => isset($_POST['active']),
                'first_purchase_only' => isset($_POST['first_purchase_only']),
            ]);
            $message = 'Отстъпката е запазена успешно';
            break;

        case 'delete_discount':
            $discount_id = $_POST['discount_id'] ?? '';
            delete_discount_data($discount_id);
            $message = 'Отстъпката е изтрита';
            break;
        
        case 'update_inquiry_status':
            $inquiry_id = $_POST['inquiry_id'] ?? '';
            update_inquiry_status_data($inquiry_id, $_POST['status'] ?? 'pending');
            $message = 'Статусът на запитването е актуализиран';
            break;
        
        case 'delete_inquiry':
            $inquiry_id = $_POST['inquiry_id'] ?? '';
            delete_inquiry_data($inquiry_id);
            $message = 'Запитването е изтрито';
            break;

        case 'delete_media':
            $filename = $_POST['filename'] ?? '';
            if ($filename === '' || strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                $message = 'Невалидно име на файл';
                break;
            }
            
            $uploadDir = CMS_ROOT . '/uploads/';
            $filePath = $uploadDir . $filename;
            
            if (file_exists($filePath) && is_file($filePath)) {
                if (unlink($filePath)) {
                    $message = 'Файлът "' . htmlspecialchars($filename) . '" е изтрит успешно';
                } else {
                    $message = 'Грешка при изтриване на файла';
                }
            } else {
                $message = 'Файлът не съществува';
            }
            break;

        default:
            // Unknown action - do nothing
            break;
    }
}

$stats = get_dashboard_stats();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin.cms_dashboard'); ?></title>
    <link rel="stylesheet" href="../assets/css/themes.css">
    <?php echo get_custom_theme_css(); ?>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/admin-dashboard-section.css">
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2><?php echo icon_settings(22); ?> CMS</h2>
            
            <!-- Overview Section -->
            <div class="sidebar-section">
                <ul>
                    <li><a href="../" class="back-to-site"><?php echo icon_home(18); ?> <?php echo __('menu.back_to_site'); ?></a></li>
                    <li><a href="?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>"><?php echo icon_package(18); ?> <?php echo __('menu.dashboard'); ?></a></li>
                </ul>
            </div>

            <!-- E-commerce Section -->
            <div class="sidebar-section">
                <p class="sidebar-section-title">E-Commerce</p>
                <ul>
                    <li><a href="?section=products" class="<?php echo $section === 'products' ? 'active' : ''; ?>"><?php echo icon_package(18); ?> <?php echo __('menu.products'); ?></a></li>
                    <li><a href="?section=import-products" class="<?php echo $section === 'import-products' ? 'active' : ''; ?>">📥 Импорт</a></li>
                    <li><a href="?section=categories" class="<?php echo $section === 'categories' ? 'active' : ''; ?>"><?php echo icon_folder(18); ?> <?php echo __('admin.categories'); ?></a></li>
                    <li><a href="?section=orders" class="<?php echo $section === 'orders' ? 'active' : ''; ?>"><?php echo icon_cart(18); ?> <?php echo __('menu.orders'); ?></a></li>
                </ul>
            </div>

            <!-- Marketing Section -->
            <div class="sidebar-section">
                <p class="sidebar-section-title">Маркетинг</p>
                <ul>
                    <li><a href="?section=promotions" class="<?php echo $section === 'promotions' ? 'active' : ''; ?>"><?php echo icon_megaphone(18); ?> <?php echo __('admin.promotions'); ?></a></li>
                    <li><a href="?section=discounts" class="<?php echo $section === 'discounts' ? 'active' : ''; ?>"><?php echo icon_percent(18); ?> <?php echo __('admin.discounts'); ?></a></li>
                </ul>
            </div>

            <!-- Content Section -->
            <div class="sidebar-section">
                <p class="sidebar-section-title">Съдържание</p>
                <ul>
                    <li><a href="?section=pages" class="<?php echo $section === 'pages' ? 'active' : ''; ?>"><?php echo icon_home(18); ?> <?php echo __('menu.pages'); ?></a></li>
                    <li><a href="?section=posts" class="<?php echo $section === 'posts' ? 'active' : ''; ?>"><?php echo icon_check(18); ?> <?php echo __('menu.blog_posts'); ?></a></li>
                    <li><a href="?section=media" class="<?php echo $section === 'media' ? 'active' : ''; ?>"><?php echo icon_package(18); ?> <?php echo __('menu.media'); ?></a></li>
                    <li><a href="?section=themes" class="<?php echo $section === 'themes' ? 'active' : ''; ?>"><?php echo icon_edit(18); ?> Themes</a></li>
                </ul>
            </div>

            <!-- Communication Section -->
            <div class="sidebar-section">
                <p class="sidebar-section-title">Комуникация</p>
                <ul>
                    <li><a href="?section=inquiries" class="<?php echo $section === 'inquiries' ? 'active' : ''; ?>"><?php echo icon_mail(18); ?> <?php echo __('inquiry.title'); ?></a></li>
                    <li><a href="?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>"><?php echo icon_user(18); ?> <?php echo __('menu.users'); ?></a></li>
                </ul>
            </div>

            <!-- System Section -->
            <div class="sidebar-section">
                <p class="sidebar-section-title">Система</p>
                <ul>
                    <li><a href="?section=database" class="<?php echo $section === 'database' ? 'active' : ''; ?>"><?php echo icon_settings(18); ?> <?php echo __('menu.database'); ?></a></li>
                    <li><a href="?section=settings" class="<?php echo $section === 'settings' ? 'active' : ''; ?>"><?php echo icon_settings(18); ?> <?php echo __('menu.settings'); ?></a></li>
                    <li><a href="?section=tools" class="<?php echo $section === 'tools' ? 'active' : ''; ?>"><?php echo icon_alert(18); ?> <?php echo __('menu.tools'); ?></a></li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <div class="header">
                <div>
                    <h1><?php echo icon_home(24); ?> <?php echo $sectionTitles[$section] ?? ucfirst($section); ?></h1>
                </div>
                <div class="header-actions">
                    <a href="?section=<?php echo htmlspecialchars($section); ?>&lang=<?php echo opposite_lang(); ?>" 
                       class="lang-btn action-btn" 
                       title="Switch to <?php echo lang_name(opposite_lang()); ?>">
                        <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
                    </a>
                    <form method="POST" class="inline-form">
                        <button type="submit" name="logout" value="1" class="logout-btn"><?php echo __('admin.logout'); ?></button>
                    </form>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="content">
                <?php
                // Load section content
                if ($section === 'dashboard') {
                    include_once 'sections/dashboard.php';
                } elseif ($section === 'products') {
                    include_once 'sections/products.php';
                } elseif ($section === 'import-products') {
                    include_once 'sections/import-products.php';
                } elseif ($section === 'categories') {
                    include_once 'sections/categories.php';
                } elseif ($section === 'orders') {
                    include_once 'sections/orders.php';
                } elseif ($section === 'promotions') {
                    include_once 'sections/promotions.php';
                } elseif ($section === 'discounts') {
                    include_once 'sections/discounts.php';
                } elseif ($section === 'inquiries') {
                    include_once 'sections/inquiries.php';
                } elseif ($section === 'pages') {
                    include_once 'sections/pages.php';
                } elseif ($section === 'posts') {
                    include_once 'sections/posts.php';
                } elseif ($section === 'media') {
                    include_once 'sections/media.php';
                } elseif ($section === 'themes') {
                    include_once 'sections/themes.php';
                } elseif ($section === 'users') {
                    include_once 'sections/users.php';
                } elseif ($section === 'database') {
                    include_once 'sections/database.php';
                } elseif ($section === 'settings') {
                    include_once 'sections/settings.php';
                } elseif ($section === 'tools') {
                    include_once 'sections/tools.php';
                }
                ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/theme-manager.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>

