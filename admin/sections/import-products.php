<?php
/**
 * WordPress/WooCommerce Products Import Tool
 * Импорт на продукти от WordPress в offmetabg.com
 */

define('IMPORT_ERRORS_MESSAGE', ' продукта с грешки.');

$importMessage = '';
$importSuccess = false;
$importedCount = 0;
$importedProducts = [];
$importErrors = [];

if (isset($_POST['import_products'])) {
    // Increase execution time for large imports
    set_time_limit(300); // 5 minutes
    ini_set('memory_limit', '256M');
    
    $importType = $_POST['import_type'] ?? 'csv';
    
    if ($importType === 'csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $products = import_products_from_csv($file['tmp_name']);
            $importedCount = count($products);
            $importedProducts = $products;
            $importSuccess = true;
            $importMessage = "✅ Успешно импортирани {$importedCount} продукта!";
            
            // Check for errors during save
            if (!empty($importErrors)) {
                $importMessage .= "<br>⚠️ ". count($importErrors) . "продукта с грешки.";
            }
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
            
            // Check for errors during save
            if (!empty($importErrors)) {
                $importMessage .= "<br>⚠️ ". count($importErrors) . IMPORT_ERRORS_MESSAGE;
            }
        } else {
            $importMessage = "❌ Грешка при качване на файла.";
        }
    } elseif ($importType === 'manual' && !empty($_POST['products_json'])) {
        $products = import_products_from_json($_POST['products_json']);
        $importedCount = count($products);
        $importedProducts = $products;
        $importSuccess = true;
        $importMessage = "✅ Успешно импортирани {$importedCount} продукта!";
        
        // Check for errors during save
        if (!empty($importErrors)) {
            $importMessage .= "<br>⚠️ ". count($importErrors) . IMPORT_ERRORS_MESSAGE;
        }
    }
}

/**
 * Import products from CSV file (WooCommerce format)
 */
function import_products_from_csv($filePath) {
    $products = [];
    $handle = fopen($filePath, 'r');
    
    // Try to detect and set correct encoding
    $firstLine = fgets($handle);
    rewind($handle);
    
    // Check if file is UTF-8 with BOM
    if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
        // Skip BOM
        fseek($handle, 3);
    }
    
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        return [];
    }
    
    $rowCount = 0;
    $skippedCount = 0;
    $processedCount = 0;
    
    // Enhanced column mapping - supports multiple formats including Bulgarian
    $columnMap = [
        // ID variants
        'ID' => 'id',
        'id' => 'id',
        'product_id' => 'id',
        'Post ID' => 'id',
        
        // Type variants (for filtering)
        'Вид' => 'type',
        'Type' => 'type',
        'type' => 'type',
        
        // Name variants (English + Bulgarian)
        'Name' => 'name',
        'name' => 'name',
        'Име' => 'name',
        'post_title' => 'name',
        'Title' => 'name',
        'title' => 'name',
        'Product name' => 'name',
        'product_name' => 'name',
        
        // Description variants (English + Bulgarian)
        'Description' => 'description',
        'description' => 'description',
        'Описание' => 'description',
        'post_content' => 'description',
        'Content' => 'description',
        'Short description' => 'description',
        'short_description' => 'description',
        'Кратко описание' => 'description',
        
        // Price variants (English + Bulgarian)
        'Regular price' => 'price',
        'regular_price' => 'price',
        'Price' => 'price',
        'price' => 'price',
        'Sale price' => 'price',
        'sale_price' => 'price',
        'Редовна цена:' => 'price',
        'Промоционална цена:' => 'price',
        'Цена' => 'price',
        
        // Category variants (English + Bulgarian)
        'Categories' => 'category',
        'categories' => 'category',
        'Категории' => 'category',
        'product_cat' => 'category',
        'Category' => 'category',
        'category' => 'category',
        
        // Stock variants (English + Bulgarian)
        'Stock' => 'stock',
        'stock' => 'stock',
        'stock_quantity' => 'stock',
        'Stock quantity' => 'stock',
        'Quantity' => 'stock',
        'quantity' => 'stock',
        'Наличност' => 'stock',
        'В наличност?' => 'status_stock',
        
        // Image variants (English + Bulgarian)
        'Images' => 'image',
        'images' => 'image',
        'image' => 'image',
        'Image' => 'image',
        'Featured image' => 'image',
        'featured_image' => 'image',
        'thumbnail' => 'image',
        'Изображения' => 'image',
        
        // Status variants (English + Bulgarian)
        'Status' => 'status',
        'status' => 'status',
        'post_status' => 'status',
        'Published' => 'status',
        'Публикувано' => 'status'
    ];
    
    while (($row = fgetcsv($handle)) !== false) {
        $rowCount++;
        $product = [];
        foreach ($headers as $index => $header) {
            $header = trim($header);
            $mappedKey = $columnMap[$header] ?? strtolower(str_replace([' ', '-'], '_', $header));
            $value = isset($row[$index]) ? trim($row[$index]) : '';
            $product[$mappedKey] = $value;
        }
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            $skippedCount++;
            continue;
        }
        
        // Skip product variations (only import parent products)
        if (isset($product['type'])) {
            $productType = strtolower($product['type']);
            if ($productType === 'variation') {
                $skippedCount++;
                continue; // Skip variations, we only want main products
            }
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
            // Skip products without names
            $skippedCount++;
            continue;
        }
        
        $processedCount++;
        
        // Description - clean HTML and get first part
        $productDescription = trim($product['description'] ?? '');
        if (!empty($productDescription)) {
            $productDescription = strip_tags($productDescription);
            // Limit to 500 characters
            if (strlen($productDescription) > 500) {
                $productDescription = substr($productDescription, 0, 500) . '...';
            }
        }
        
        // Price - handle both promotional and regular price
        $productPrice = 0;
        if (!empty($product['price'])) {
            $priceStr = $product['price'];
            // Remove currency symbols and convert to float
            $priceStr = preg_replace('/[^\d.,]/', '', $priceStr);
            $priceStr = str_replace(',', '.', $priceStr);
            $productPrice = floatval($priceStr);
        }
        
        // Category - take first category if multiple
        $productCategory = trim($product['category'] ?? 'general');
        if (!empty($productCategory)) {
            // If multiple categories separated by comma, take first one
            if (strpos($productCategory, ',') !== false) {
                $categories = explode(',', $productCategory);
                $productCategory = trim($categories[0]);
            }
            // Convert to slug
            $productCategory = strtolower($productCategory);
            $productCategory = preg_replace('/[^\p{L}\p{N}]+/u', '-', $productCategory);
            $productCategory = trim($productCategory, '-');
        }
        if (empty($productCategory)) {
            $productCategory = 'general';
        }
        
        // Stock - parse integer
        $productStock = 0;
        if (isset($product['stock']) && $product['stock'] !== '') {
            $productStock = intval($product['stock']);
        }
        
        // Status - handle Bulgarian "Публикувано"status
        $productStatus = 'published';
        if (isset($product['status'])) {
            $statusValue = strtolower(trim($product['status']));
            // Check for published indicators: 1, true, publish, published, публикувано
            if (in_array($statusValue, ['0', 'false', 'draft', 'чернова', ''])) {
                $productStatus = 'draft';
            }
        }
        // Also check status_stock field (В наличност?)
        if (isset($product['status_stock'])) {
            $stockStatus = strtolower(trim($product['status_stock']));
            if ($stockStatus === '0' || $stockStatus === 'no' || $stockStatus === 'не') {
                $productStock = 0;
            }
        }
        
        // Images - take first image if multiple
        $productImage = trim($product['image'] ?? '');
        if (!empty($productImage)) {
            // If multiple images separated by comma, take first one
            if (strpos($productImage, ',') !== false) {
                $images = explode(',', $productImage);
                $productImage = trim($images[0]);
            }
        }
        
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
    
    // Log import statistics
    error_log("CSV Import: Total rows: $rowCount, Skipped: $skippedCount, Processed: $processedCount, Products created: ". count($products));
    
    // Save products
    $saveResult = save_imported_products($products);
    error_log("CSV Save: Saved: {$saveResult['saved']}, Failed: {$saveResult['failed']}");
    
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
    $saved = 0;
    $failed = 0;
    $errors = [];
    
    foreach ($newProducts as $product) {
        try {
            // Use the standard save_product_data function which handles both JSON and DB
            save_product_data($product);
            $saved++;
        } catch (Exception $e) {
            $failed++;
            $errors[] = "Product '{$product['name']}': ". $e->getMessage();
            // Log error but continue processing
            error_log("Import error for product '{$product['name']}': ". $e->getMessage());
        }
    }
    
    return [
        'saved' => $saved,
        'failed' => $failed,
        'errors' => $errors
    ];
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
                <a href="?section=products"class="btn btn-sm ml-auto">Виж продуктите →</a>
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
                                    <td><?php echo $currency_symbol; ?><?php echo number_format($prod['price'], 2); ?></td>
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

        <form method="POST"enctype="multipart/form-data">
            <div class="form-group">
                <label>Формат на файла</label>
                <select name="import_type"id="import_type"onchange="toggleImportMethod()">
                    <option value="csv">CSV (WooCommerce Export)</option>
                    <option value="json">JSON (WooCommerce REST API)</option>
                    <option value="manual">JSON (Ръчно вмъкване)</option>
                </select>
            </div>

            <div id="csv_upload"class="form-group">
                <label>CSV файл</label>
                <input type="file"name="csv_file"accept=".csv">
                <small class="hint">
                    💡 Експортирай продуктите от WordPress/WooCommerce като CSV файл.<br>
                    Поддържа стандартния WooCommerce CSV Export формат.
                </small>
            </div>

            <div id="json_upload"class="form-group"style="display:none;">
                <label>JSON файл</label>
                <input type="file"name="json_file"accept=".json">
                <small class="hint">
                    💡 Използвай WooCommerce REST API за да експортираш продукти във JSON формат.<br>
                    Или експортирай от друг плъгин за JSON export.
                </small>
            </div>

            <div id="manual_json"class="form-group"style="display:none;">
                <label>JSON данни</label>
                <textarea name="products_json"rows="10"placeholder='[{"name":"Product 1","price":10,"description":"..."}]'></textarea>
                <small class="hint">
                    💡 Постави JSON данни директно - или от WooCommerce REST API, или в нашия формат.
                </small>
            </div>

            <button type="submit"name="import_products"class="btn btn-primary">
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
            <li>Инсталирай плъгин "Export Products to JSON"или използвай REST API</li>
            <li>Експортирай продуктите като JSON</li>
            <li>Качи JSON файла тук</li>
        </ol>

        <h4>Метод 3: WordPress Export Tool</h4>
        <ol>
            <li>Влез в WordPress админ панела</li>
            <li>Отиди на <code>Tools → Export</code></li>
            <li>Избери "Products"и изтегли XML файла</li>
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
    background: var(--success-bg);
    color: var(--success-text);
    border: 1px solid var(--success-border);
}
.alert-error {
    background: var(--danger-bg);
    color: var(--danger-text);
    border: 1px solid var(--danger-border);
}
pre {
    background: rgba(27, 20, 48, 0.6);
    border: 1px solid rgba(159, 122, 234, 0.2);
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    color: var(--text-primary);
}
code {
    background: rgba(27, 20, 48, 0.5);
    border: 1px solid rgba(159, 122, 234, 0.15);
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    color: var(--text-primary);
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
