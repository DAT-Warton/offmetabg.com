<?php
define('CMS_ROOT', __DIR__);
require_once CMS_ROOT . '/includes/database.php';

echo "Checking product descriptions...\n\n";

$db = Database::getInstance();
$pdo = $db->getPDO();

// Check total products
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM products");
$total = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "Total products: $total\n";

// Check total descriptions
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM product_descriptions");
$desc_count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "Total product_descriptions: $desc_count\n";

// Check descriptions with actual text
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM product_descriptions WHERE description IS NOT NULL AND description != ''");
$with_desc = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "Products with non-empty description: $with_desc\n\n";

// Get one product with full data
echo "=== Sample Product ===\n";
$stmt = $pdo->query("
    SELECT p.id, p.slug, pd.name, pd.short_description, pd.description,
           LENGTH(pd.description) as desc_length
    FROM products p
    LEFT JOIN product_descriptions pd ON p.id = pd.product_id
    LIMIT 1
");
$sample = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($sample);

// Now let's check the JSON
echo "\n=== JSON Check ===\n";
$json_path = __DIR__ . '/storage/products.json';
if (file_exists($json_path)) {
    echo "JSON file exists\n";
    $json_data = file_get_contents($json_path);
    $products = json_decode($json_data, true);
    
    if ($products && is_array($products) && count($products) > 0) {
        echo "JSON has " . count($products) . " products\n";
        echo "First product keys: " . implode(', ', array_keys($products[0])) . "\n";
        
        if (isset($products[0]['Описание'])) {
            echo "\nFirst product 'Описание':\n";
            echo substr($products[0]['Описание'], 0, 200) . "...\n";
        }
    }
} else {
    echo "JSON file not found\n";
}
