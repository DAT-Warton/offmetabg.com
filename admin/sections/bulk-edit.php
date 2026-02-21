<?php
/**
 * Bulk Edit Products - Quick fix for prices, stock, and descriptions
 */

if (!isset($_SESSION['admin_user'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Get all products
$products = get_products_data();

// Filter for products with issues
$productsWithIssues = array_filter($products, function($p) {
    return $p['price'] <= 0 || 
           $p['stock'] <= 0 || 
           empty($p['short_description']) ||
           strpos($p['name'], '&amp;') !== false ||
           strpos($p['short_description'], 'data-path-to-node') !== false;
});

$totalProducts = count($products);
$issuesCount = count($productsWithIssues);
?>

<style>
.bulk-edit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.2);
    padding: 16px;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    display: block;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

.issue-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    transition: box-shadow 0.2s;
}

.issue-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.issue-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-right: 8px;
    margin-bottom: 8px;
}

.badge-price { background: #fee; color: #c00; }
.badge-stock { background: #ffc; color: #860; }
.badge-desc { background: #eef; color: #06c; }
.badge-html { background: #fcf; color: #808; }

.quick-fix-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 8px 0;
}

.btn-quick-fix {
    background: #2ecc71;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-quick-fix:hover {
    background: #27ae60;
}

.product-preview {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 12px;
}

.preview-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.preview-details {
    flex: 1;
}

.preview-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
}

.preview-info {
    color: #666;
    font-size: 14px;
}
</style>

<div class="bulk-edit-header">
    <h1>🛠️ Бърза корекция на продукти</h1>
    <p>Намерени проблеми в продуктите - коригирайте ги бързо</p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number"><?php echo $totalProducts; ?></span>
            <span class="stat-label">Общо продукти</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" style="color: #e74c3c;"><?php echo $issuesCount; ?></span>
            <span class="stat-label">С проблеми</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" style="color: #2ecc71;"><?php echo $totalProducts - $issuesCount; ?></span>
            <span class="stat-label">Без проблеми</span>
        </div>
    </div>
</div>

<?php if (empty($productsWithIssues)): ?>
    <div style="text-align: center; padding: 48px; background: #f0f8ff; border-radius: 12px;">
        <h2 style="color: #2ecc71;">✅ Всички продукти са наред!</h2>
        <p style="color: #666;">Не са намерени проблеми с цени, наличности или описания.</p>
    </div>
<?php else: ?>

<div class="section-actions" style="margin-bottom: 24px;">
    <button onclick="fixAllHtmlEntities()" class="btn-quick-fix">
        🔧 Fix All HTML Entities
    </button>
    <button onclick="removeAllHtmlArtifacts()" class="btn-quick-fix" style="background: #3498db;">
        🧹 Clean All HTML Artifacts
    </button>
</div>

<?php foreach ($productsWithIssues as $product): ?>
    <div class="issue-card" id="product-<?php echo $product['id']; ?>">
        <div class="product-preview">
            <?php if (!empty($product['image'])): ?>
                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="Product" 
                     class="preview-image">
            <?php else: ?>
                <div class="preview-image" style="background: #eee; display: flex; align-items: center; justify-content: center;">
                    📦
                </div>
            <?php endif; ?>
            
            <div class="preview-details">
                <div class="preview-name"><?php echo htmlspecialchars($product['name']); ?></div>
                <div class="preview-info">
                    SKU: <?php echo $product['sku'] ?: 'N/A'; ?> | 
                    Category: <?php echo $product['category']; ?>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 12px;">
            <?php if ($product['price'] <= 0): ?>
                <span class="issue-badge badge-price">❌ Липсва цена</span>
            <?php endif; ?>
            
            <?php if ($product['stock'] <= 0): ?>
                <span class="issue-badge badge-stock">⚠️ Няма наличност</span>
            <?php endif; ?>
            
            <?php if (empty($product['short_description'])): ?>
                <span class="issue-badge badge-desc">📝 Няма кратко описание</span>
            <?php endif; ?>
            
            <?php if (strpos($product['name'], '&amp;') !== false): ?>
                <span class="issue-badge badge-html">🔧 HTML entities в името</span>
            <?php endif; ?>
            
            <?php if (strpos($product['short_description'], 'data-path-to-node') !== false): ?>
                <span class="issue-badge badge-html">🧹 HTML artifacts в описанието</span>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="dashboard.php?section=bulk-edit" class="quick-fix-form">
            <input type="hidden" name="action" value="bulk_fix_product">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="font-size: 13px; color: #666;">Цена (BGN)</label>
                    <input type="number" 
                           name="price" 
                           step="0.01" 
                           value="<?php echo $product['price']; ?>"
                           class="quick-fix-input"
                           placeholder="0.00">
                </div>
                
                <div>
                    <label style="font-size: 13px; color: #666;">Наличност (бр.)</label>
                    <input type="number" 
                           name="stock" 
                           value="<?php echo $product['stock']; ?>"
                           class="quick-fix-input"
                           placeholder="0">
                </div>
            </div>
            
            <div style="margin-top: 12px;">
                <label style="font-size: 13px; color: #666;">Кратко описание (1-2 изречения)</label>
                <textarea name="short_description" 
                          class="quick-fix-input" 
                          rows="2"
                          placeholder="Добавете кратко описание..."><?php echo htmlspecialchars($product['short_description']); ?></textarea>
            </div>
            
            <div style="margin-top: 12px; display: flex; gap: 8px;">
                <button type="submit" class="btn-quick-fix">
                    ✅ Запази промените
                </button>
                <a href="dashboard.php?section=products&edit=<?php echo $product['id']; ?>" 
                   style="padding: 8px 16px; text-decoration: none; color: #3498db; border: 1px solid #3498db; border-radius: 6px; font-weight: 600;">
                    ✏️ Пълно редактиране
                </a>
            </div>
        </form>
    </div>
<?php endforeach; ?>

<?php endif; ?>

<script>
function fixAllHtmlEntities() {
    if (!confirm('Това ще декодира всички HTML entities (&amp; → &) във всички продукти. Продължавате?')) {
        return;
    }
    
    // Submit AJAX request to fix all HTML entities
    fetch('dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=fix_all_html_entities'
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'HTML entities са коригирани!');
        location.reload();
    })
    .catch(error => {
        alert('Грешка: ' + error);
    });
}

function removeAllHtmlArtifacts() {
    if (!confirm('Това ще премахне всички HTML artifacts (data-path-to-node, etc.) от описанията. Продължавате?')) {
        return;
    }
    
    fetch('dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=remove_all_html_artifacts'
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'HTML artifacts са премахнати!');
        location.reload();
    })
    .catch(error => {
        alert('Грешка: ' + error);
    });
}
</script>
