<?php
/**
 * Promotions Management Section
 * Supports visual (banners/popups) and sales promotions (bundles/discounts)
 */

$promotions = load_json('storage/promotions.json');
$products = load_json('storage/products.json');
$categories = load_json('storage/categories.json');
$editPromotion = null;

// Handle edit mode
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = $_GET['id'];
    foreach ($promotions as $promo) {
        if ($promo['id'] === $editId) {
            $editPromotion = $promo;
            break;
        }
    }
}
?>

<div class="section-header">
    <h2><?php echo icon_megaphone(24); ?> <?php echo __('admin.promotions'); ?></h2>
    <?php if ($action !== 'new' && $action !== 'edit'): ?>
        <a href="?section=promotions&action=new" class="btn"><?php echo icon_megaphone(18); ?> <?php echo __('admin.add_promotion'); ?></a>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <!-- Create/Edit Promotion Form -->
    <div class="card">
        <h3><?php echo $action === 'edit' ? __('admin.edit_promotion') : __('admin.add_promotion'); ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="save_promotion">
            <input type="hidden" name="promotion_id" value="<?php echo htmlspecialchars($editPromotion['id'] ?? ''); ?>">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label><?php echo __('admin.promotion_title'); ?></label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($editPromotion['title'] ?? ''); ?>" required placeholder="Лятна разпродажба 2026">
                </div>

                <div class="form-group">
                    <label><?php echo __('admin.promotion_type'); ?></label>
                    <select name="type" id="promotionType" required onchange="updatePromotionFields()">
                        <optgroup label="Визуални промоции">
                            <option value="banner" <?php echo ($editPromotion['type'] ?? '') === 'banner' ? 'selected' : ''; ?>>🖼️ Банер</option>
                            <option value="popup" <?php echo ($editPromotion['type'] ?? '') === 'popup' ? 'selected' : ''; ?>>💬 Popup</option>
                            <option value="notification" <?php echo ($editPromotion['type'] ?? '') === 'notification' ? 'selected' : ''; ?>>🔔 Известие</option>
                            <option value="homepage" <?php echo ($editPromotion['type'] ?? '') === 'homepage' ? 'selected' : ''; ?>>🏠 Начална страница</option>
                        </optgroup>
                        <optgroup label="💰 Продуктови промоции">
                            <option value="bundle" <?php echo ($editPromotion['type'] ?? '') === 'bundle' ? 'selected' : ''; ?>>📦 Бъндъл</option>
                            <option value="buy_x_get_y" <?php echo ($editPromotion['type'] ?? '') === 'buy_x_get_y' ? 'selected' : ''; ?>>🎁 Купи X, вземи Y</option>
                            <option value="product_discount" <?php echo ($editPromotion['type'] ?? '') === 'product_discount' ? 'selected' : ''; ?>>🏷️ Отстъпка на продукти</option>
                            <option value="category_discount" <?php echo ($editPromotion['type'] ?? '') === 'category_discount' ? 'selected' : ''; ?>>📂 Отстъпка на категория</option>
                            <option value="cart_discount" <?php echo ($editPromotion['type'] ?? '') === 'cart_discount' ? 'selected' : ''; ?>>🛒 Отстъпка при покупка</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.promotion_description'); ?></label>
                <textarea name="description" rows="2" placeholder="До 50% отстъпка на всички продукти"><?php echo htmlspecialchars($editPromotion['description'] ?? ''); ?></textarea>
            </div>

            <!-- Visual Promotion Fields (for banner/popup/notification/homepage) -->
            <div id="visualFields" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label><?php echo __('admin.promotion_image'); ?></label>
                        <input type="text" name="image" value="<?php echo htmlspecialchars($editPromotion['image'] ?? ''); ?>" placeholder="/uploads/promo-banner.jpg">
                        <small style="color: var(--text-secondary, #666);">URL на изображение (банер/снимка)</small>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('admin.promotion_link'); ?></label>
                        <input type="text" name="link" value="<?php echo htmlspecialchars($editPromotion['link'] ?? ''); ?>" placeholder="/products?category=sale">
                        <small style="color: var(--text-secondary, #666);">Линк към страница (оставете празно за без линк)</small>
                    </div>
                </div>
            </div>

            <!-- Sales Promotion Fields (for bundle/discount/buy_x_get_y) -->
            <div id="salesFields" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>💶 Тип отстъпка</label>
                        <select name="discount_type">
                            <option value="percentage" <?php echo ($editPromotion['discount_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>📊 Процент (%)</option>
                            <option value="fixed" <?php echo ($editPromotion['discount_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>💵 Фиксирана сума (€)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label id="discountValueLabel">💰 Стойност</label>
                        <input type="number" name="discount_value" step="0.01" min="0" value="<?php echo htmlspecialchars($editPromotion['discount_value'] ?? ''); ?>" placeholder="10">
                    </div>

                    <div class="form-group">
                        <label>🛒 Минимална покупка (€)</label>
                        <input type="number" name="min_purchase" step="0.01" min="0" value="<?php echo htmlspecialchars($editPromotion['min_purchase'] ?? '0'); ?>" placeholder="0">
                        <small style="color: var(--text-secondary, #666);">0 = без минимум</small>
                    </div>
                </div>

                <!-- Product Selection (for bundle/product_discount/buy_x_get_y) -->
                <div id="productFields" style="display: none;">
                    <div class="form-group">
                        <label>🏷️ Изберете продукти</label>
                        <select name="product_ids[]" multiple size="8" style="width: 100%; padding: 8px; border: 1px solid var(--border-color, #e0e0e0); border-radius: 6px;">
                            <?php if (empty($products)): ?>
                                <option disabled>Няма налични продукти</option>
                            <?php else: ?>
                                <?php 
                                $selectedProducts = $editPromotion['product_ids'] ?? [];
                                foreach ($products as $product): 
                                    $selected = in_array($product['id'], $selectedProducts) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?> - €<?php echo number_format($product['price'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small style="color: var(--text-secondary, #666);">Ctrl+Click за избор на много продукти</small>
                    </div>
                </div>

                <!-- Category Selection (for category_discount) -->
                <div id="categoryFields" style="display: none;">
                    <div class="form-group">
                        <label>📂 Изберете категория</label>
                        <select name="category_id" style="width: 100%; padding: 10px; border: 1px solid var(--border-color, #e0e0e0); border-radius: 6px;">
                            <option value="">Всички категории</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo ($editPromotion['category_id'] ?? '') === $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Buy X Get Y Fields -->
                <div id="buyXGetYFields" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>🛍️ Купи X броя</label>
                            <input type="number" name="buy_quantity" min="1" value="<?php echo htmlspecialchars($editPromotion['buy_quantity'] ?? '2'); ?>" placeholder="2">
                        </div>

                        <div class="form-group">
                            <label>🎁 Вземи Y броя</label>
                            <input type="number" name="get_quantity" min="1" value="<?php echo htmlspecialchars($editPromotion['get_quantity'] ?? '1'); ?>" placeholder="1">
                            <small style="color: var(--text-secondary, #666);">Купи 2, вземи 1 безплатно</small>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 100px; gap: 20px; align-items: start;">
                <div class="form-group">
                    <label><?php echo __('admin.start_date'); ?></label>
                    <input type="datetime-local" name="start_date" value="<?php echo htmlspecialchars($editPromotion['start_date'] ?? ''); ?>">
                    <small style="color: var(--text-secondary, #666);">Начало на промоцията</small>
                </div>

                <div class="form-group">
                    <label><?php echo __('admin.end_date'); ?></label>
                    <input type="datetime-local" name="end_date" value="<?php echo htmlspecialchars($editPromotion['end_date'] ?? ''); ?>">
                    <small style="color: var(--text-secondary, #666);">Край на промоцията</small>
                </div>

                <div class="form-group">
                    <label><?php echo __('admin.display_order'); ?></label>
                    <input type="number" name="order" value="<?php echo htmlspecialchars($editPromotion['order'] ?? '0'); ?>" min="0" style="text-align: center;">
                    <small style="color: var(--text-secondary, #666);">Ред</small>
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?php echo ($editPromotion['active'] ?? true) ? 'checked' : ''; ?>>
                    <span><?php echo __('admin.promotion_active'); ?></span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn"><?php echo icon_check(18); ?> Запази промоция</button>
                <a href="?section=promotions" class="btn-secondary"><?php echo icon_x(18); ?> Отказ</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Promotions List -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><?php echo __('admin.promotion_title'); ?></th>
                    <th><?php echo __('admin.promotion_type'); ?></th>
                    <th><?php echo __('admin.period'); ?></th>
                    <th><?php echo __('admin.status'); ?></th>
                    <th><?php echo __('admin.actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($promotions)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary, #666);">
                            <?php echo icon_megaphone(32); ?><br>
                            <?php echo __('admin.no_promotions'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($promotions as $promotion): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($promotion['title']); ?></strong>
                                <?php if (!empty($promotion['description'])): ?>
                                    <br><small style="color: var(--text-secondary, #666);"><?php echo htmlspecialchars(substr($promotion['description'], 0, 60)); ?><?php echo strlen($promotion['description']) > 60 ? '...' : ''; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $typeIcons = [
                                    // Visual promotions
                                    'banner' => ['icon' => '🖼️', 'label' => 'Банер', 'color' => '#667eea'],
                                    'popup' => ['icon' => '💬', 'label' => 'Popup', 'color' => '#f97316'],
                                    'notification' => ['icon' => '🔔', 'label' => 'Известие', 'color' => '#06b6d4'],
                                    'homepage' => ['icon' => '🏠', 'label' => 'Начална', 'color' => '#10b981'],
                                    // Sales promotions
                                    'bundle' => ['icon' => '📦', 'label' => 'Бъндъл', 'color' => '#8b5cf6'],
                                    'buy_x_get_y' => ['icon' => '🎁', 'label' => 'Купи X Вземи Y', 'color' => '#ec4899'],
                                    'product_discount' => ['icon' => '🏷️', 'label' => 'Продуктова', 'color' => '#f59e0b'],
                                    'category_discount' => ['icon' => '📂', 'label' => 'Категория', 'color' => '#14b8a6'],
                                    'cart_discount' => ['icon' => '🛒', 'label' => 'Кошница', 'color' => '#06b6d4']
                                ];
                                $type = $typeIcons[$promotion['type']] ?? ['icon' => '📌', 'label' => $promotion['type'], 'color' => '#8b5cf6'];
                                ?>
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: <?php echo $type['color']; ?>15; color: <?php echo $type['color']; ?>; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid <?php echo $type['color']; ?>30;">
                                    <span style="font-size: 16px;"><?php echo $type['icon']; ?></span>
                                    <?php echo $type['label']; ?>
                                </span>
                            </td>
                            <td style="font-size: 13px; color: var(--text-secondary, #666);">
                                <?php if (!empty($promotion['start_date']) && !empty($promotion['end_date'])): ?>
                                    <?php echo date('d.m.Y', strtotime($promotion['start_date'])); ?> - <?php echo date('d.m.Y', strtotime($promotion['end_date'])); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary, #666);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($promotion['active'] ?? true): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #10b98115; color: #10b981; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid #10b98130;">
                                        <?php echo icon_check_circle(14); ?> Активна
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #6b728015; color: #6b7280; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid #6b728030;">
                                        <?php echo icon_x_circle(14); ?> Неактивна
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="display: flex; gap: 8px;">
                                    <a href="?section=promotions&action=edit&id=<?php echo urlencode($promotion['id']); ?>" class="btn-small" title="Редактирай">
                                        ✏️ Редактирай
                                    </a>
                                    <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('<?php echo __('admin.confirm_delete_promotion'); ?>');">
                                        <input type="hidden" name="action" value="delete_promotion">
                                        <input type="hidden" name="promotion_id" value="<?php echo htmlspecialchars($promotion['id']); ?>">
                                        <button type="submit" class="btn-delete" title="Изтрий">
                                            🗑️ Изтрий
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
function updatePromotionFields() {
    const type = document.getElementById('promotionType').value;
    const visualFields = document.getElementById('visualFields');
    const salesFields = document.getElementById('salesFields');
    const productFields = document.getElementById('productFields');
    const categoryFields = document.getElementById('categoryFields');
    const buyXGetYFields = document.getElementById('buyXGetYFields');
    
    // Hide all first
    if (visualFields) visualFields.style.display = 'none';
    if (salesFields) salesFields.style.display = 'none';
    if (productFields) productFields.style.display = 'none';
    if (categoryFields) categoryFields.style.display = 'none';
    if (buyXGetYFields) buyXGetYFields.style.display = 'none';
    
    // Visual promotions (banners, popups, notifications)
    const visualTypes = ['banner', 'popup', 'notification', 'homepage'];
    if (visualTypes.includes(type)) {
        if (visualFields) visualFields.style.display = 'block';
    }
    
    // Sales promotions (discounts, bundles, etc)
    const salesTypes = ['bundle', 'buy_x_get_y', 'product_discount', 'category_discount', 'cart_discount'];
    if (salesTypes.includes(type)) {
        if (salesFields) salesFields.style.display = 'block';
        
        // Show specific fields per type
        if (type === 'bundle' || type === 'product_discount') {
            if (productFields) productFields.style.display = 'block';
        }
        
        if (type === 'buy_x_get_y') {
            if (productFields) productFields.style.display = 'block';
            if (buyXGetYFields) buyXGetYFields.style.display = 'block';
        }
        
        if (type === 'category_discount') {
            if (categoryFields) categoryFields.style.display = 'block';
        }
        
        // cart_discount doesn't need product/category selection
    }
}

// Run on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('promotionType')) {
        updatePromotionFields();
    }
});
</script>
