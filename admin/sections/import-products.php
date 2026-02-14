<?php
/**
 * WordPress/WooCommerce Products Import Tool
 * Импорт на продукти от WordPress в offmetabg.com
 */

$importMessage = '';
$importSuccess = false;
$importedCount = 0;

if (isset($_POST['import_products'])) {
    $importType = $_POST['import_type'] ?? 'csv';
    
    if ($importType === 'csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $products = import_products_from_csv($file['tmp_name']);
            $importedCount = count($products);
            $importSuccess = true;
            $importMessage = "✅ Успешно импортирани {$importedCount} продукта!";
        }
    } elseif ($importType === 'json' && isset($_FILES['json_file'])) {
        $file = $_FILES['json_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $jsonData = file_get_contents($file['tmp_name']);
            $products = import_products_from_json($jsonData);
            $importedCount = count($products);
            $importSuccess = true;
            $importMessage = "✅ Успешно импортирани {$importedCount} продукта!";
        }
    } elseif ($importType === 'manual' && !empty($_POST['products_json'])) {
        $products = import_products_from_json($_POST['products_json']);
        $importedCount = count($products);
        $importSuccess = true;
        $importMessage = "✅ Успешно импортирани {$importedCount} продукта!";
    }
}

/**
 * Import products from CSV file (WooCommerce format)
 */
function import_products_from_csv($filePath) {
    $products = [];
    $handle = fopen($filePath, 'r');
    $headers = fgetcsv($handle);
    
    // Map WordPress/WooCommerce column names
    $columnMap = [
        'ID' => 'id',
        'Name' => 'name',
        'post_title' => 'name',
        'Description' => 'description',
        'post_content' => 'description',
        'Regular price' => 'price',
        'regular_price' => 'price',
        'Price' => 'price',
        'Categories' => 'category',
        'product_cat' => 'category',
        'Stock' => 'stock',
        'stock_quantity' => 'stock',
        'Images' => 'image',
        'image' => 'image',
        'Status' => 'status',
        'post_status' => 'status'
    ];
    
    while (($row = fgetcsv($handle)) !== false) {
        $product = [];
        foreach ($headers as $index => $header) {
            $mappedKey = $columnMap[$header] ?? strtolower(str_replace(' ', '_', $header));
            $product[$mappedKey] = $row[$index] ?? '';
        }
        
        // Generate ID if not present
        if (empty($product['id'])) {
            $product['id'] = 'prod_' . uniqid();
        } else {
            $product['id'] = 'prod_wp_' . $product['id'];
        }
        
        // Clean and format data
        $product['name'] = $product['name'] ?? 'Untitled Product';
        $product['description'] = $product['description'] ?? '';
        $product['price'] = floatval($product['price'] ?? 0);
        $product['category'] = $product['category'] ?? 'general';
        $product['stock'] = intval($product['stock'] ?? 0);
        $product['status'] = ($product['status'] ?? 'publish') === 'publish' ? 'published' : 'draft';
        $product['image'] = $product['image'] ?? '';
        $product['videos'] = [
            'youtube' => '',
            'tiktok' => '',
            'instagram' => ''
        ];
        $product['created'] = date('Y-m-d H:i:s');
        $product['updated'] = date('Y-m-d H:i:s');
        
        $products[$product['id']] = $product;
    }
    
    fclose($handle);
    
    // Save products
    save_imported_products($products);
    
    return $products;
}

/**
 * Import products from JSON (WooCommerce REST API format or custom)
 */
function import_products_from_json($jsonData) {
    $data = json_decode($jsonData, true);
    if (!$data) {
        return [];
    }
    
    $products = [];
    
    // Check if it's WooCommerce REST API format
    if (isset($data['products']) && is_array($data['products'])) {
        $data = $data['products'];
    } elseif (!isset($data[0]) && count($data) > 0) {
        // Already in our format
        save_imported_products($data);
        return $data;
    }
    
    // Convert WooCommerce format to our format
    foreach ($data as $wpProduct) {
        $productId = 'prod_wp_' . ($wpProduct['id'] ?? uniqid());
        
        // Extract image URL
        $image = '';
        if (!empty($wpProduct['images'][0]['src'])) {
            $image = $wpProduct['images'][0]['src'];
        } elseif (!empty($wpProduct['image']['src'])) {
            $image = $wpProduct['image']['src'];
        }
        
        // Extract category
        $category = 'general';
        if (!empty($wpProduct['categories'][0]['name'])) {
            $category = $wpProduct['categories'][0]['name'];
        } elseif (!empty($wpProduct['category'])) {
            $category = $wpProduct['category'];
        }
        
        $product = [
            'id' => $productId,
            'name' => $wpProduct['name'] ?? $wpProduct['title'] ?? 'Untitled Product',
            'description' => strip_tags($wpProduct['description'] ?? $wpProduct['content'] ?? ''),
            'price' => floatval($wpProduct['price'] ?? $wpProduct['regular_price'] ?? 0),
            'image' => $image,
            'category' => $category,
            'stock' => intval($wpProduct['stock_quantity'] ?? $wpProduct['stock'] ?? 0),
            'status' => ($wpProduct['status'] ?? 'publish') === 'publish' ? 'published' : 'draft',
            'videos' => [
                'youtube' => '',
                'tiktok' => '',
                'instagram' => ''
            ],
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
        
        $products[$productId] = $product;
    }
    
    // Save products
    save_imported_products($products);
    
    return $products;
}

/**
 * Save imported products to storage (JSON or Database)
 */
function save_imported_products($newProducts) {
    foreach ($newProducts as $product) {
        // Use the standard save_product_data function which handles both JSON and DB
        save_product_data($product);
    }
}
?>

<div>
    <div class="section-header">
        <h2 class="section-title">📥 Импорт на продукти</h2>
    </div>

    <?php if ($importMessage): ?>
        <div class="alert alert-<?php echo $importSuccess ? 'success' : 'error'; ?>">
            <?php echo $importMessage; ?>
            <?php if ($importSuccess): ?>
                <a href="?section=products" class="btn btn-sm ml-auto">Виж продуктите →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card card-lg">
        <h3>🛒 Импорт от WordPress/WooCommerce</h3>
        <p class="text-muted">Импортирай продукти от твоя WordPress сайт във формат CSV или JSON.</p>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Формат на файла</label>
                <select name="import_type" id="import_type" onchange="toggleImportMethod()">
                    <option value="csv">CSV (WooCommerce Export)</option>
                    <option value="json">JSON (WooCommerce REST API)</option>
                    <option value="manual">JSON (Ръчно вмъкване)</option>
                </select>
            </div>

            <div id="csv_upload" class="form-group">
                <label>CSV файл</label>
                <input type="file" name="csv_file" accept=".csv">
                <small class="hint">
                    💡 Експортирай продуктите от WordPress/WooCommerce като CSV файл.<br>
                    Поддържа стандартния WooCommerce CSV Export формат.
                </small>
            </div>

            <div id="json_upload" class="form-group" style="display:none;">
                <label>JSON файл</label>
                <input type="file" name="json_file" accept=".json">
                <small class="hint">
                    💡 Използвай WooCommerce REST API за да експортираш продукти във JSON формат.<br>
                    Или експортирай от друг плъгин за JSON export.
                </small>
            </div>

            <div id="manual_json" class="form-group" style="display:none;">
                <label>JSON данни</label>
                <textarea name="products_json" rows="10" placeholder='[{"name":"Product 1","price":10,"description":"..."}]'></textarea>
                <small class="hint">
                    💡 Постави JSON данни директно - или от WooCommerce REST API, или в нашия формат.
                </small>
            </div>

            <button type="submit" name="import_products" class="btn btn-primary">
                📥 Импортирай продукти
            </button>
        </form>
    </div>

    <div class="card card-lg mt-20">
        <h3>📋 Инструкции за експорт от WordPress</h3>
        
        <h4>Метод 1: CSV Export (WooCommerce)</h4>
        <ol>
            <li>Влез в WordPress админ панела</li>
            <li>Отиди на <code>WooCommerce → Products</code></li>
            <li>Кликни <strong>Export</strong> в горното меню</li>
            <li>Избери всички продукти и формат CSV</li>
            <li>Изтегли CSV файла</li>
            <li>Качи го тук</li>
        </ol>

        <h4>Метод 2: JSON Export (WooCommerce REST API)</h4>
        <ol>
            <li>Инсталирай плъгин "Export Products to JSON" или използвай REST API</li>
            <li>Експортирай продуктите като JSON</li>
            <li>Качи JSON файла тук</li>
        </ol>

        <h4>Метод 3: WordPress Export Tool</h4>
        <ol>
            <li>Влез в WordPress админ панела</li>
            <li>Отиди на <code>Tools → Export</code></li>
            <li>Избери "Products" и изтегли XML файла</li>
            <li>Конвертирай XML към JSON с онлайн инструмент</li>
            <li>Качи JSON файла тук</li>
        </ol>
    </div>

    <div class="card card-lg mt-20">
        <h3>🔧 Примерен формат</h3>
        <p>Нашият формат на продукти:</p>
        <pre><code>{
  "prod_123": {
    "id": "prod_123",
    "name": "Продукт 1",
    "description": "Описание на продукта",
    "price": 29.99,
    "image": "/uploads/product.jpg",
    "category": "general",
    "stock": 10,
    "status": "published",
    "videos": {
      "youtube": "",
      "tiktok": "",
      "instagram": ""
    },
    "created": "2026-02-14 10:00:00",
    "updated": "2026-02-14 10:00:00"
  }
}</code></pre>
    </div>
</div>

<script>
function toggleImportMethod() {
    const type = document.getElementById('import_type').value;
    document.getElementById('csv_upload').style.display = type === 'csv' ? 'block' : 'none';
    document.getElementById('json_upload').style.display = type === 'json' ? 'block' : 'none';
    document.getElementById('manual_json').style.display = type === 'manual' ? 'block' : 'none';
}
</script>

<style>
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
pre {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}
code {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
ol {
    margin-left: 20px;
}
ol li {
    margin: 5px 0;
}
h4 {
    margin-top: 20px;
    color: var(--primary);
}
</style>
