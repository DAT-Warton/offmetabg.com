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
    'categories' => __('admin.categories'),
    'orders' => __('menu.orders'),
    'promotions' => __('admin.promotions'),
    'discounts' => __('admin.discounts'),
    'pages' => __('menu.pages'),
    'posts' => __('menu.blog_posts'),
    'media' => __('menu.media'),
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
            if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
                $uploadDir = CMS_ROOT . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = basename($_FILES['media']['name']);
                $targetPath = $uploadDir . $fileName;

                // Check if file already exists
                if (file_exists($targetPath)) {
                    $fileName = time() . '_' . $fileName;
                    $targetPath = $uploadDir . $fileName;
                }

                if (move_uploaded_file($_FILES['media']['tmp_name'], $targetPath)) {
                    $message = __('admin.file_uploaded') . ': ' . htmlspecialchars($fileName);
                } else {
                    $message = __('admin.upload_error');
                }
            } else {
                $message = __('admin.no_file_selected');
            }
            break;

        case 'save_product':
            $products = load_json('storage/products.json');
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
            
            $products[$product_id] = [
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
                'created' => $products[$product_id]['created'] ?? date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ];
            save_json('storage/products.json', $products);
            
            // Update category product counts
            update_category_product_counts();
            
            $message = __('admin.product_saved');
            break;

        case 'delete_product':
            $products = load_json('storage/products.json');
            $product_id = $_POST['product_id'] ?? '';
            unset($products[$product_id]);
            save_json('storage/products.json', $products);
            
            // Update category product counts
            update_category_product_counts();
            
            $message = __('admin.product_deleted');
            break;

        case 'save_customer':
            $customers = load_json('storage/customers.json');
            $customer_id = $_POST['customer_id'] ?: uniqid('cust_');
            $customers[$customer_id] = [
                'id' => $customer_id,
                'username' => sanitize($_POST['username']),
                'email' => sanitize($_POST['email']),
                'password' => !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : ($customers[$customer_id]['password'] ?? ''),
                'role' => sanitize($_POST['role'] ?? 'customer'),
                'permissions' => $_POST['permissions'] ?? ['view_products', 'place_orders'],
                'created' => $customers[$customer_id]['created'] ?? date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ];
            save_json('storage/customers.json', $customers);
            $message = __('admin.customer_saved');
            break;

        case 'delete_customer':
            $customers = load_json('storage/customers.json');
            $customer_id = $_POST['customer_id'] ?? '';
            unset($customers[$customer_id]);
            save_json('storage/customers.json', $customers);
            $message = __('admin.customer_deleted');
            break;

        case 'update_order_status':
            $orders = load_json('storage/orders.json');
            $order_id = $_POST['order_id'] ?? '';
            if (isset($orders[$order_id])) {
                $orders[$order_id]['status'] = $_POST['status'];
                $orders[$order_id]['updated'] = date('Y-m-d H:i:s');
                save_json('storage/orders.json', $orders);
                $message = __('admin.order_status_updated');
            }
            break;

        case 'delete_order':
            $orders = load_json('storage/orders.json');
            $order_id = $_POST['order_id'] ?? '';
            unset($orders[$order_id]);
            save_json('storage/orders.json', $orders);
            $message = __('admin.order_deleted');
            break;

        // Categories Management
        case 'save_category':
            $categories = load_json('storage/categories.json');
            $category_id = $_POST['category_id'] ?: uniqid('cat_');
            $categories[$category_id] = [
                'id' => $category_id,
                'name' => sanitize($_POST['name']),
                'slug' => sanitize($_POST['slug']),
                'description' => sanitize($_POST['description'] ?? ''),
                'parent_id' => sanitize($_POST['parent_id'] ?? ''),
                'icon' => sanitize($_POST['icon'] ?? ''),
                'order' => intval($_POST['order'] ?? 0),
                'active' => isset($_POST['active']),
                'product_count' => $categories[$category_id]['product_count'] ?? 0,
                'created' => $categories[$category_id]['created'] ?? date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ];
            save_json('storage/categories.json', $categories);
            
            // Update product counts for all categories
            update_category_product_counts();
            
            $message = 'Категорията е запазена успешно';
            break;

        case 'delete_category':
            $categories = load_json('storage/categories.json');
            $category_id = $_POST['category_id'] ?? '';
            unset($categories[$category_id]);
            save_json('storage/categories.json', $categories);
            
            // Update product counts for remaining categories
            update_category_product_counts();
            
            $message = 'Категорията е изтрита';
            break;

        // Promotions Management
        case 'save_promotion':
            $promotions = load_json('storage/promotions.json');
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
                'created' => $promotions[$promotion_id]['created'] ?? date('Y-m-d H:i:s'),
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
            
            $promotions[$promotion_id] = $promotion;
            save_json('storage/promotions.json', $promotions);
            $message = 'Промоцията е запазена успешно';
            break;

        case 'delete_promotion':
            $promotions = load_json('storage/promotions.json');
            $promotion_id = $_POST['promotion_id'] ?? '';
            unset($promotions[$promotion_id]);
            save_json('storage/promotions.json', $promotions);
            $message = 'Промоцията е изтрита';
            break;

        // Discounts Management
        case 'save_discount':
            $discounts = load_json('storage/discounts.json');
            $discount_id = $_POST['discount_id'] ?: uniqid('disc_');
            $discounts[$discount_id] = [
                'id' => $discount_id,
                'code' => strtoupper(sanitize($_POST['code'])),
                'description' => sanitize($_POST['description'] ?? ''),
                'type' => sanitize($_POST['type']),
                'value' => floatval($_POST['value']),
                'min_purchase' => floatval($_POST['min_purchase'] ?? 0),
                'max_uses' => intval($_POST['max_uses'] ?? 0),
                'used_count' => $discounts[$discount_id]['used_count'] ?? 0,
                'start_date' => sanitize($_POST['start_date'] ?? ''),
                'end_date' => sanitize($_POST['end_date'] ?? ''),
                'active' => isset($_POST['active']),
                'first_purchase_only' => isset($_POST['first_purchase_only']),
                'created' => $discounts[$discount_id]['created'] ?? date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ];
            save_json('storage/discounts.json', $discounts);
            $message = 'Отстъпката е запазена успешно';
            break;

        case 'delete_discount':
            $discounts = load_json('storage/discounts.json');
            $discount_id = $_POST['discount_id'] ?? '';
            unset($discounts[$discount_id]);
            save_json('storage/discounts.json', $discounts);
            $message = 'Отстъпката е изтрита';
            break;
        
        case 'update_inquiry_status':
            $inquiries = load_json('storage/inquiries.json');
            $inquiry_id = $_POST['inquiry_id'] ?? '';
            if (isset($inquiries[$inquiry_id])) {
                $inquiries[$inquiry_id]['status'] = $_POST['status'];
                $inquiries[$inquiry_id]['updated'] = date('Y-m-d H:i:s');
                save_json('storage/inquiries.json', $inquiries);
                $message = 'Статусът на запитването е актуализиран';
            }
            break;
        
        case 'delete_inquiry':
            $inquiries = load_json('storage/inquiries.json');
            $inquiry_id = $_POST['inquiry_id'] ?? '';
            unset($inquiries[$inquiry_id]);
            save_json('storage/inquiries.json', $inquiries);
            $message = 'Запитването е изтрито';
            break;

        default:
            // Unknown action - do nothing
            break;
    }
}

$stats = get_dashboard_stats();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin.cms_dashboard'); ?></title>
    <link rel="stylesheet" href="../assets/css/dark-theme.css" id="dark-theme-style" disabled>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #f5f7fa;
            --bg-secondary: #ffffff;
            --bg-sidebar: #2c3e50;
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
            --shadow: rgba(0, 0, 0, 0.05);
            --primary: #3498db;
            --primary-hover: #5568d3;
            --danger: #dc3545;
            --success-bg: #d4edda;
            --success-border: #c3e6cb;
            --success-text: #155724;
        }
        
        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-sidebar: #1f1f1f;
            --text-primary: #f5f5f5;
            --text-secondary: #d4d4d4;
            --border-color: #404040;
            --shadow: rgba(0, 0, 0, 0.5);
            --success-bg: #1e4620;
            --success-border: #2d5a2f;
            --success-text: #7bc67e;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px var(--shadow);
            transition: background-color 0.3s ease;
        }
        .sidebar h2 {
            padding: 0 20px 15px;
            font-size: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
            margin-bottom: 0;
        }
        .sidebar-section {
            margin-bottom: 20px;
        }
        .sidebar-section-title {
            padding: 12px 20px 8px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            margin: 0;
        }
        .sidebar ul { 
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .sidebar li { margin: 0; }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 14px;
        }
        .sidebar a:hover {
            background: rgba(102, 126, 234, 0.2);
            color: white;
        }
        .sidebar a.active {
            background: var(--primary);
            border-left-color: #fff;
            color: white;
        }
        .main {
            flex: 1;
            padding: 30px;
        }
        .header {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px var(--shadow);
            transition: background-color 0.3s ease;
        }
        h1 { font-size: 28px; color: var(--text-primary); transition: color 0.3s ease; }
        h2, h3, h4 { color: var(--text-primary); transition: color 0.3s ease; }
        .logout-btn {
            padding: 10px 20px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow);
        }
        .stat-card h3 { color: var(--text-secondary); font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: var(--primary); }
        .content {
            background: var(--bg-secondary);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow);
            transition: background-color 0.3s ease;
        }
        .message {
            padding: 15px;
            background: var(--success-bg, #d4edda);
            border: 1px solid var(--success-border, #c3e6cb);
            border-radius: 6px;
            color: var(--success-text, #155724);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            font-size: 13px;
            color: var(--text-secondary, #666);
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-secondary, #555);
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.2s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea { min-height: 200px; resize: vertical; }
        input[type="datetime-local"] {
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary, #333);
            background: var(--bg-secondary, white);
            border: 2px solid var(--border-color, #e0e0e0);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s;
            filter: var(--text-primary) === '#f5f5f5' ? invert(1) : invert(0);
        }
        input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        input[type="datetime-local"]:hover {
            border-color: var(--primary, #3498db);
        }
        input[type="datetime-local"]:focus {
            border-color: var(--primary, #3498db);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            background: var(--bg-secondary, white);
        }
        input[type="number"] {
            font-variant-numeric: tabular-nums;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color, #e0e0e0);
        }
        button {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }
        button:hover { 
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: var(--bg-primary);
            font-weight: 600;
            color: var(--text-primary);
        }
        tr:hover {
            background: var(--bg-primary);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color, #e0e0e0);
        }
        .section-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary, #333);
            margin: 0;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary, #3498db) 0%, #2980b9 100%);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.25);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn svg {
            flex-shrink: 0;
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--bg-secondary, #f5f5f5);
            color: var(--text-primary, #333);
            text-decoration: none;
            border: 2px solid var(--border-color, #e0e0e0);
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .btn-secondary:hover {
            background: var(--bg-primary, #ebebeb);
            border-color: var(--primary, #3498db);
            color: var(--primary, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-secondary svg {
            flex-shrink: 0;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            background: var(--primary, #3498db);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-small:hover {
            background: var(--primary-hover, #5568d3);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        }
        .btn-delete {
            background: var(--danger, #dc3545);
        }
        .btn-delete:hover {
            background: #c82333;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
        }
        .theme-btn,
        .lang-btn {
            transition: all 0.2s;
        }
        .theme-btn:hover,
        .lang-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; }
            .header { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2><?php echo icon_settings(22); ?> CMS</h2>
            
            <!-- Overview Section -->
            <div class="sidebar-section">
                <ul>
                    <li><a href="../" style="background: rgba(102, 126, 234, 0.2); border-left-color: var(--primary, #3498db);"><?php echo icon_home(18); ?> <?php echo __('menu.back_to_site'); ?></a></li>
                    <li><a href="?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>"><?php echo icon_package(18); ?> <?php echo __('menu.dashboard'); ?></a></li>
                </ul>
            </div>

            <!-- E-commerce Section -->
            <div class="sidebar-section">
                <p class="sidebar-section-title">E-Commerce</p>
                <ul>
                    <li><a href="?section=products" class="<?php echo $section === 'products' ? 'active' : ''; ?>"><?php echo icon_package(18); ?> <?php echo __('menu.products'); ?></a></li>
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
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" onclick="toggleTheme()" class="theme-btn" title="<?php echo __('theme.switch_to_dark'); ?>" style="padding: 8px 16px; border-radius: 6px; background: var(--primary); color: white; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px;">
                        <span id="theme-icon"><?php echo icon_moon(18); ?></span>
                    </button>
                    <a href="?section=<?php echo htmlspecialchars($section); ?>&lang=<?php echo opposite_lang(); ?>" 
                       class="lang-btn" 
                       title="Switch to <?php echo lang_name(opposite_lang()); ?>"
                       style="padding: 8px 16px; border-radius: 6px; background: var(--primary); color: white; text-decoration: none; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 6px;">
                        <?php echo lang_flag(opposite_lang()); ?> <?php echo strtoupper(opposite_lang()); ?>
                    </a>
                    <form method="POST" style="display: inline;">
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
    <script>
        // Admin-specific theme toggle
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                if (newTheme === 'dark') {
                    themeIcon.innerHTML = <?php echo json_encode(icon_sun(18)); ?>;
                } else {
                    themeIcon.innerHTML = <?php echo json_encode(icon_moon(18)); ?>;
                }
            }
            
            // Enable/disable dark theme stylesheet
            const darkThemeStyle = document.getElementById('dark-theme-style');
            if (darkThemeStyle) {
                darkThemeStyle.disabled = (newTheme !== 'dark');
            }
        }
        
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.body.setAttribute('data-theme', savedTheme);
            
            const darkThemeStyle = document.getElementById('dark-theme-style');
            if (darkThemeStyle) {
                darkThemeStyle.disabled = (savedTheme !== 'dark');
            }
            
            // Update icon based on current theme
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                if (savedTheme === 'dark') {
                    themeIcon.innerHTML = <?php echo json_encode(icon_sun(18)); ?>;
                } else {
                    themeIcon.innerHTML = <?php echo json_encode(icon_moon(18)); ?>;
                }
            }
        });
    </script>
</body>
</html>

