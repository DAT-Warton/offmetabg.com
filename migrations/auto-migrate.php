<?php
/**
 * Auto-Migration Script
 * Runs automatically on first deploy when DATABASE_URL is set
 * Checks if database is empty and migrates JSON data if needed
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/functions.php';

// Check if DATABASE_URL is set
$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    echo "No DATABASE_URL found - using JSON storage\n";
    exit(0);
}

echo "DATABASE_URL detected - checking if migration needed...\n";

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
    
    echo "Connected to PostgreSQL database: {$database}\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "Falling back to JSON storage\n";
    exit(0);
}

// Check if products table exists and has data
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "Database already has {$count} products - migration not needed\n";
        exit(0);
    }
    
    echo "Database is empty - starting migration...\n";
} catch (PDOException $e) {
    // Table doesn't exist - create schema first
    echo "Tables don't exist - creating schema...\n";
    
    $schemaFile = CMS_ROOT . '/migrations/postgresql-schema.sql';
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        try {
            $pdo->exec($schema);
            echo "Schema created successfully\n";
        } catch (PDOException $e) {
            echo "Schema creation error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

// Run migration
echo "\n=== Starting JSON to PostgreSQL Migration ===\n\n";

function migrateTable($pdo, $tableName, $jsonFile, $columns) {
    $jsonData = load_json($jsonFile);
    
    if (empty($jsonData)) {
        echo "{$tableName}: No data\n";
        return 0;
    }

    $count = 0;
    foreach ($jsonData as $id => $row) {
        try {
            if (!isset($row['id'])) {
                $row['id'] = is_string($id) ? $id : uniqid();
            }

            $data = [];
            foreach ($columns as $col) {
                if (isset($row[$col])) {
                    $data[$col] = $row[$col];
                }
            }

            if (empty($data)) continue;

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
            // Silent error - continue with next row
        }
    }

    echo "{$tableName}: {$count} records\n";
    return $count;
}

// Migrate all tables
migrateTable($pdo, 'products', 'storage/products.json', [
    'id', 'slug', 'name', 'description', 'price', 'compare_price',
    'category', 'images', 'stock', 'sku', 'status', 'featured',
    'meta_description', 'created_at', 'updated_at'
]);

migrateTable($pdo, 'categories', 'storage/categories.json', [
    'id', 'slug', 'name', 'description', 'image', 'parent_id',
    'sort_order', 'status', 'created_at', 'updated_at'
]);

migrateTable($pdo, 'customers', 'storage/customers.json', [
    'id', 'username', 'email', 'password', 'first_name', 'last_name',
    'phone', 'address', 'city', 'postal_code', 'country', 'status',
    'email_verified', 'activation_token', 'reset_token', 'reset_expires',
    'created_at', 'updated_at'
]);

migrateTable($pdo, 'admins', 'storage/admins.json', [
    'id', 'username', 'email', 'password', 'role', 'created_at', 'updated_at'
]);

migrateTable($pdo, 'orders', 'storage/orders.json', [
    'id', 'order_number', 'customer_id', 'customer_email', 'customer_name',
    'customer_phone', 'shipping_address', 'shipping_city', 'shipping_postal_code',
    'shipping_country', 'items', 'subtotal', 'shipping_cost', 'discount',
    'total', 'status', 'payment_method', 'payment_status', 'tracking_number',
    'notes', 'created_at', 'updated_at'
]);

migrateTable($pdo, 'inquiries', 'storage/inquiries.json', [
    'id', 'name', 'email', 'phone', 'subject', 'message', 'status',
    'notes', 'created_at', 'updated_at'
]);

migrateTable($pdo, 'discounts', 'storage/discounts.json', [
    'id', 'code', 'type', 'value', 'min_purchase', 'max_uses', 'uses_count',
    'valid_from', 'valid_until', 'status', 'description', 'created_at', 'updated_at'
]);

migrateTable($pdo, 'promotions', 'storage/promotions.json', [
    'id', 'title', 'description', 'discount_percentage', 'applies_to',
    'target_ids', 'valid_from', 'valid_until', 'status', 'created_at', 'updated_at'
]);

migrateTable($pdo, 'pages', 'storage/pages.json', [
    'id', 'slug', 'title', 'content', 'meta_description', 'status',
    'created_at', 'updated_at'
]);

migrateTable($pdo, 'posts', 'storage/posts.json', [
    'id', 'slug', 'title', 'content', 'excerpt', 'meta_description',
    'featured_image', 'category', 'status', 'created_at', 'updated_at'
]);

// Migrate options
$options = load_json('storage/options.json');
if (!empty($options)) {
    $count = 0;
    foreach ($options as $key => $value) {
        try {
            $stmt = $pdo->prepare("INSERT INTO options (option_key, option_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT (option_key) DO UPDATE SET option_value = EXCLUDED.option_value, updated_at = CURRENT_TIMESTAMP");
            $stmt->execute([$key, $value]);
            $count++;
        } catch (PDOException $e) {
            // Silent error
        }
    }
    echo "options: {$count} records\n";
}

echo "\n=== Migration Complete ===\n";
echo "PostgreSQL is now active!\n\n";
