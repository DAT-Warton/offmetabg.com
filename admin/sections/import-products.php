<?php
/**
 * WordPress/WooCommerce Products Import Tool
 * Импорт на продукти от WordPress в offmetabg.com
 */

$importMessage = '';
$importSuccess = false;
$importedCount = 0;
$importedProducts = [];

if (isset($_POST['import_products'])) {
    $importType = $_POST['import_type'] ?? 'csv';
    
    if ($importType === 'csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $products = import_products_from_csv($file['tmp_name']);
            $importedCount = count($products);
            $importedProducts = $products;
            $importSuccess = true;
            $importMessage = "✅ Успешно импортирани {$importedCount} продукта!";
        } else {
            $importMessage = "❌ Грешка при качване на файла.";
        }
    } elseif ($importType === 'json' && isset($_FILES['json_file'])) {
        $file = $_FILES['json_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $jsonData = file_get_contents($file['tmp_name']);
            $products = import_products_from_json($jsonData);
            $importedCount = count($products);
            $importedProducts = $products;
            $importSuccess = true;
            $importMessage = "✅ Успешно импортирани {$importedCount} продукта!";
        } else {
            $importMessage = "❌ Грешка при качване на файла.";
        }
    } elseif ($importType === 'manual' && !empty($_POST['products_json'])) {
        $products = import_products_from_json($_POST['products_json']);
        $importedCount = count($products);
        $importedProducts = $products;
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
    
    // Enhanced column mapping - supports multiple formats
    $columnMap = [
        // ID variants
        'ID' => 'id',
        'id' => 'id',
        'product_id' => 'id',
        'Post ID' => 'id',
        
        // Name variants
        'Name' => 'name',
        'name' => 'name',
        'post_title' => 'name',
        'Title' => 'name',
        'title' => 'name',
        'Product name' => 'name',
        'product_name' => 'name',
        
        // Description variants
        'Description' => 'description',
        'description' => 'description',
        'post_content' => 'description',
        'Content' => 'description',
        'Short description' => 'description',
        'short_description' => 'description',
        
        // Price variants
        'Regular price' => 'price',
        'regular_price' => 'price',
        'Price' => 'price',
        'price' => 'price',
        'Sale price' => 'price',
        'sale_price' => 'price',
        
        // Category variants
        'Categories' => 'category',
        'categories' => 'category',
        'product_cat' => 'category',
        'Category' => 'category',
        'category' => 'category',
        
        // Stock variants
        'Stock' => 'stock',
        'stock' => 'stock',
        'stock_quantity' => 'stock',
        'Stock quantity' => 'stock',
        'Quantity' => 'stock',
        'quantity' => 'stock',
        
        // Image variants
        'Images' => 'image',
        'images' => 'image',
        'image' => 'image',
        'Image' => 'image',
        'Featured image' => 'image',
        'featured_image' => 'image',
        'thumbnail' => 'image',
        
        // Status variants
        'Status' => 'status',
        'status' => 'status',
        'post_status' => 'status',
        'Published' => 'status'
    ];
    
    while (($row = fgetcsv($handle)) !== false) {
        $product = [];
        foreach ($headers as $index => $header) {
            $header = trim($header);
            $mappedKey = $columnMap[$header] ?? strtolower(str_replace([' ', '-'], '_', $header));
            $value = isset($row[$index]) ? trim($row[$index]) : '';
            $product[$mappedKey] = $value;
        }
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Generate ID if not present
        if (empty($product['id'])) {
            $product['id'] = 'prod_' . uniqid();
        } else {
            $product['id'] = 'prod_wp_' . $product['id'];
        }
        
        // Clean and format data with better validation
        $productName = trim($product['name'] ?? '');
        if (empty($productName)) {
            $productName = 'Untitled Product';
        }
        
        $productDescription = trim($product['description'] ?? '');
        $productPrice = $product['price'] ?? 0;
        // Remove currency symbols and convert to float
        $productPrice = preg_replace('/[^\d.,]/', '', $productPrice);
        $productPrice = str_replace(',', '.', $productPrice);
        $productPrice = floatval($productPrice);
        
        $productCategory = trim($product['category'] ?? 'general');
        if (empty($productCategory)) {
            $productCategory = 'general';
        }
        
        $productStock = intval($product['stock'] ?? 0);
        
        $productStatus = strtolower(trim($product['status'] ?? 'publish'));
        $productStatus = in_array($productStatus, ['publish', 'published', '1', 'true']) ? 'published' : 'draft';
        
        $productImage = trim($product['image'] ?? '');
        
        // Build final product
        $finalProduct = [
            'id' => $product['id'],
            'name' => $productName,
            'description' => $productDescription,
            'price' => $productPrice,
            'category' => $productCategory,
            'stock' => $productStock,
            'status' => $productStatus,
            'image' => $productImage,
            'videos' => [
                'youtube' => '',
                'tiktok' => '',
                'instagram' => ''
            ],
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
        
        $products[$finalProduct['id']] = $finalProduct;
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
        
        <?php if ($importSuccess && !empty($importedProducts)): ?>
            <div class="card card-lg mt-20">
                <h3>📋 Импортирани продукти</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Име</th>
                                <th>Цена</th>
                                <th>Категория</th>
                                <th>Наличност</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 0;
                            foreach ($importedProducts as $prod): 
                                if ($counter >= 10) break; // Show max 10
                                $counter++;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                    <td>$<?php echo number_format($prod['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($prod['category']); ?></td>
                                    <td><?php echo $prod['stock']; ?></td>
                                    <td>
                                        <?php if ($prod['status'] === 'published'): ?>
                                            <span class="badge badge-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($importedProducts) > 10): ?>
                        <p class="text-muted text-center">...и още <?php echo count($importedProducts) - 10; ?> продукта</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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
