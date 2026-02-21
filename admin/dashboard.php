<?php
/**
 * CMS Admin Dashboard
 */

// Define CMS Root
if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

// Block prefetch/prerender requests to avoid Cloudflare 503 errors
if (isset($_SERVER['HTTP_SEC_PURPOSE']) && in_array($_SERVER['HTTP_SEC_PURPOSE'], ['prefetch', 'prerender'])) {
    http_response_code(204); // No Content
    exit;
}

// Start session
session_start();

// Load required files
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/image-optimizer.php';
require_once __DIR__ . '/../includes/site-settings.php';
require_once __DIR__ . '/../includes/dashboard-cache.php';
require_once __DIR__ . '/../includes/cloudflare-analytics.php';

// Check authentication
if (!isset($_SESSION['admin_user'])) {
    header('Location: /auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

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
    'analytics' => 'Web Analytics',
    'financial' => 'Financial Data',
    'database' => __('menu.database'),
    'database-browser' => 'Database Browser',
    'settings' => __('menu.settings'),
    'tools' => __('menu.tools'),
];

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // DEBUG: Log POST requests
    error_log("[ADMIN DEBUG] POST action=$action | slug=". ($_POST['slug'] ?? 'none'));

    switch ($action) {
        case 'save_page':
            try {
                error_log("[ADMIN DEBUG] Calling save_page for: ". $_POST['slug']);
                save_page($_POST['slug'], [
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'meta_description' => $_POST['meta_description'] ?? '',
                    'status' => $_POST['status'] ?? 'published',
                ]);
                error_log("[ADMIN DEBUG] save_page completed");
                $message = __('admin.page_saved');
            } catch (Exception $e) {
                error_log('Error saving page: ' . $e->getMessage());
                $message = 'Грешка при запазване: ' . $e->getMessage();
            }
            break;

        case 'delete_page':
            try {
                delete_page($_POST['slug']);
                $message = __('admin.page_deleted');
            } catch (Exception $e) {
                error_log('Error deleting page: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        case 'save_post':
            try {
                save_post($_POST['slug'], [
                    'title' => $_POST['title'],
                    'content' => $_POST['content'],
                    'excerpt' => $_POST['excerpt'] ?? '',
                    'meta_description' => $_POST['meta_description'] ?? '',
                    'status' => $_POST['status'] ?? 'published',
                    'category' => $_POST['category'] ?? 'uncategorized',
                ]);
                $message = __('admin.post_saved');
            } catch (Exception $e) {
                error_log('Error saving post: ' . $e->getMessage());
                $message = 'Грешка при запазване: ' . $e->getMessage();
            }
            break;

        case 'delete_post':
            try {
                delete_post($_POST['slug']);
                $message = __('admin.post_deleted');
            } catch (Exception $e) {
                error_log('Error deleting post: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        case 'update_settings':
            try {
                update_option('site_title', $_POST['site_title']);
                update_option('site_description', $_POST['site_description']);
                update_option('site_email', $_POST['site_email']);
                $message = __('admin.settings_updated');
            } catch (Exception $e) {
                error_log('Error updating settings: ' . $e->getMessage());
                $message = 'Грешка при актуализиране: ' . $e->getMessage();
            }
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
                    // Optimize image to reduce file size (max 85% quality, 2MB+ files)
                    if (filesize($targetPath) > 2097152) { // 2 MB
                        $optimization = optimize_image($targetPath, null, 85, 2000, 2000);
                        if ($optimization['success']) {
                            error_log("Image optimized: $fileName - Saved {$optimization['savings']}");
                        }
                    }
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
            try {
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
                
                // Price validation warnings
                $price = floatval($_POST['price']);
                $warningMessages = [];
                
                if ($price <= 0) {
                    $warningMessages[] = "⚠️ Внимание: Цената е 0. Продуктът ще показва 'Свържете се за цена'.";
                }
                
                if ($price > 0 && $price < 1) {
                    $warningMessages[] = "⚠️ Внимание: Много ниска цена (под 1 {$_POST['currency']}).";
                }
                
                save_product_data([
                    'id' => $product_id,
                    'name' => sanitize($_POST['name']),
                    'description' => sanitize($_POST['description']),
                    'price' => $price,
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
                if (!empty($warningMessages)) {
                    $message .= '<br>' . implode('<br>', $warningMessages);
                }
            } catch (Exception $e) {
                error_log("Product save failed: ". $e->getMessage() . "| Trace: ". $e->getTraceAsString());
                $message = "⚠️ Error saving product: ". htmlspecialchars($e->getMessage());
            }
            break;

        case 'delete_product':
            try {
                $product_id = $_POST['product_id'] ?? '';
                delete_product_data($product_id);
                $message = __('admin.product_deleted');
            } catch (Exception $e) {
                error_log('Error deleting product: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        case 'bulk_delete_products':
            try {
                $product_ids = $_POST['product_ids'] ?? '';
                if (!empty($product_ids)) {
                    $ids = explode(',', $product_ids);
                    $deleted_count = 0;
                    foreach ($ids as $product_id) {
                        $product_id = trim($product_id);
                        if (!empty($product_id)) {
                            delete_product_data($product_id);
                            $deleted_count++;
                        }
                    }
                    $message = "✅ Изтрити {$deleted_count} продукта успешно!";
                } else {
                    $message = "⚠️ Не са избрани продукти за изтриване";
                }
            } catch (Exception $e) {
                error_log('Error bulk deleting products: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        case 'save_customer':
            try {
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
            } catch (Exception $e) {
                error_log('Error saving customer: ' . $e->getMessage());
                $message = 'Грешка при запазване: ' . $e->getMessage();
            }
            break;

        case 'delete_customer':
            try {
                $customer_id = $_POST['customer_id'] ?? '';
                delete_customer_data($customer_id);
                $message = __('admin.customer_deleted');
            } catch (Exception $e) {
                error_log('Error deleting customer: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        case 'update_order_status':
            try {
                $order_id = $_POST['order_id'] ?? '';
                update_order_status_data($order_id, $_POST['status'] ?? 'pending');
                $message = __('admin.order_status_updated');
            } catch (Exception $e) {
                error_log('Error updating order status: ' . $e->getMessage());
                $message = 'Грешка при актуализиране: ' . $e->getMessage();
            }
            break;

        case 'delete_order':
            try {
                $order_id = $_POST['order_id'] ?? '';
                delete_order_data($order_id);
                $message = __('admin.order_deleted');
            } catch (Exception $e) {
                error_log('Error deleting order: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        case 'save_category':
            try {
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
            } catch (Exception $e) {
                error_log('Error saving category: ' . $e->getMessage());
                $message = 'Грешка при запазване: ' . $e->getMessage();
            }
            break;

        case 'delete_category':
            try {
                $category_id = $_POST['category_id'] ?? '';
                delete_category_data($category_id);
                $message = 'Категорията е изтрита';
            } catch (Exception $e) {
                error_log('Error deleting category: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        // Promotions Management
        case 'save_promotion':
            try {
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
                
                // Visual promotion fields - ALL visual types need image/link
                $visual_types = ['banner', 'popup', 'notification', 'homepage', 
                                'sidebar_left', 'sidebar_right', 'slider', 
                                'seasonal', 'flash_sale', 'clearance', 'new_arrival', 'bundle_deal'];
                if (in_array($promotion['type'], $visual_types)) {
                    $promotion['image'] = sanitize($_POST['image'] ?? '');
                    $promotion['link'] = sanitize($_POST['link'] ?? '');
                    $promotion['subtitle'] = sanitize($_POST['subtitle'] ?? '');
                    $promotion['button_text'] = sanitize($_POST['button_text'] ?? '');
                    $promotion['background_color'] = sanitize($_POST['background_color'] ?? '');
                    $promotion['text_color'] = sanitize($_POST['text_color'] ?? '');
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
            } catch (Exception $e) {
                error_log('Error saving promotion: ' . $e->getMessage());
                $message = 'Грешка при запазване: ' . $e->getMessage();
            }
            break;

        case 'delete_promotion':
            try {
                $promotion_id = $_POST['promotion_id'] ?? '';
                delete_promotion_data($promotion_id);
                $message = 'Промоцията е изтрита';
            } catch (Exception $e) {
                error_log('Error deleting promotion: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        // Discounts Management
        case 'save_discount':
            try {
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
            } catch (Exception $e) {
                error_log('Error saving discount: ' . $e->getMessage());
                $message = 'Грешка при запазване: ' . $e->getMessage();
            }
            break;

        case 'delete_discount':
            try {
                $discount_id = $_POST['discount_id'] ?? '';
                delete_discount_data($discount_id);
                $message = 'Отстъпката е изтрита';
            } catch (Exception $e) {
                error_log('Error deleting discount: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;
        
        case 'update_inquiry_status':
            try {
                $inquiry_id = $_POST['inquiry_id'] ?? '';
                update_inquiry_status_data($inquiry_id, $_POST['status'] ?? 'pending');
                $message = 'Статусът на запитването е актуализиран';
            } catch (Exception $e) {
                error_log('Error updating inquiry status: ' . $e->getMessage());
                $message = 'Грешка при актуализиране: ' . $e->getMessage();
            }
            break;
        
        case 'delete_inquiry':
            try {
                $inquiry_id = $_POST['inquiry_id'] ?? '';
                delete_inquiry_data($inquiry_id);
                $message = 'Запитването е изтрито';
            } catch (Exception $e) {
                error_log('Error deleting inquiry: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        case 'delete_media':
            try {
                $filename = $_POST['filename'] ?? '';
                if ($filename === '' || strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                    $message = 'Невалидно име на файл';
                    break;
                }
                
                $uploadDir = CMS_ROOT . '/uploads/';
                $filePath = $uploadDir . $filename;
                
                if (file_exists($filePath) && is_file($filePath)) {
                    if (unlink($filePath)) {
                        $message = 'Файлът "' . htmlspecialchars($filename) . '"е изтрит успешно';
                    } else {
                        $message = 'Грешка при изтриване на файла';
                    }
                } else {
                    $message = 'Файлът не съществува';
                }
            } catch (Exception $e) {
                error_log('Error deleting media: ' . $e->getMessage());
                $message = 'Грешка при изтриване: ' . $e->getMessage();
            }
            break;

        // Analytics Management
        case 'save_analytics':
            try {
                save_analytics_data($_POST);
                $message = '✅ Analytics entry saved successfully!';
            } catch (Exception $e) {
                $message = '❌ Error saving analytics: ' . htmlspecialchars($e->getMessage());
            }
            break;

        case 'delete_analytics':
            try {
                $entry_id = $_POST['entry_id'] ?? '';
                delete_analytics_entry($entry_id);
                $message = '✅ Analytics entry deleted!';
            } catch (Exception $e) {
                $message = '❌ Error deleting analytics: ' . htmlspecialchars($e->getMessage());
            }
            break;

        // Financial Data Management
        case 'save_financial':
            try {
                save_financial_data($_POST);
                $message = '✅ Financial data saved successfully!';
            } catch (Exception $e) {
                $message = '❌ Error saving financial data: ' . htmlspecialchars($e->getMessage());
            }
            break;

        case 'delete_financial':
            try {
                $entry_id = $_POST['entry_id'] ?? '';
                delete_financial_entry($entry_id);
                $message = '✅ Financial entry deleted!';
            } catch (Exception $e) {
                $message = '❌ Error deleting financial data: ' . htmlspecialchars($e->getMessage());
            }
            break;

        // Bulk Edit Handlers
        case 'bulk_fix_product':
            try {
                $product_id = $_POST['product_id'] ?? '';
                $price = floatval($_POST['price'] ?? 0);
                $stock = intval($_POST['stock'] ?? 0);
                $short_description = sanitize($_POST['short_description'] ?? '');
                
                // Clean HTML artifacts from short description
                $short_description = preg_replace('/<(\w+)\s+data-[^>]*>/', '<$1>', $short_description);
                $short_description = preg_replace('/\sdata-[a-z-]+="[^"]*"/', '', $short_description);
                $short_description = strip_tags($short_description);
                
                $db = Database::getInstance();
                $pdo = $db->getPDO();
                
                // Update price
                $stmt = $pdo->prepare("UPDATE product_prices SET price = ? WHERE product_id = ? AND is_active = true");
                $stmt->execute([$price, $product_id]);
                
                // Update stock
                $stmt = $pdo->prepare("UPDATE product_inventory SET quantity = ? WHERE product_id = ?");
                $stmt->execute([$stock, $product_id]);
                
                // Update short description
                $lang = $_SESSION['lang'] ?? 'bg';
                $stmt = $pdo->prepare("UPDATE product_descriptions SET short_description = ? WHERE product_id = ? AND language_code = ?");
                $stmt->execute([$short_description, $product_id, $lang]);
                
                // Clear product cache
                $cache_files = glob(CMS_ROOT . '/cache/products_data_*.json');
                foreach ($cache_files as $file) {
                    @unlink($file);
                }
                
                $message = '✅ Продуктът е актуализиран успешно!';
            } catch (Exception $e) {
                error_log('Error in bulk_fix_product: ' . $e->getMessage());
                $message = '❌ Грешка: ' . htmlspecialchars($e->getMessage());
            }
            break;

        case 'fix_all_html_entities':
            try {
                $db = Database::getInstance();
                $pdo = $db->getPDO();
                
                // Fix product names
                $stmt = $pdo->query("SELECT product_id, name, language_code FROM product_descriptions WHERE name LIKE '%&amp;%'");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $fixed_count = 0;
                foreach ($rows as $row) {
                    $decoded_name = html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8');
                    $update = $pdo->prepare("UPDATE product_descriptions SET name = ? WHERE product_id = ? AND language_code = ?");
                    $update->execute([$decoded_name, $row['product_id'], $row['language_code']]);
                    $fixed_count++;
                }
                
                // Clear cache
                $cache_files = glob(CMS_ROOT . '/cache/products_data_*.json');
                foreach ($cache_files as $file) {
                    @unlink($file);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => "✅ Fixed $fixed_count HTML entities!"]);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
                exit;
            }

        case 'remove_all_html_artifacts':
            try {
                $db = Database::getInstance();
                $pdo = $db->getPDO();
                
                // Get all short descriptions with HTML artifacts
                $stmt = $pdo->query("SELECT product_id, short_description, language_code FROM product_descriptions WHERE short_description LIKE '%data-path-to-node%' OR short_description LIKE '%data-index-in-node%'");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $fixed_count = 0;
                foreach ($rows as $row) {
                    $cleaned = preg_replace('/<(\w+)\s+data-[^>]*>/', '<$1>', $row['short_description']);
                    $cleaned = preg_replace('/\sdata-[a-z-]+="[^"]*"/', '', $cleaned);
                    
                    $update = $pdo->prepare("UPDATE product_descriptions SET short_description = ? WHERE product_id = ? AND language_code = ?");
                    $update->execute([$cleaned, $row['product_id'], $row['language_code']]);
                    $fixed_count++;
                }
                
                // Clear cache
                $cache_files = glob(CMS_ROOT . '/cache/products_data_*.json');
                foreach ($cache_files as $file) {
                    @unlink($file);
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => "✅ Cleaned $fixed_count product descriptions!"]);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
                exit;
            }

        case 'refresh_exchange_rate':
            try {
                require_once CMS_ROOT . '/includes/currency-exchange.php';
                refresh_exchange_rate();
                $message = '✅ Обменният курс е актуализиран успешно!';
            } catch (Exception $e) {
                $message = '❌ Грешка при актуализиране: ' . htmlspecialchars($e->getMessage());
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
    
    <!-- Critical CSS inlined for instant render - OPTIMIZED FOR LCP/CLS -->
    <style>
        :root{--color-white:#fff;--primary:#9f7aea;--primary-hover:#b794f4;--bg-body:linear-gradient(135deg,#0f0a1a 0%,#1a1625 50%,#2d1b4e 100%);--bg-primary:#1b1430;--bg-sidebar:#0f0a1a;--bg-secondary:#52456d;--bg-card:rgba(27,20,48,0.95);--bg-hover:#2d1b4e;--text-primary:#fff;--text-secondary:#e8e4f0;--text-muted:#c8c0d8;--border-color:#2d1b4e;--border-light:rgba(159,122,234,0.1);--shadow-md:rgba(0,0,0,0.3);--shadow-xl:rgba(0,0,0,0.5);--color-purple-alpha-20:rgba(159,122,234,0.2)}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:var(--bg-primary);color:var(--text-primary);min-height:100vh}
        .container{display:flex;min-height:100vh;width:100%}
        /* SIDEBAR - Compact design */
        .sidebar{width:220px;min-width:220px;background:var(--bg-sidebar);padding:6px 0;box-shadow:2px 0 20px var(--shadow-md);overflow-y:auto}
        .sidebar h2{padding:0 16px 10px;font-size:14px;border-bottom:1px solid var(--border-light);margin-bottom:0;font-weight:600;color:var(--text-primary)}
        /* SIDEBAR-SECTION - Collapsible with dropdown */
        .sidebar-section{margin-bottom:2px;padding:0}
        .sidebar-section-title{padding:6px 16px 6px 12px;font-size:10px;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:0.5px;margin-bottom:0;cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:all 0.2s ease;user-select:none}
        .sidebar-section-title:hover{background:var(--bg-hover);color:var(--text-primary)}
        .sidebar-section-title::after{content:'▼';font-size:8px;transition:transform 0.2s ease;opacity:0.6}
        .sidebar-section.collapsed .sidebar-section-title::after{transform:rotate(-90deg)}
        .sidebar-section-content{max-height:500px;overflow:hidden;transition:max-height 0.3s ease}
        .sidebar-section.collapsed .sidebar-section-content{max-height:0}
        .sidebar ul{list-style:none;padding:0;margin:0}
        .sidebar li{margin:0}
        .sidebar a{display:flex;align-items:center;gap:8px;padding:6px 16px;color:var(--text-secondary);text-decoration:none;border-left:3px solid transparent;font-size:13px;transition:all 0.2s ease}
        .sidebar a.active{background:var(--primary);color:var(--text-primary);border-left-color:var(--primary-hover)}
        .sidebar a:hover{background:var(--bg-hover);color:var(--text-primary)}
        /* MAIN - Fixed for LCP */
        .main{flex:1;padding:12px;background:var(--bg-primary);min-width:0;overflow-x:hidden}
        /* HEADER - Critical for LCP element (h1) */
        .header{background:var(--bg-card);padding:12px 16px;border-radius:8px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;border:2px solid var(--color-purple-alpha-20);min-height:60px}
        /* H1 - LCP ELEMENT - Must be in critical CSS! */
        h1{font-size:24px;font-weight:600;color:var(--color-white);margin:0;display:flex;align-items:center;gap:8px;line-height:1.2}
        h1 svg{width:24px;height:24px;flex-shrink:0;display:inline-block;vertical-align:middle}
        /* HEADER-ACTIONS - Prevent button shifts */
        .header-actions{display:flex;align-items:center;gap:8px}
        .lang-btn,.action-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--bg-secondary);color:var(--text-primary);cursor:pointer;text-decoration:none;font-size:13px;font-weight:500;white-space:nowrap}
        .logout-btn{padding:6px 12px;border:1px solid var(--border-color);border-radius:6px;background:var(--bg-secondary);color:var(--text-primary);cursor:pointer;font-size:13px;font-weight:500;font-family:inherit}
        .inline-form{display:inline-block;margin:0}
        /* CONTENT */
        .content{background:var(--bg-card);padding:20px;border-radius:12px;box-shadow:0 4px 16px var(--color-purple-alpha-20);border:2px solid var(--color-purple-alpha-20);min-height:400px}
        h2.page-title{font-size:24px;margin-bottom:20px;color:var(--color-white)}
        /* MESSAGE */
        .message{background:var(--bg-secondary);padding:12px 16px;border-radius:6px;margin-bottom:16px;color:var(--text-primary);border-left:4px solid var(--primary)}
        
        /* CONTENT AREA - Forms, Tables, Grids - Critical for CLS fix! */
        .grid{display:grid;gap:15px}
        .grid-1-1-100{grid-template-columns:1fr 1fr 100px;min-height:60px}
        .grid-2{grid-template-columns:1fr 1fr;min-height:60px}
        .grid-3{grid-template-columns:1fr 1fr 1fr;min-height:60px}
        .align-start{align-items:start}
        
        /* FORM ELEMENTS - Prevent shifts */
        .form-group{margin-bottom:15px;min-height:70px}
        .form-actions{display:flex;gap:12px;margin-top:20px;padding-top:15px;border-top:1px solid var(--border-color);min-height:56px;align-items:center}
        label{display:block;margin-bottom:6px;font-weight:600;color:var(--text-secondary);line-height:1.4;min-height:20px}
        input[type="text"],input[type="email"],input[type="password"],input[type="number"],textarea,select{width:100%;padding:8px 10px;border:1px solid var(--border-color);border-radius:6px;font-family:inherit;font-size:14px;background:var(--bg-card);color:var(--text-primary);min-height:36px;line-height:1.4}
        textarea{min-height:80px;resize:vertical}
        
        /* BUTTONS - Fixed dimensions prevent shift */
        .btn,.btn-secondary{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:all 0.2s ease;min-height:40px;line-height:1.4}
        .btn{background:var(--primary);color:var(--text-primary);border:none;box-shadow:0 2px 8px var(--shadow-md)}
        .btn-secondary{background:var(--bg-secondary);color:var(--text-primary);border:2px solid var(--border-color);box-shadow:0 2px 4px var(--shadow-sm)}
        .btn svg,.btn-secondary svg{width:18px;height:18px;flex-shrink:0}
        button{padding:8px 16px;background:var(--primary);color:var(--text-primary);border:none;border-radius:6px;cursor:pointer;font-weight:600;min-height:36px;font-family:inherit;line-height:1.4}
        
        /* TABLES - Row heights prevent collapse */
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px 12px;text-align:left;border-bottom:1px solid var(--border-color);min-height:40px;line-height:1.4}
        th{background:var(--bg-primary);font-weight:600;color:var(--color-white)}
        tr{min-height:40px}
        
        /* TEXT HINTS */
        .hint{display:block;margin-top:5px;color:var(--text-secondary);font-size:13px;line-height:1.4;min-height:18px}
    </style>
    
    <!-- Unified CSS -->
    <link rel="stylesheet" href="../assets/css/themes.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/app.css?v=<?php echo time(); ?>">
    <?php echo get_custom_theme_css(); ?>
    
    <script>
        // Sidebar collapsible sections
        document.addEventListener('DOMContentLoaded', function() {
            // Load collapsed state from localStorage
            const collapsedSections = JSON.parse(localStorage.getItem('sidebar_collapsed') || '[]');
            
            // Apply saved state
            collapsedSections.forEach(sectionIndex => {
                const section = document.querySelector(`.sidebar-section[data-section-index="${sectionIndex}"]`);
                if (section) section.classList.add('collapsed');
            });
            
            // Add click handlers to all section titles
            document.querySelectorAll('.sidebar-section-title').forEach(title => {
                title.addEventListener('click', function() {
                    const section = this.closest('.sidebar-section');
                    const sectionIndex = section.getAttribute('data-section-index');
                    
                    section.classList.toggle('collapsed');
                    
                    // Save state to localStorage
                    let collapsed = JSON.parse(localStorage.getItem('sidebar_collapsed') || '[]');
                    if (section.classList.contains('collapsed')) {
                        if (!collapsed.includes(sectionIndex)) collapsed.push(sectionIndex);
                    } else {
                        collapsed = collapsed.filter(i => i !== sectionIndex);
                    }
                    localStorage.setItem('sidebar_collapsed', JSON.stringify(collapsed));
                });
            });
        });
    </script>
</head>
<body class="admin-page" data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    
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
            <div class="sidebar-section" data-section-index="1">
                <p class="sidebar-section-title">E-Commerce</p>
                <div class="sidebar-section-content">
                    <ul>
                        <li><a href="?section=products" class="<?php echo $section === 'products' ? 'active' : ''; ?>"><?php echo icon_package(18); ?> <?php echo __('menu.products'); ?></a></li>
                        <li><a href="?section=import-products" class="<?php echo $section === 'import-products' ? 'active' : ''; ?>">📥 Импорт</a></li>
                        <li><a href="?section=bulk-edit" class="<?php echo $section === 'bulk-edit' ? 'active' : ''; ?>">🛠️ Бърза корекция</a></li>
                        <li><a href="?section=categories" class="<?php echo $section === 'categories' ? 'active' : ''; ?>"><?php echo icon_folder(18); ?> <?php echo __('admin.categories'); ?></a></li>
                        <li><a href="?section=orders" class="<?php echo $section === 'orders' ? 'active' : ''; ?>"><?php echo icon_cart(18); ?> <?php echo __('menu.orders'); ?></a></li>
                    </ul>
                </div>
            </div>

            <!-- Marketing Section -->
            <div class="sidebar-section" data-section-index="2">
                <p class="sidebar-section-title">Маркетинг</p>
                <div class="sidebar-section-content">
                    <ul>
                        <li><a href="?section=promotions" class="<?php echo $section === 'promotions' ? 'active' : ''; ?>"><?php echo icon_megaphone(18); ?> <?php echo __('admin.promotions'); ?></a></li>
                        <li><a href="?section=discounts" class="<?php echo $section === 'discounts' ? 'active' : ''; ?>"><?php echo icon_percent(18); ?> <?php echo __('admin.discounts'); ?></a></li>
                    </ul>
                </div>
            </div>

            <!-- Content Section -->
            <div class="sidebar-section" data-section-index="3">
                <p class="sidebar-section-title">Съдържание</p>
                <div class="sidebar-section-content">
                    <ul>
                        <li><a href="?section=pages" class="<?php echo $section === 'pages' ? 'active' : ''; ?>"><?php echo icon_home(18); ?> <?php echo __('menu.pages'); ?></a></li>
                        <li><a href="?section=posts" class="<?php echo $section === 'posts' ? 'active' : ''; ?>"><?php echo icon_check(18); ?> <?php echo __('menu.blog_posts'); ?></a></li>
                        <li><a href="?section=media" class="<?php echo $section === 'media' ? 'active' : ''; ?>"><?php echo icon_package(18); ?> <?php echo __('menu.media'); ?></a></li>
                        <li><a href="?section=themes" class="<?php echo $section === 'themes' ? 'active' : ''; ?>"><?php echo icon_edit(18); ?> Themes</a></li>
                    </ul>
                </div>
            </div>

            <!-- Communication Section -->
            <div class="sidebar-section" data-section-index="4">
                <p class="sidebar-section-title">Комуникация</p>
                <div class="sidebar-section-content">
                    <ul>
                        <li><a href="?section=inquiries" class="<?php echo $section === 'inquiries' ? 'active' : ''; ?>"><?php echo icon_mail(18); ?> <?php echo __('inquiry.title'); ?></a></li>
                        <li><a href="?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>"><?php echo icon_user(18); ?> <?php echo __('menu.users'); ?></a></li>
                    </ul>
                </div>
            </div>

            <!-- Business Intelligence Section -->
            <div class="sidebar-section" data-section-index="5">
                <p class="sidebar-section-title">Business Intelligence</p>
                <div class="sidebar-section-content">
                    <ul>
                        <li><a href="?section=analytics" class="<?php echo $section === 'analytics' ? 'active' : ''; ?>">📊 Web Analytics</a></li>
                        <li><a href="?section=financial" class="<?php echo $section === 'financial' ? 'active' : ''; ?>">💰 Financial Data</a></li>
                    </ul>
                </div>
            </div>

            <!-- System Section -->
            <div class="sidebar-section" data-section-index="6">
                <p class="sidebar-section-title">Система</p>
                <div class="sidebar-section-content">
                    <ul>
                        <li><a href="?section=currency-settings" class="<?php echo $section === 'currency-settings' ? 'active' : ''; ?>">💱 Валутни курсове</a></li>
                        <li><a href="?section=database" class="<?php echo $section === 'database' ? 'active' : ''; ?>"><?php echo icon_settings(18); ?> <?php echo __('menu.database'); ?></a></li>
                        <li><a href="?section=database-browser" class="<?php echo $section === 'database-browser' ? 'active' : ''; ?>">🗄️ Database Browser</a></li>
                        <li><a href="?section=settings" class="<?php echo $section === 'settings' ? 'active' : ''; ?>"><?php echo icon_settings(18); ?> <?php echo __('menu.settings'); ?></a></li>
                        <li><a href="?section=tools" class="<?php echo $section === 'tools' ? 'active' : ''; ?>"><?php echo icon_alert(18); ?> <?php echo __('menu.tools'); ?></a></li>
                    </ul>
                </div>
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
                } elseif ($section === 'currency-settings') {
                    include_once 'sections/currency-settings.php';
                } elseif ($section === 'database') {
                    include_once 'sections/database.php';
                } elseif ($section === 'database-browser') {
                    include_once 'sections/database-browser.php';
                } elseif ($section === 'settings') {
                    include_once 'sections/settings.php';
                } elseif ($section === 'tools') {
                    include_once 'sections/tools.php';
                }
                ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/theme-manager.min.js"defer></script>
    <script src="assets/js/admin.js"defer></script>
    <script src="assets/js/mobile-menu.js"></script>
</body>
</html>

