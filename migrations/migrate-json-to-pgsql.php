<?php
/**
 * Migration Script: JSON → PostgreSQL
 * Run this ONCE after setting up PostgreSQL database
 * 
 * Usage: php migrations/migrate-json-to-pgsql.php
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

echo "========================================\n";
echo "  JSON to PostgreSQL Migration Script  \n";
echo "========================================\n\n";

// Check if DATABASE_URL is set
$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    echo "❌ ERROR: DATABASE_URL environment variable not set!\n";
    echo "Please set DATABASE_URL in Render dashboard or .env file\n";
    exit(1);
}

echo "✅ DATABASE_URL found\n";

// Connect to PostgreSQL
try {
    $parts = parse_url($databaseUrl);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 5432;
    $database = ltrim($parts['path'] ?? '', '/');
    $user = $parts['user'] ?? '';
    $password = $parts['pass'] ?? '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to PostgreSQL: {$database}\n\n";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Create schema first
echo "Creating schema...\n";
$schemaFile = CMS_ROOT . '/migrations/postgresql-schema.sql';
if (file_exists($schemaFile)) {
    $schema = file_get_contents($schemaFile);
    try {
        $pdo->exec($schema);
        echo "✅ Schema created successfully\n\n";
    } catch (PDOException $e) {
        echo "⚠️  Schema error (might already exist): " . $e->getMessage() . "\n\n";
    }
} else {
    echo "⚠️  Schema file not found: {$schemaFile}\n\n";
}

// Migration functions
function migrateTable($pdo, $tableName, $jsonData, $columns) {
    if (empty($jsonData)) {
        echo "⚠️  {$tableName}: No data to migrate\n";
        return 0;
    }

    $count = 0;
    foreach ($jsonData as $id => $row) {
        try {
            // Ensure id is set
            if (!isset($row['id'])) {
                $row['id'] = is_string($id) ? $id : uniqid();
            }

            // Filter only specified columns
            $data = [];
            foreach ($columns as $col) {
                if (isset($row[$col])) {
                    $data[$col] = $row[$col];
                }
            }

            if (empty($data)) continue;

            // Build INSERT query
            $keys = array_keys($data);
            $placeholders = array_fill(0, count($keys), '?');
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (id) DO NOTHING",
                $tableName,
                implode(', ', $keys),
                implode(', ', $placeholders)
            );

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $count++;
        } catch (PDOException $e) {
            echo "  ⚠️  Error inserting row: " . $e->getMessage() . "\n";
        }
    }

    echo "✅ {$tableName}: Migrated {$count} records\n";
    return $count;
}

// Migrate Products
echo "Migrating products...\n";
$products = load_json('storage/products.json');
migrateTable($pdo, 'products', $products, [
    'id', 'slug', 'name', 'description', 'price', 'compare_price',
    'category', 'images', 'stock', 'sku', 'status', 'featured',
    'meta_description', 'created_at', 'updated_at'
]);

// Migrate Categories
echo "Migrating categories...\n";
$categories = load_json('storage/categories.json');
migrateTable($pdo, 'categories', $categories, [
    'id', 'slug', 'name', 'description', 'image', 'parent_id',
    'sort_order', 'status', 'created_at', 'updated_at'
]);

// Migrate Customers
echo "Migrating customers...\n";
$customers = load_json('storage/customers.json');
migrateTable($pdo, 'customers', $customers, [
    'id', 'username', 'email', 'password', 'first_name', 'last_name',
    'phone', 'address', 'city', 'postal_code', 'country', 'status',
    'email_verified', 'activation_token', 'reset_token', 'reset_expires',
    'created_at', 'updated_at'
]);

// Migrate Admins
echo "Migrating admins...\n";
$admins = load_json('storage/admins.json');
migrateTable($pdo, 'admins', $admins, [
    'id', 'username', 'email', 'password', 'role', 'created_at', 'updated_at'
]);

// Migrate Orders
echo "Migrating orders...\n";
$orders = load_json('storage/orders.json');
migrateTable($pdo, 'orders', $orders, [
    'id', 'order_number', 'customer_id', 'customer_email', 'customer_name',
    'customer_phone', 'shipping_address', 'shipping_city', 'shipping_postal_code',
    'shipping_country', 'items', 'subtotal', 'shipping_cost', 'discount',
    'total', 'status', 'payment_method', 'payment_status', 'tracking_number',
    'notes', 'created_at', 'updated_at'
]);

// Migrate Inquiries
echo "Migrating inquiries...\n";
$inquiries = load_json('storage/inquiries.json');
migrateTable($pdo, 'inquiries', $inquiries, [
    'id', 'name', 'email', 'phone', 'subject', 'message', 'status',
    'notes', 'created_at', 'updated_at'
]);

// Migrate Discounts
echo "Migrating discounts...\n";
$discounts = load_json('storage/discounts.json');
migrateTable($pdo, 'discounts', $discounts, [
    'id', 'code', 'type', 'value', 'min_purchase', 'max_uses', 'uses_count',
    'valid_from', 'valid_until', 'status', 'description', 'created_at', 'updated_at'
]);

// Migrate Promotions
echo "Migrating promotions...\n";
$promotions = load_json('storage/promotions.json');
migrateTable($pdo, 'promotions', $promotions, [
    'id', 'title', 'description', 'discount_percentage', 'applies_to',
    'target_ids', 'valid_from', 'valid_until', 'status', 'created_at', 'updated_at'
]);

// Migrate Pages
echo "Migrating pages...\n";
$pages = load_json('storage/pages.json');
migrateTable($pdo, 'pages', $pages, [
    'id', 'slug', 'title', 'content', 'meta_description', 'status',
    'created_at', 'updated_at'
]);

// Migrate Posts
echo "Migrating posts...\n";
$posts = load_json('storage/posts.json');
migrateTable($pdo, 'posts', $posts, [
    'id', 'slug', 'title', 'content', 'excerpt', 'meta_description',
    'featured_image', 'category', 'status', 'created_at', 'updated_at'
]);

// Migrate Options
echo "Migrating options...\n";
$options = load_json('storage/options.json');
if (!empty($options)) {
    $count = 0;
    foreach ($options as $key => $value) {
        try {
            $stmt = $pdo->prepare("INSERT INTO options (option_key, option_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT (option_key) DO UPDATE SET option_value = EXCLUDED.option_value, updated_at = CURRENT_TIMESTAMP");
            $stmt->execute([$key, $value]);
            $count++;
        } catch (PDOException $e) {
            echo "  ⚠️  Error inserting option {$key}: " . $e->getMessage() . "\n";
        }
    }
    echo "✅ options: Migrated {$count} records\n";
}

echo "\n========================================\n";
echo "  Migration Complete!                  \n";
echo "========================================\n\n";
echo "Next steps:\n";
echo "1. Verify data in PostgreSQL database\n";
echo "2. System will automatically use PostgreSQL when DATABASE_URL is set\n";
echo "3. JSON files in storage/ will be kept as backup\n\n";
