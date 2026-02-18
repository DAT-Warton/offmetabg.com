<?php
/**
 * Professional Discount Management System
 * Advanced discount codes with rules, conditions, stacking, targeting, and analytics
 */

$discounts = get_discounts_data();
$products = get_products_data();
$categories = get_categories_data();
$editDiscount = null;

// Get currency settings
$currency_settings = get_currency_settings();
$currency_symbol = $currency_settings['symbol'];

// Handle edit mode
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = $_GET['id'];
    foreach ($discounts as $disc) {
        if ($disc['id'] === $editId) {
            $editDiscount = $disc;
            break;
        }
    }
}

// Prepare statistics
$total_discounts = count($discounts);
$active_discounts = count(array_filter($discounts, function($d) { return $d['active'] ?? false; }));
$total_uses = array_sum(array_column($discounts, 'used_count'));

// Calculate total savings from orders
$total_savings = 0;
try {
    $pdo = get_database();
    $stmt = $pdo->query("SELECT COALESCE(SUM(discount_amount), 0) as total FROM orders WHERE discount_amount > 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_savings = (float)($result['total'] ?? 0);
} catch (Exception $e) {
    error_log("Error calculating total savings: " . $e->getMessage());
}
?>

<div class="section-header">
    <h2><?php echo icon_percent(24); ?> Управление на отстъпки</h2>
    <div class="flex-gap-8">
        <?php if ($action !== 'new' && $action !== 'edit'): ?>
            <a href="?section=discounts&action=analytics"class="btn-secondary">
                📊 Аналитика
            </a>
            <a href="?section=discounts&action=new"class="btn">
                <?php echo icon_percent(18); ?> Нова отстъпка
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($message)): ?>
    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($action === 'analytics'): ?>
    <!-- Analytics View -->
    <div class="grid grid-4 mb-30">
        <div class="card text-center">
            <h4 class="text-muted mb-10">Общо отстъпки</h4>
            <p class="stat-number text-primary"><?php echo $total_discounts; ?></p>
        </div>
        <div class="card text-center">
            <h4 class="text-muted mb-10">Активни</h4>
            <p class="stat-number text-success"><?php echo $active_discounts; ?></p>
        </div>
        <div class="card text-center">
            <h4 class="text-muted mb-10">Използвания</h4>
            <p class="stat-number text-warning"><?php echo $total_uses; ?></p>
        </div>
        <div class="card text-center">
            <h4 class="text-muted mb-10">Спестени</h4>
            <p class="stat-number text-accent"><?php echo $currency_symbol; ?><?php echo number_format($total_savings, 2); ?></p>
        </div>
    </div>
    <a href="?section=discounts"class="btn-secondary mb-20">← Назад</a>
<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <!-- Create/Edit Discount Form -->
    <div class="card">
        <h3><?php echo $action === 'edit' ? 'Редактирай отстъпка' : 'Създай нова отстъпка'; ?></h3>
        
        <form method="POST"id="discountForm">
            <input type="hidden"name="action"value="save_discount">
            <input type="hidden"name="discount_id"value="<?php echo htmlspecialchars($editDiscount['id'] ?? ''); ?>">
            
            <!-- Basic Information -->
            <fieldset class="form-section">
                <legend>📋 Основна информация</legend>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="discount_code">Промо код *</label>
                        <input type="text"id="discount_code"name="code"
                               value="<?php echo htmlspecialchars($editDiscount['code'] ?? ''); ?>"
                               required
                               class="input-uppercase"
                               pattern="[A-Z0-9\-_]+"
                               placeholder="SUMMER2026">
                        <small class="hint">Само главни букви, цифри, тире и долна черта</small>
                    </div>

                    <div class="form-group">
                        <label for="discount_name">Име на отстъпката</label>
                        <input type="text"id="discount_name"name="name"
                               value="<?php echo htmlspecialchars($editDiscount['name'] ?? ''); ?>"
                               placeholder="Лятна разпродажба 2026">
                        <small class="hint">Вътрешно име за разпознаване</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="discount_description">Описание</label>
                    <textarea id="discount_description"name="description"rows="2"
                              placeholder="20% отстъпка за нови клиенти при първа поръчка"><?php echo htmlspecialchars($editDiscount['description'] ?? ''); ?></textarea>
                </div>
            </fieldset>

            <!-- Discount Value -->
            <fieldset class="form-section">
                <legend>💰 Стойност на отстъпката</legend>
                
                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="discount_type">Тип *</label>
                        <select name="type"id="discount_type"required onchange="updateDiscountValueHint()">
                            <option value="percentage"<?php echo ($editDiscount['type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>
                                📊 Процент (%)
                            </option>
                            <option value="fixed"<?php echo ($editDiscount['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>
                                💶 Фиксирана сума (<?php echo $currency_symbol; ?>)
                            </option>
                            <option value="free_shipping"<?php echo ($editDiscount['type'] ?? '') === 'free_shipping' ? 'selected' : ''; ?>>
                                🚚 Безплатна доставка
                            </option>
                            <option value="buy_x_get_y"<?php echo ($editDiscount['type'] ?? '') === 'buy_x_get_y' ? 'selected' : ''; ?>>
                                🎁 Купи X, вземи Y
                            </option>
                        </select>
                    </div>

                    <div class="form-group"id="value_field">
                        <label for="discount_value">Стойност *</label>
                        <input type="number"name="value"id="discount_value"
                               value="<?php echo htmlspecialchars($editDiscount['value'] ?? ''); ?>"
                               step="0.01"min="0"max="100"required>
                        <small id="value_hint"class="hint">Въведете процент (0-100)</small>
                    </div>

                    <div class="form-group">
                        <label for="max_discount">Максимална отстъпка</label>
                        <input type="number"id="max_discount"name="max_discount"
                               value="<?php echo htmlspecialchars($editDiscount['max_discount'] ?? ''); ?>"
                               step="0.01"min="0"placeholder="0">
                        <small class="hint">0 = без лимит (<?php echo $currency_symbol; ?>)</small>
                    </div>
                </div>

                <!-- Buy X Get Y Fields -->
                <div id="buy_x_get_y_fields"class="grid grid-2 mt-20"style="display: none;">
                    <div class="form-group">
                        <label for="buy_quantity">Купи X броя</label>
                        <input type="number"id="buy_quantity"name="buy_quantity"
                               value="<?php echo htmlspecialchars($editDiscount['buy_quantity'] ?? '2'); ?>"
                               min="1"placeholder="2">
                    </div>
                    <div class="form-group">
                        <label for="get_quantity">Вземи Y броя безплатно</label>
                        <input type="number"id="get_quantity"name="get_quantity"
                               value="<?php echo htmlspecialchars($editDiscount['get_quantity'] ?? '1'); ?>"
                               min="1"placeholder="1">
                    </div>
                </div>
            </fieldset>

            <!-- Conditions & Rules -->
            <fieldset class="form-section">
                <legend>📏 Условия и правила</legend>
                
                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="min_purchase">Минимална поръчка</label>
                        <input type="number"id="min_purchase"name="min_purchase"
                               value="<?php echo htmlspecialchars($editDiscount['min_purchase'] ?? '0'); ?>"
                               step="0.01"min="0"placeholder="0">
                        <small class="hint">Минимална сума (<?php echo $currency_symbol; ?>)</small>
                    </div>

                    <div class="form-group">
                        <label for="max_purchase">Максимална поръчка</label>
                        <input type="number"id="max_purchase"name="max_purchase"
                               value="<?php echo htmlspecialchars($editDiscount['max_purchase'] ?? ''); ?>"
                               step="0.01"min="0"placeholder="0">
                        <small class="hint">0 = без лимит (<?php echo $currency_symbol; ?>)</small>
                    </div>

                    <div class="form-group">
                        <label for="min_items">Минимум продукти</label>
                        <input type="number"id="min_items"name="min_items"
                               value="<?php echo htmlspecialchars($editDiscount['min_items'] ?? '0'); ?>"
                               min="0"placeholder="0">
                        <small class="hint">Минимален брой артикули</small>
                    </div>
                </div>

                <!-- Product/Category Selection -->
                <div class="grid grid-2 mt-20">
                    <div class="form-group">
                        <label for="applies_to">Приложима за</label>
                        <select id="applies_to"name="applies_to"onchange="updateAppliesTo()">
                            <option value="all"<?php echo ($editDiscount['applies_to'] ?? 'all') === 'all' ? 'selected' : ''; ?>>
                                🌐 Всички продукти
                            </option>
                            <option value="products"<?php echo ($editDiscount['applies_to'] ?? '') === 'products' ? 'selected' : ''; ?>>
                                🏷️ Конкретни продукти
                            </option>
                            <option value="categories"<?php echo ($editDiscount['applies_to'] ?? '') === 'categories' ? 'selected' : ''; ?>>
                                📂 Конкретни категории
                            </option>
                            <option value="except_products"<?php echo ($editDiscount['applies_to'] ?? '') === 'except_products' ? 'selected' : ''; ?>>
                                🚫 Всички освен избрани
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="customer_eligibility">Приложима за клиенти</label>
                        <select id="customer_eligibility"name="customer_eligibility">
                            <option value="all"<?php echo ($editDiscount['customer_eligibility'] ?? 'all') === 'all' ? 'selected' : ''; ?>>
                                👥 Всички клиенти
                            </option>
                            <option value="new"<?php echo ($editDiscount['customer_eligibility'] ?? '') === 'new' ? 'selected' : ''; ?>>
                                🆕 Само нови клиенти
                            </option>
                            <option value="returning"<?php echo ($editDiscount['customer_eligibility'] ?? '') === 'returning' ? 'selected' : ''; ?>>
                                🔁 Само върнали се клиенти
                            </option>
                            <option value="vip"<?php echo ($editDiscount['customer_eligibility'] ?? '') === 'vip' ? 'selected' : ''; ?>>
                                ⭐ VIP клиенти
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Product Selection (hidden by default) -->
                <div id="product_selection"style="display: none;"class="mt-20">
                    <div class="form-group">
                        <label for="product_ids">Избери продукти</label>
                        <select id="product_ids"name="product_ids[]"multiple size="10"class="select-multi">
                            <?php
                            $selectedProducts = $editDiscount['product_ids'] ?? [];
                            foreach ($products as $product):
                                $selected = in_array($product['id'], $selectedProducts) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"<?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?> - <?php echo $currency_symbol; ?><?php echo number_format($product['price'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="hint">Ctrl+Click за избор на няколко продукта</small>
                    </div>
                </div>

                <!-- Category Selection (hidden by default) -->
                <div id="category_selection"style="display: none;"class="mt-20">
                    <div class="form-group">
                        <label for="category_ids">Избери категории</label>
                        <select id="category_ids"name="category_ids[]"multiple size="6"class="select-multi">
                            <?php
                            $selectedCategories = $editDiscount['category_ids'] ?? [];
                            foreach ($categories as $category):
                                $selected = in_array($category['id'], $selectedCategories) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>"<?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="hint">Ctrl+Click за избор на няколко категории</small>
                    </div>
                </div>
            </fieldset>

            <!-- Usage Limits & Scheduling -->
            <fieldset class="form-section">
                <legend>⏱️ Лимити и график</legend>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="start_date">Начална дата</label>
                        <input type="datetime-local"id="start_date"name="start_date"
                               value="<?php echo htmlspecialchars($editDiscount['start_date'] ?? ''); ?>">
                        <small class="hint">Оставете празно за незабавен старт</small>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Крайна дата</label>
                        <input type="datetime-local"id="end_date"name="end_date"
                               value="<?php echo htmlspecialchars($editDiscount['end_date'] ?? ''); ?>">
                        <small class="hint">Оставете празно за безсрочна</small>
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="max_uses">Максимум използвания</label>
                        <input type="number"id="max_uses"name="max_uses"
                               value="<?php echo htmlspecialchars($editDiscount['max_uses'] ?? ''); ?>"
                               min="0"placeholder="0">
                        <small class="hint">0 = неограничено</small>
                    </div>

                    <div class="form-group">
                        <label for="max_uses_per_customer">На клиент</label>
                        <input type="number"id="max_uses_per_customer"name="max_uses_per_customer"
                               value="<?php echo htmlspecialchars($editDiscount['max_uses_per_customer'] ?? '1'); ?>"
                               min="1"placeholder="1">
                        <small class="hint">Колко пъти може да се ползва</small>
                    </div>

                    <div class="form-group">
                        <label for="priority">Приоритет</label>
                        <input type="number"id="priority"name="priority"
                               value="<?php echo htmlspecialchars($editDiscount['priority'] ?? '0'); ?>"
                               min="0"max="100"placeholder="0">
                        <small class="hint">По-висок = първи се прилага</small>
                    </div>
                </div>
            </fieldset>

            <!-- Advanced Options -->
            <fieldset class="form-section">
                <legend>⚙️ Допълнителни настройки</legend>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox"name="active"value="1"
                               <?php echo ($editDiscount['active'] ?? true) ? 'checked' : ''; ?>>
                        <span>✅ Активна отстъпка</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="combinable"value="1"
                               <?php echo ($editDiscount['combinable'] ?? false) ? 'checked' : ''; ?>>
                        <span>🔗 Може да се комбинира с други отстъпки</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="auto_apply"value="1"
                               <?php echo ($editDiscount['auto_apply'] ?? false) ? 'checked' : ''; ?>>
                        <span>🤖 Автоматично прилагане (без код)</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="first_purchase_only"value="1"
                               <?php echo ($editDiscount['first_purchase_only'] ?? false) ? 'checked' : ''; ?>>
                        <span>🎁 Само за първа поръчка</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="exclude_sale_items"value="1"
                               <?php echo ($editDiscount['exclude_sale_items'] ?? false) ? 'checked' : ''; ?>>
                        <span>🚫 Изключи вече намалени продукти</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="show_in_promotions"value="1"
                               <?php echo ($editDiscount['show_in_promotions'] ?? true) ? 'checked' : ''; ?>>
                        <span>📢 Покажи в промоционален банер</span>
                    </label>
                </div>
            </fieldset>

            <!-- Internal Notes -->
            <fieldset class="form-section">
                <legend>📝 Вътрешни бележки</legend>
                <div class="form-group">
                    <textarea name="internal_notes"rows="3"class="w-full"
                              placeholder="Бележки за вътрешна употреба (не се показват на клиенти)"><?php echo htmlspecialchars($editDiscount['internal_notes'] ?? ''); ?></textarea>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit"class="btn">
                    <?php echo icon_check(18); ?> Запази отстъпка
                </button>
                <a href="?section=discounts"class="btn-secondary">
                    <?php echo icon_x(18); ?> Отказ
                </a>
                <?php if ($editDiscount): ?>
                    <button type="button"class="btn-muted"onclick="duplicateDiscount()">
                        📋 Дублирай
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    // Dynamic form updates
    function updateDiscountValueHint() {
        const type = document.getElementById('discount_type').value;
        const valueField = document.getElementById('value_field');
        const valueInput = document.getElementById('discount_value');
        const valueHint = document.getElementById('value_hint');
        const buyXGetYFields = document.getElementById('buy_x_get_y_fields');
        
        if (type === 'percentage') {
            valueField.style.display = '';
            valueInput.max = 100;
            valueInput.required = true;
            valueHint.textContent = 'Въведете процент (0-100)';
            buyXGetYFields.style.display = 'none';
        } else if (type === 'fixed') {
            valueField.style.display = '';
            valueInput.max = '';
            valueInput.required = true;
            valueHint.textContent = 'Въведете сума в <?php echo $currency_symbol; ?>';
            buyXGetYFields.style.display = 'none';
        } else if (type === 'free_shipping') {
            valueField.style.display = 'none';
            valueInput.required = false;
            buyXGetYFields.style.display = 'none';
        } else if (type === 'buy_x_get_y') {
            valueField.style.display = 'none';
            valueInput.required = false;
            buyXGetYFields.style.display = 'grid';
        }
    }

    function updateAppliesTo() {
        const appliesTo = document.getElementById('applies_to').value;
        const productSelection = document.getElementById('product_selection');
        const categorySelection = document.getElementById('category_selection');
        
        productSelection.style.display = 'none';
        categorySelection.style.display = 'none';
        
        if (appliesTo === 'products' || appliesTo === 'except_products') {
            productSelection.style.display = 'block';
        } else if (appliesTo === 'categories') {
            categorySelection.style.display = 'block';
        }
    }

    function duplicateDiscount() {
        if (confirm('Дублирай тази отстъпка?')) {
            const codeInput = document.getElementById('discount_code');
            codeInput.value = codeInput.value + '_COPY';
            document.querySelector('input[name="discount_id"]').value = '';
            document.getElementById('discountForm').submit();
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        updateDiscountValueHint();
        updateAppliesTo();
    });
    </script>

<?php else: ?>
    <!-- Discounts List -->
    <?php if (empty($discounts)): ?>
        <div class="card text-center">
            <div class="p-50">
                <?php echo icon_percent(48); ?><br><br>
                <h3 class="text-muted mb-10">Няма създадени отстъпки</h3>
                <p class="text-muted mb-20">Създайте първата си отстъпка, за да привлечете клиенти и увеличите продажбите</p>
                <a href="?section=discounts&action=new"class="btn">
                    <?php echo icon_percent(18); ?> Създай първа отстъпка
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Quick Stats -->
        <div class="grid grid-4 mb-20">
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Общо</span>
                    <span class="stat-mini-value"><?php echo $total_discounts; ?></span>
                </div>
            </div>
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Активни</span>
                    <span class="stat-mini-value text-success"><?php echo $active_discounts; ?></span>
                </div>
            </div>
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Използвания</span>
                    <span class="stat-mini-value text-warning"><?php echo $total_uses; ?></span>
                </div>
            </div>
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Спестявания</span>
                    <span class="stat-mini-value text-accent"><?php echo $currency_symbol; ?><?php echo number_format($total_savings, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Код</th>
                            <th>Тип</th>
                            <th>Стойност</th>
                            <th>Условия</th>
                            <th>Използвания</th>
                            <th>Период</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($discounts as $discount): ?>
                            <tr>
                                <td>
                                    <code class="code-pill"><?php echo htmlspecialchars($discount['code']); ?></code>
                                    <?php if (!empty($discount['name'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($discount['name']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($discount['auto_apply'] ?? false): ?>
                                        <br><span class="badge badge-info">🤖 Авто</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeIcons = [
                                        'percentage' => '📊',
                                        'fixed' => '💶',
                                        'free_shipping' => '🚚',
                                        'buy_x_get_y' => '🎁'
                                    ];
                                    echo $typeIcons[$discount['type']] ?? '🏷️';
                                    ?>
                                </td>
                                <td class="font-semibold text-primary">
                                    <?php
                                    if ($discount['type'] === 'percentage') {
                                        echo $discount['value'] . '%';
                                    } elseif ($discount['type'] === 'fixed') {
                                        echo $currency_symbol . number_format($discount['value'], 2);
                                    } elseif ($discount['type'] === 'free_shipping') {
                                        echo 'Безплатна';
                                    } elseif ($discount['type'] === 'buy_x_get_y') {
                                        $buy = $discount['buy_quantity'] ?? 2;
                                        $get = $discount['get_quantity'] ?? 1;
                                        echo "{$buy}+{$get}";
                                    }
                                    ?>
                                </td>
                                <td class="text-sm">
                                    <?php
                                    $conditions = [];
                                    if (!empty($discount['min_purchase']) && $discount['min_purchase'] > 0) {
                                        $conditions[] = 'Мин: ' . $currency_symbol . number_format($discount['min_purchase'], 2);
                                    }
                                    if (!empty($discount['applies_to']) && $discount['applies_to'] !== 'all') {
                                        $appliesLabels = [
                                            'products' => '🏷️ Продукти',
                                            'categories' => '📂 Категории',
                                            'except_products' => '🚫 Изключения'
                                        ];
                                        $conditions[] = $appliesLabels[$discount['applies_to']] ?? '';
                                    }
                                    if ($discount['first_purchase_only'] ?? false) {
                                        $conditions[] = '🎁 Нови';
                                    }
                                    echo !empty($conditions) ? implode('<br>', $conditions) : '—';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $used = $discount['used_count'] ?? 0;
                                    $max = $discount['max_uses'] ?? 0;
                                    $percentage = $max > 0 ? round(($used / $max) * 100) : 0;
                                    ?>
                                    <div class="usage-indicator">
                                        <span class="usage-text"><?php echo $used; ?> / <?php echo $max > 0 ? $max : '∞'; ?></span>
                                        <?php if ($max > 0): ?>
                                            <div class="usage-bar">
                                                <div class="usage-bar-fill"style="width: <?php echo min(100, $percentage); ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-sm text-muted">
                                    <?php if (!empty($discount['start_date']) || !empty($discount['end_date'])): ?>
                                        <?php if (!empty($discount['start_date'])): ?>
                                            <?php echo date('d.m.Y', strtotime($discount['start_date'])); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($discount['end_date'])): ?>
                                            <br>до <?php echo date('d.m.Y', strtotime($discount['end_date'])); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        ∞
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $now = time();
                                    $isActive = $discount['active'] ?? false;
                                    $hasStarted = empty($discount['start_date']) || strtotime($discount['start_date']) <= $now;
                                    $notEnded = empty($discount['end_date']) || strtotime($discount['end_date']) >= $now;
                                    $notMaxed = ($discount['max_uses'] ?? 0) == 0 || ($discount['used_count'] ?? 0) < $discount['max_uses'];
                                    
                                    if ($isActive && $hasStarted && $notEnded && $notMaxed): ?>
                                        <span class="status-pill status-active">✓ Активна</span>
                                    <?php elseif (!$hasStarted): ?>
                                        <span class="status-pill status-scheduled">⏰ Планирана</span>
                                    <?php elseif (!$notEnded): ?>
                                        <span class="status-pill status-expired">⏱️ Изтекла</span>
                                    <?php elseif (!$notMaxed): ?>
                                        <span class="status-pill status-maxed">🔒 Изчерпана</span>
                                    <?php else: ?>
                                        <span class="status-pill status-inactive">⏸️ Неактивна</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <div class="flex-gap-8">
                                        <a href="?section=discounts&action=edit&id=<?php echo urlencode($discount['id']); ?>"
                                           class="btn-small"title="Редактирай">✏️</a>
                                        <form method="POST"class="inline-form"
                                              onsubmit="return confirm('Сигурни ли сте, че искате да изтриете тази отстъпка?');">
                                            <input type="hidden"name="action"value="delete_discount">
                                            <input type="hidden"name="discount_id"value="<?php echo htmlspecialchars($discount['id']); ?>">
                                            <button type="submit"class="btn-delete"title="Изтрий">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
.form-section {
    border: 1px solid var(--border-color, #e0e0e0);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-section legend {
    font-weight: 600;
    padding: 0 10px;
    font-size: 16px;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background 0.2s;
}

.checkbox-label:hover {
    background: var(--background-hover, #f5f5f5);
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.usage-indicator {
    min-width: 100px;
}

.usage-text {
    font-size: 13px;
    font-weight: 500;
}

.usage-bar {
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    margin-top: 4px;
    overflow: hidden;
}

.usage-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #4caf50, #8bc34a);
    transition: width 0.3s;
}

.code-pill {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 12px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    font-size: 14px;
}

.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-info {
    background: #e1f5fe;
    color: #01579b;
}

.input-uppercase {
    text-transform: uppercase;
}

.card-mini {
    background: var(--card-background, #fff);
    border: 1px solid var(--border-color, #e0e0e0);
    border-radius: 8px;
    padding: 15px;
}

.stat-mini {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.stat-mini-label {
    font-size: 12px;
    color: var(--text-muted, #666);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-mini-value {
    font-size: 24px;
    font-weight: 700;
}

.status-pill {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-active { background: #e8f5e9; color: #2e7d32; }
.status-inactive { background: #e0e0e0; color: #616161; }
.status-scheduled { background: #fff3e0; color: #e65100; }
.status-expired { background: #ffebee; color: #c62828; }
.status-maxed { background: #f3e5f5; color: #6a1b9a; }
</style>
