<?php
/**
 * Sync all JSON data to PostgreSQL Database
 * Run this script to migrate all data from JSON files to the database
 */

define('CMS_ROOT', __DIR__);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';

// Check if running from command line
$isCLI = php_sapi_name() === 'cli';

function log_message($message, $isCLI = false) {
    if ($isCLI) {
        echo $message . PHP_EOL;
    } else {
        echo htmlspecialchars($message) . "<br>\n";
    }
}

// Database connection
$db = Database::getInstance();
if ($db->getDriver() !== 'pgsql') {
    log_message("ERROR: Database is not configured for PostgreSQL!", $isCLI);
    log_message("Please check config/database.json and ensure PostgreSQL is configured.", $isCLI);
    exit(1);
}

log_message("=== Starting JSON to PostgreSQL Migration ===", $isCLI);
log_message("", $isCLI);

// 1. Migrate Admins
log_message("Migrating Admins...", $isCLI);
$adminsJson = @file_get_contents('storage/admins.json');
if ($adminsJson) {
    $admins = json_decode($adminsJson, true) ?: [];
    $adminTable = $db->table('admins');
    
    foreach ($admins as $admin) {
        // Check if admin exists
        $existing = $adminTable->find('username', $admin['username']);
        if ($existing) {
            log_message("  - Admin '{$admin['username']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert admin
        $adminTable->insert([
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'] ?? '',
            'password' => $admin['password'],
            'role' => $admin['role'] ?? 'admin',
            'created_at' => $admin['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated admin: {$admin['username']}", $isCLI);
    }
}
log_message("", $isCLI);

// 2. Migrate Customers
log_message("Migrating Customers...", $isCLI);
$customersJson = @file_get_contents('storage/customers.json');
if ($customersJson) {
    $customers = json_decode($customersJson, true) ?: [];
    $customerTable = $db->table('customers');
    
    foreach ($customers as $customer) {
        // Check if customer exists
        $existing = $customerTable->find('username', $customer['username']);
        if ($existing) {
            log_message("  - Customer '{$customer['username']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert customer
        $customerTable->insert([
            'id' => $customer['id'],
            'username' => $customer['username'],
            'email' => $customer['email'],
            'password' => $customer['password'],
            'first_name' => $customer['first_name'] ?? '',
            'last_name' => $customer['last_name'] ?? '',
            'phone' => $customer['phone'] ?? '',
            'address' => $customer['address'] ?? '',
            'city' => $customer['city'] ?? '',
            'postal_code' => $customer['postal_code'] ?? '',
            'country' => $customer['country'] ?? 'Bulgaria',
            'status' => ($customer['activated'] ?? false) ? 'active' : 'pending',
            'email_verified' => $customer['activated'] ?? false,
            'activation_token' => $customer['activation_token'] ?? null,
            'created_at' => $customer['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated customer: {$customer['username']}", $isCLI);
    }
}
log_message("", $isCLI);

// 3. Migrate Products
log_message("Migrating Products...", $isCLI);
$productsJson = @file_get_contents('storage/products.json');
if ($productsJson) {
    $products = json_decode($productsJson, true) ?: [];
    $productTable = $db->table('products');
    
    foreach ($products as $product) {
        // Check if product exists
        $existing = $productTable->find('id', $product['id']);
        if ($existing) {
            log_message("  - Product '{$product['name']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert product
        $productTable->insert([
            'id' => $product['id'],
            'slug' => $product['slug'],
            'name' => $product['name'],
            'description' => $product['description'] ?? '',
            'price' => $product['price'] ?? 0,
            'compare_price' => $product['compare_price'] ?? null,
            'category' => $product['category'] ?? '',
            'images' => json_encode($product['images'] ?? []),
            'stock' => $product['stock'] ?? 0,
            'sku' => $product['sku'] ?? '',
            'status' => $product['status'] ?? 'published',
            'featured' => $product['featured'] ?? false,
            'meta_description' => $product['meta_description'] ?? '',
            'created_at' => $product['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated product: {$product['name']}", $isCLI);
    }
}
log_message("", $isCLI);

// 4. Migrate Categories
log_message("Migrating Categories...", $isCLI);
$categoriesJson = @file_get_contents('storage/categories.json');
if ($categoriesJson) {
    $categories = json_decode($categoriesJson, true) ?: [];
    $categoryTable = $db->table('categories');
    
    foreach ($categories as $category) {
        // Check if category exists
        $existing = $categoryTable->find('id', $category['id']);
        if ($existing) {
            log_message("  - Category '{$category['name']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert category
        $categoryTable->insert([
            'id' => $category['id'],
            'slug' => $category['slug'],
            'name' => $category['name'],
            'description' => $category['description'] ?? '',
            'image' => $category['image'] ?? '',
            'parent_id' => $category['parent_id'] ?? null,
            'sort_order' => $category['sort_order'] ?? 0,
            'status' => $category['status'] ?? 'active',
            'created_at' => $category['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated category: {$category['name']}", $isCLI);
    }
}
log_message("", $isCLI);

// 5. Migrate Orders
log_message("Migrating Orders...", $isCLI);
$ordersJson = @file_get_contents('storage/orders.json');
if ($ordersJson) {
    $orders = json_decode($ordersJson, true) ?: [];
    $orderTable = $db->table('orders');
    
    foreach ($orders as $order) {
        // Check if order exists
        $existing = $orderTable->find('id', $order['id']);
        if ($existing) {
            log_message("  - Order '{$order['order_number']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert order
        $orderTable->insert([
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'customer_id' => $order['customer_id'] ?? null,
            'customer_email' => $order['customer_email'] ?? '',
            'customer_name' => $order['customer_name'] ?? '',
            'customer_phone' => $order['customer_phone'] ?? '',
            'shipping_address' => $order['shipping_address'] ?? '',
            'shipping_city' => $order['shipping_city'] ?? '',
            'shipping_postal_code' => $order['shipping_postal_code'] ?? '',
            'shipping_country' => $order['shipping_country'] ?? 'Bulgaria',
            'items' => json_encode($order['items'] ?? []),
            'subtotal' => $order['subtotal'] ?? 0,
            'shipping_cost' => $order['shipping_cost'] ?? 0,
            'discount' => $order['discount'] ?? 0,
            'total' => $order['total'] ?? 0,
            'status' => $order['status'] ?? 'pending',
            'payment_method' => $order['payment_method'] ?? '',
            'payment_status' => $order['payment_status'] ?? 'pending',
            'tracking_number' => $order['tracking_number'] ?? '',
            'notes' => $order['notes'] ?? '',
            'created_at' => $order['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated order: {$order['order_number']}", $isCLI);
    }
}
log_message("", $isCLI);

// 6. Migrate Pages
log_message("Migrating Pages...", $isCLI);
$pagesJson = @file_get_contents('storage/pages.json');
if ($pagesJson) {
    $pages = json_decode($pagesJson, true) ?: [];
    $pageTable = $db->table('pages');
    
    foreach ($pages as $page) {
        // Generate ID if missing
        if (empty($page['id'])) {
            $page['id'] = uniqid('page_');
        }
        
        // Check if page exists
        $existing = $pageTable->find('id', $page['id']);
        if (!$existing) {
            $existing = $pageTable->find('slug', $page['slug']);
        }
        if ($existing) {
            log_message("  - Page '{$page['title']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert page
        $pageTable->insert([
            'id' => $page['id'],
            'slug' => $page['slug'],
            'title' => $page['title'],
            'content' => $page['content'] ?? '',
            'meta_description' => $page['meta_description'] ?? '',
            'status' => $page['status'] ?? 'published',
            'created_at' => $page['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated page: {$page['title']}", $isCLI);
    }
}
log_message("", $isCLI);

// 7. Migrate Posts
log_message("Migrating Posts...", $isCLI);
$postsJson = @file_get_contents('storage/posts.json');
if ($postsJson) {
    $posts = json_decode($postsJson, true) ?: [];
    $postTable = $db->table('posts');
    
    foreach ($posts as $post) {
        // Generate ID if missing
        if (empty($post['id'])) {
            $post['id'] = uniqid('post_');
        }
        
        // Check if post exists
        $existing = $postTable->find('id', $post['id']);
        if (!$existing) {
            $existing = $postTable->find('slug', $post['slug']);
        }
        if ($existing) {
            log_message("  - Post '{$post['title']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert post
        $postTable->insert([
            'id' => $post['id'],
            'slug' => $post['slug'],
            'title' => $post['title'],
            'content' => $post['content'] ?? '',
            'excerpt' => $post['excerpt'] ?? '',
            'meta_description' => $post['meta_description'] ?? '',
            'featured_image' => $post['featured_image'] ?? '',
            'category' => $post['category'] ?? '',
            'status' => $post['status'] ?? 'published',
            'created_at' => $post['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated post: {$post['title']}", $isCLI);
    }
}
log_message("", $isCLI);

// 8. Migrate Inquiries
log_message("Migrating Inquiries...", $isCLI);
$inquiriesJson = @file_get_contents('storage/inquiries.json');
if ($inquiriesJson) {
    $inquiries = json_decode($inquiriesJson, true) ?: [];
    $inquiryTable = $db->table('inquiries');
    
    foreach ($inquiries as $inquiry) {
        // Check if inquiry exists
        $existing = $inquiryTable->find('id', $inquiry['id']);
        if ($existing) {
            log_message("  - Inquiry from '{$inquiry['name']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert inquiry
        $inquiryTable->insert([
            'id' => $inquiry['id'],
            'name' => $inquiry['name'],
            'email' => $inquiry['email'],
            'phone' => $inquiry['phone'] ?? '',
            'subject' => $inquiry['subject'] ?? '',
            'message' => $inquiry['message'],
            'status' => $inquiry['status'] ?? 'new',
            'notes' => $inquiry['notes'] ?? '',
            'created_at' => $inquiry['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated inquiry from: {$inquiry['name']}", $isCLI);
    }
}
log_message("", $isCLI);

// 9. Migrate Discounts
log_message("Migrating Discounts...", $isCLI);
$discountsJson = @file_get_contents('storage/discounts.json');
if ($discountsJson) {
    $discounts = json_decode($discountsJson, true) ?: [];
    $discountTable = $db->table('discounts');
    
    foreach ($discounts as $discount) {
        // Check if discount exists
        $existing = $discountTable->find('id', $discount['id']);
        if ($existing) {
            log_message("  - Discount '{$discount['code']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert discount
        $discountTable->insert([
            'id' => $discount['id'],
            'code' => $discount['code'],
            'type' => $discount['type'] ?? 'percentage',
            'value' => $discount['value'] ?? 0,
            'min_purchase' => $discount['min_purchase'] ?? 0,
            'max_uses' => $discount['max_uses'] ?? null,
            'uses_count' => $discount['uses_count'] ?? 0,
            'valid_from' => $discount['valid_from'] ?? null,
            'valid_until' => $discount['valid_until'] ?? null,
            'status' => $discount['status'] ?? 'active',
            'description' => $discount['description'] ?? '',
            'created_at' => $discount['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated discount: {$discount['code']}", $isCLI);
    }
}
log_message("", $isCLI);

// 10. Migrate Promotions
log_message("Migrating Promotions...", $isCLI);
$promotionsJson = @file_get_contents('storage/promotions.json');
if ($promotionsJson) {
    $promotions = json_decode($promotionsJson, true) ?: [];
    $promotionTable = $db->table('promotions');
    
    foreach ($promotions as $promotion) {
        // Check if promotion exists
        $existing = $promotionTable->find('id', $promotion['id']);
        if ($existing) {
            log_message("  - Promotion '{$promotion['title']}' already exists, skipping", $isCLI);
            continue;
        }
        
        // Insert promotion
        $promotionTable->insert([
            'id' => $promotion['id'],
            'title' => $promotion['title'],
            'description' => $promotion['description'] ?? '',
            'discount_percentage' => $promotion['discount_percentage'] ?? null,
            'applies_to' => $promotion['applies_to'] ?? 'all',
            'target_ids' => json_encode($promotion['target_ids'] ?? []),
            'valid_from' => $promotion['valid_from'] ?? null,
            'valid_until' => $promotion['valid_until'] ?? null,
            'status' => $promotion['status'] ?? 'active',
            'created_at' => $promotion['created'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        log_message("  ✓ Migrated promotion: {$promotion['title']}", $isCLI);
    }
}
log_message("", $isCLI);

log_message("=== Migration Complete ===", $isCLI);
log_message("All JSON data has been migrated to PostgreSQL!", $isCLI);
log_message("", $isCLI);
log_message("IMPORTANT: After verifying the migration, you should:", $isCLI);
log_message("1. Backup the storage/*.json files", $isCLI);
log_message("2. Clear or delete the JSON files to prevent conflicts", $isCLI);
log_message("3. Test all application functionality", $isCLI);
