<?php
/**
 * Professional Promotions Management System
 * Marketing campaigns, banners, popups, and promotional events
 */

$promotions = get_promotions_data();
$products = get_products_data();
$categories = get_categories_data();
$editPromotion = null;

// Get currency settings
$currency_settings = get_currency_settings();
$currency_symbol = $currency_settings['symbol'];

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

// Statistics
$total_promotions = count($promotions);
$active_promotions = count(array_filter($promotions, function($p) { return $p['active'] ?? false; }));
$visual_promotions = count(array_filter($promotions, function($p) { return in_array($p['type'] ?? '', ['banner', 'popup', 'notification', 'homepage']); }));
$campaign_promotions = count(array_filter($promotions, function($p) { return in_array($p['type'] ?? '', ['seasonal', 'flash_sale', 'clearance', 'new_arrival']); }));
?>

<div class="section-header">
    <h2><?php echo icon_megaphone(24); ?> Управление на промоции</h2>
    <div class="flex-gap-8">
        <?php if ($action !== 'new' && $action !== 'edit'): ?>
            <a href="?section=promotions&action=analytics"class="btn-secondary">
                📊 Аналитика
            </a>
            <a href="?section=promotions&action=new"class="btn">
                <?php echo icon_megaphone(18); ?> Нова промоция
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
            <h4 class="text-muted mb-10">Общо промоции</h4>
            <p class="stat-number text-primary"><?php echo $total_promotions; ?></p>
        </div>
        <div class="card text-center">
            <h4 class="text-muted mb-10">Активни</h4>
            <p class="stat-number text-success"><?php echo $active_promotions; ?></p>
        </div>
        <div class="card text-center">
            <h4 class="text-muted mb-10">Визуални</h4>
            <p class="stat-number text-info"><?php echo $visual_promotions; ?></p>
        </div>
        <div class="card text-center">
            <h4 class="text-muted mb-10">Кампании</h4>
            <p class="stat-number text-warning"><?php echo $campaign_promotions; ?></p>
        </div>
    </div>
    <a href="?section=promotions"class="btn-secondary mb-20">← Назад</a>
<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <!-- Create/Edit Promotion Form -->
    <div class="card">
        <h3><?php echo $action === 'edit' ? 'Редактирай промоция' : 'Създай нова промоция'; ?></h3>
        
        <form method="POST"id="promotionForm"enctype="multipart/form-data">
            <input type="hidden"name="action"value="save_promotion">
            <input type="hidden"name="promotion_id"value="<?php echo htmlspecialchars($editPromotion['id'] ?? ''); ?>">
            
            <!-- Basic Information -->
            <fieldset class="form-section">
                <legend>📋 Основна информация</legend>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="promotion_title">Заглавие *</label>
                        <input type="text"id="promotion_title"name="title"
                               value="<?php echo htmlspecialchars($editPromotion['title'] ?? ''); ?>"
                               required 
                               placeholder="Лятна разпродажба 2026">
                    </div>

                    <div class="form-group">
                        <label for="promotion_type">Тип промоция *</label>
                        <select name="type"id="promotion_type"required onchange="updatePromotionTypeFields()">
                            <optgroup label="🎨 Визуални промоции">
                                <option value="banner"<?php echo ($editPromotion['type'] ?? '') === 'banner' ? 'selected' : ''; ?>>
                                    🖼️ Банер (Homepage/Category)
                                </option>
                                <option value="sidebar_left"<?php echo ($editPromotion['type'] ?? '') === 'sidebar_left' ? 'selected' : ''; ?>>
                                    ⬅️ Вертикален банер - Ляво
                                </option>
                                <option value="sidebar_right"<?php echo ($editPromotion['type'] ?? '') === 'sidebar_right' ? 'selected' : ''; ?>>
                                    ➡️ Вертикален банер - Дясно
                                </option>
                                <option value="popup"<?php echo ($editPromotion['type'] ?? '') === 'popup' ? 'selected' : ''; ?>>
                                    💬 Popup прозорец
                                </option>
                                <option value="notification"<?php echo ($editPromotion['type'] ?? '') === 'notification' ? 'selected' : ''; ?>>
                                    🔔 Известие (Top bar)
                                </option>
                                <option value="slider"<?php echo ($editPromotion['type'] ?? '') === 'slider' ? 'selected' : ''; ?>>
                                    🎠 Слайдер (Homepage)
                                </option>
                            </optgroup>
                            <optgroup label="🎯 Маркетинг кампании">
                                <option value="seasonal"<?php echo ($editPromotion['type'] ?? '') === 'seasonal' ? 'selected' : ''; ?>>
                                    🌸 Сезонна кампания
                                </option>
                                <option value="flash_sale"<?php echo ($editPromotion['type'] ?? '') === 'flash_sale' ? 'selected' : ''; ?>>
                                    ⚡ Светкавична разпродажба
                                </option>
                                <option value="clearance"<?php echo ($editPromotion['type'] === 'clearance' ? 'selected' : ''); ?>>
                                    🏷️ Разпродажба
                                </option>
                                <option value="new_arrival"<?php echo ($editPromotion['type'] ?? '') === 'new_arrival' ? 'selected' : ''; ?>>
                                    ✨ Нови продукти
                                </option>
                                <option value="bundle_deal"<?php echo ($editPromotion['type'] ?? '') === 'bundle_deal' ? 'selected' : ''; ?>>
                                    📦 Пакетна оферта
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="promotion_description">Описание</label>
                    <textarea id="promotion_description"name="description"rows="3"
                              placeholder="До 50% отстъпка на всички летни продукти"><?php echo htmlspecialchars($editPromotion['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="promotion_subtitle">Подзаглавие</label>
                    <input type="text"id="promotion_subtitle"name="subtitle"
                           value="<?php echo htmlspecialchars($editPromotion['subtitle'] ?? ''); ?>"
                           placeholder="Валидна до 31 август">
                    <small class="hint">Допълнителен текст под заглавието</small>
                </div>
            </fieldset>

            <!-- Visual Settings (for visual promotions) -->
            <fieldset class="form-section"id="visual_settings">
                <legend>🎨 Визуални настройки</legend>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="promotion_image">Изображение/Банер</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="text"id="promotion_image"name="image"
                                   value="<?php echo htmlspecialchars($editPromotion['image'] ?? ''); ?>"
                                   placeholder="/uploads/promotions/summer-sale.jpg"
                                   style="flex: 1;">
                            <button type="button"class="btn-secondary"onclick="openMediaPicker()"style="white-space: nowrap;">
                                🖼️ Избери от галерия
                            </button>
                        </div>
                        <small class="hint">URL на изображение, избери от галерия или качи нов файл</small>
                    </div>

                    <div class="form-group">
                        <label for="promotion_image_upload">Качи ново изображение</label>
                        <input type="file"id="promotion_image_upload"name="image_upload"accept="image/*">
                        <small class="hint">JPG, PNG или WebP (макс 2MB)</small>
                    </div>
                </div>
                
                <?php if (!empty($editPromotion['image'])): ?>
                <div class="form-group">
                    <label>Текуща снимка:</label>
                    <div style="max-width: 300px; border: 1px solid var(--border-color); border-radius: 4px; padding: 8px;">
                        <img src="<?php echo htmlspecialchars($editPromotion['image']); ?>"
                             alt="Preview"
                             style="width: 100%; height: auto; border-radius: 4px;">
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="promotion_link">Линк при клик</label>
                        <input type="url"id="promotion_link"name="link"
                               value="<?php echo htmlspecialchars($editPromotion['link'] ?? ''); ?>"
                               placeholder="https://offmetabg.com/sale">
                        <small class="hint">Къде да води банера при кликване</small>
                    </div>

                    <div class="form-group">
                        <label for="promotion_button_text">Текст на бутон</label>
                        <input type="text"id="promotion_button_text"name="button_text"
                               value="<?php echo htmlspecialchars($editPromotion['button_text'] ?? ''); ?>"
                               placeholder="Виж всички оферти">
                        <small class="hint">Текст на CTA бутона (ако има)</small>
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="background_color">Цвят на фон</label>
                        <input type="color"id="background_color"name="background_color"
                               value="<?php echo htmlspecialchars($editPromotion['background_color'] ?? '#ffffff'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="text_color">Цвят на текст</label>
                        <input type="color"id="text_color"name="text_color"
                               value="<?php echo htmlspecialchars($editPromotion['text_color'] ?? '#000000'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="button_color">Цвят на бутон</label>
                        <input type="color"id="button_color"name="button_color"
                               value="<?php echo htmlspecialchars($editPromotion['button_color'] ?? '#007bff'); ?>">
                    </div>
                </div>

                <!-- Popup specific settings -->
                <div id="popup_settings"style="display: none;">
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label for="popup_delay">Закъснение (секунди)</label>
                            <input type="number"id="popup_delay"name="popup_delay"
                                   value="<?php echo htmlspecialchars($editPromotion['popup_delay'] ?? '3'); ?>"
                                   min="0"max="60">
                            <small class="hint">След колко секунди да се покаже</small>
                        </div>

                        <div class="form-group">
                            <label for="popup_frequency">Честота на показване</label>
                            <select id="popup_frequency"name="popup_frequency">
                                <option value="always"<?php echo ($editPromotion['popup_frequency'] ?? 'once_per_session') === 'always' ? 'selected' : ''; ?>>
                                    Винаги
                                </option>
                                <option value="once_per_session"<?php echo ($editPromotion['popup_frequency'] ?? 'once_per_session') === 'once_per_session' ? 'selected' : ''; ?>>
                                    Веднъж на сесия
                                </option>
                                <option value="once_per_day"<?php echo ($editPromotion['popup_frequency'] ?? '') === 'once_per_day' ? 'selected' : ''; ?>>
                                    Веднъж на ден
                                </option>
                                <option value="once_per_week"<?php echo ($editPromotion['popup_frequency'] ?? '') === 'once_per_week' ? 'selected' : ''; ?>>
                                    Веднъж на седмица
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>&nbsp;</label>
                            <label class="checkbox-label">
                                <input type="checkbox"name="popup_closable"value="1"
                                       <?php echo ($editPromotion['popup_closable'] ?? true) ? 'checked' : ''; ?>>
                                <span>Може да се затвори</span>
                            </label>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Product/Category Target -->
            <fieldset class="form-section">
                <legend>🎯 Таргетиране</legend>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="target_type">Свързана с</label>
                        <select id="target_type"name="target_type"onchange="updateTargetFields()">
                            <option value="all"<?php echo ($editPromotion['target_type'] ?? 'all') === 'all' ? 'selected' : ''; ?>>
                                🌐 Общовалидна
                            </option>
                            <option value="products"<?php echo ($editPromotion['target_type'] ?? '') === 'products' ? 'selected' : ''; ?>>
                                🏷️ Конкретни продукти
                            </option>
                            <option value="categories"<?php echo ($editPromotion['target_type'] ?? '') === 'categories' ? 'selected' : ''; ?>>
                                📂 Конкретни категории
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="audience">Целева аудитория</label>
                        <select id="audience"name="audience">
                            <option value="all"<?php echo ($editPromotion['audience'] ?? 'all') === 'all' ? 'selected' : ''; ?>>
                                👥 Всички посетители
                            </option>
                            <option value="new"<?php echo ($editPromotion['audience'] ?? '') === 'new' ? 'selected' : ''; ?>>
                                🆕 Нови посетители
                            </option>
                            <option value="returning"<?php echo ($editPromotion['audience'] ?? '') === 'returning' ? 'selected' : ''; ?>>
                                🔁 Върнали се посетители
                            </option>
                            <option value="registered"<?php echo ($editPromotion['audience'] ?? '') === 'registered' ? 'selected' : ''; ?>>
                                👤 Регистрирани потребители
                            </option>
                            <option value="guests"<?php echo ($editPromotion['audience'] ?? '') === 'guests' ? 'selected' : ''; ?>>
                                👻 Гости (нерегистрирани)
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Product Selection -->
                <div id="product_target"style="display: none;">
                    <div class="form-group">
                        <label for="target_products">Избери продукти</label>
                        <select id="target_products"name="product_ids[]"multiple size="10"class="select-multi">
                            <?php 
                            $selectedProducts = $editPromotion['product_ids'] ?? [];
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

                <!-- Category Selection -->
                <div id="category_target"style="display: none;">
                    <div class="form-group">
                        <label for="target_categories">Избери категории</label>
                        <select id="target_categories"name="category_ids[]"multiple size="6"class="select-multi">
                            <?php 
                            $selectedCategories = $editPromotion['category_ids'] ?? [];
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

                <!-- Location targeting -->
                <div class="form-group mt-20">
                    <label for="pages">Покажи на страници</label>
                    <select id="pages"name="pages[]"multiple size="5"class="select-multi">
                        <?php 
                        $selectedPages = $editPromotion['pages'] ?? ['homepage'];
                        $pageOptions = [
                            'homepage' => '🏠 Начална страница',
                            'category' => '📂 Страници на категории',
                            'product' => '🏷️ Страници на продукти',
                            'cart' => '🛒 Кошница',
                            'checkout' => '💳 Поръчка',
                            'all' => '🌐 Всички страници'
                        ];
                        foreach ($pageOptions as $value => $label): 
                            $selected = in_array($value, $selectedPages) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $value; ?>"<?php echo $selected; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="hint">Изберете къде да се показва промоцията</small>
                </div>
            </fieldset>

            <!-- Scheduling & Priority -->
            <fieldset class="form-section">
                <legend>⏱️ График и приоритет</legend>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="start_date">Начална дата и час</label>
                        <input type="datetime-local"id="start_date"name="start_date"
                               value="<?php echo htmlspecialchars($editPromotion['start_date'] ?? ''); ?>">
                        <small class="hint">Оставете празно за незабавен старт</small>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Крайна дата и час</label>
                        <input type="datetime-local"id="end_date"name="end_date"
                               value="<?php echo htmlspecialchars($editPromotion['end_date'] ?? ''); ?>">
                        <small class="hint">Оставете празно за безсрочна</small>
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="priority">Приоритет</label>
                        <input type="number"id="priority"name="priority"
                               value="<?php echo htmlspecialchars($editPromotion['priority'] ?? '0'); ?>"
                               min="0"max="100"placeholder="0">
                        <small class="hint">По-висок = показва се първи</small>
                    </div>

                    <div class="form-group">
                        <label for="display_order">Ред на показване</label>
                        <input type="number"id="display_order"name="display_order"
                               value="<?php echo htmlspecialchars($editPromotion['display_order'] ?? '0'); ?>"
                               min="0"placeholder="0">
                        <small class="hint">Позиция в слайдер/списък</small>
                    </div>

                    <div class="form-group">
                        <label for="max_impressions">Макс. показвания</label>
                        <input type="number"id="max_impressions"name="max_impressions"
                               value="<?php echo htmlspecialchars($editPromotion['max_impressions'] ?? ''); ?>"
                               min="0"placeholder="0">
                        <small class="hint">0 = неограничено</small>
                    </div>
                </div>
            </fieldset>

            <!-- Options -->
            <fieldset class="form-section">
                <legend>⚙️ Допълнителни настройки</legend>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox"name="active"value="1"
                               <?php echo ($editPromotion['active'] ?? true) ? 'checked' : ''; ?>>
                        <span>✅ Активна промоция</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="featured"value="1"
                               <?php echo ($editPromotion['featured'] ?? false) ? 'checked' : ''; ?>>
                        <span>⭐ Избрана (Featured)</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="show_countdown"value="1"
                               <?php echo ($editPromotion['show_countdown'] ?? false) ? 'checked' : ''; ?>>
                        <span>⏰ Покажи обратно броене</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="mobile_only"value="1"
                               <?php echo ($editPromotion['mobile_only'] ?? false) ? 'checked' : ''; ?>>
                        <span>📱 Само за мобилни устройства</span>
                    </label>

                    <label class="checkbox-label">
                        <input type="checkbox"name="desktop_only"value="1"
                               <?php echo ($editPromotion['desktop_only'] ?? false) ? 'checked' : ''; ?>>
                        <span>💻 Само за десктоп</span>
                    </label>
                </div>
            </fieldset>

            <!-- Linked Discount (Optional) -->
            <fieldset class="form-section">
                <legend>🎁 Свързана отстъпка (опционално)</legend>
                
                <div class="form-group">
                    <label for="discount_code">Промо код за автоматично прилагане</label>
                    <input type="text"id="discount_code"name="discount_code"
                           value="<?php echo htmlspecialchars($editPromotion['discount_code'] ?? ''); ?>"
                           placeholder="SUMMER2026">
                    <small class="hint">Ако се попълни, този код ще се прилага автоматично при кликване на промоцията</small>
                </div>
            </fieldset>

            <!-- Internal Notes -->
            <fieldset class="form-section">
                <legend>📝 Вътрешни бележки</legend>
                <div class="form-group">
                    <textarea name="internal_notes"rows="3"class="w-full"
                              placeholder="Бележки за вътрешна употреба (не се показват на клиенти)"><?php echo htmlspecialchars($editPromotion['internal_notes'] ?? ''); ?></textarea>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit"class="btn">
                    <?php echo icon_check(18); ?> Запази промоция
                </button>
                <a href="?section=promotions"class="btn-secondary">
                    <?php echo icon_x(18); ?> Отказ
                </a>
                <?php if ($editPromotion): ?>
                    <button type="button"class="btn-muted"onclick="previewPromotion()">
                        👁️ Преглед
                    </button>
                    <button type="button"class="btn-muted"onclick="duplicatePromotion()">
                        📋 Дублирай
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    // Dynamic form updates
    function updatePromotionTypeFields() {
        const type = document.getElementById('promotion_type').value;
        const visualSettings = document.getElementById('visual_settings');
        const popupSettings = document.getElementById('popup_settings');
        
        // Show visual settings for visual promotion types
        if (['banner', 'popup', 'notification', 'slider'].includes(type)) {
            visualSettings.style.display = 'block';
        } else {
            visualSettings.style.display = 'none';
        }
        
        // Show popup specific settings
        if (type === 'popup') {
            popupSettings.style.display = 'block';
        } else {
            popupSettings.style.display = 'none';
        }
    }

    function updateTargetFields() {
        const targetType = document.getElementById('target_type').value;
        const productTarget = document.getElementById('product_target');
        const categoryTarget = document.getElementById('category_target');
        
        productTarget.style.display = 'none';
        categoryTarget.style.display = 'none';
        
        if (targetType === 'products') {
            productTarget.style.display = 'block';
        } else if (targetType === 'categories') {
            categoryTarget.style.display = 'block';
        }
    }

    function previewPromotion() {
        // Get form values
        const title = document.getElementById('promotion_title')?.value || 'Заглавие на промоцията';
        const description = document.getElementById('promotion_description')?.value || 'Описание на промоцията';
        const type = document.getElementById('promotion_type')?.value || 'banner';
        const bgColor = document.getElementById('promotion_bg_color')?.value || '#ffffff';
        const textColor = document.getElementById('promotion_text_color')?.value || '#000000';
        const buttonColor = document.getElementById('promotion_button_color')?.value || '#2563eb';
        const buttonText = document.getElementById('promotion_button_text')?.value || 'Научи повече';
        
        // Create preview modal
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999;';
        
        let previewContent = '';
        
        if (type === 'popup' || type === 'banner') {
            previewContent = `
                <div style="background:${bgColor};color:${textColor};padding:40px;border-radius:8px;max-width:600px;text-align:center;">
                    <h2 style="margin:0 0 15px 0;color:${textColor};">${title}</h2>
                    <p style="margin:0 0 20px 0;color:${textColor};">${description}</p>
                    <button style="background:${buttonColor};color:white;border:none;padding:12px 24px;border-radius:6px;font-size:16px;cursor:pointer;">${buttonText}</button>
                </div>
            `;
        } else if (type === 'notification') {
            previewContent = `
                <div style="background:${bgColor};color:${textColor};padding:16px 24px;border-radius:6px;max-width:500px;display:flex;align-items:center;gap:12px;">
                    <span style="font-size:24px;">🔔</span>
                    <div style="flex:1;">
                        <strong style="color:${textColor};">${title}</strong>
                        <p style="margin:4px 0 0 0;font-size:14px;color:${textColor};">${description}</p>
                    </div>
                </div>
            `;
        } else {
            previewContent = `
                <div style="background:white;padding:30px;border-radius:8px;max-width:500px;">
                    <h3 style="margin:0 0 10px 0;">Преглед на промоцията</h3>
                    <p><strong>Заглавие:</strong> ${title}</p>
                    <p><strong>Описание:</strong> ${description}</p>
                    <p><strong>Тип:</strong> ${type}</p>
                </div>
            `;
        }
        
        modal.innerHTML = `
            <div>
                ${previewContent}
                <div style="text-align:center;margin-top:20px;">
                    <button onclick="this.closest('[style*=fixed]').remove()"style="background:#ef4444;color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;">Затвори прегледа</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
    }

    function duplicatePromotion() {
        if (confirm('Дублирай тази промоция?')) {
            const titleInput = document.getElementById('promotion_title');
            titleInput.value = titleInput.value + ' (Копие)';
            document.querySelector('input[name="promotion_id"]').value = '';
            document.getElementById('promotionForm').submit();
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        updatePromotionTypeFields();
        updateTargetFields();
    });
    </script>

<?php else: ?>
    <!-- Promotions List -->
    <?php if (empty($promotions)): ?>
        <div class="card text-center">
            <div class="p-50">
                <?php echo icon_megaphone(48); ?><br><br>
                <h3 class="text-muted mb-10">Няма създадени промоции</h3>
                <p class="text-muted mb-20">Създайте първата си промоция, за да увеличите видимостта на продуктите</p>
                <a href="?section=promotions&action=new"class="btn">
                    <?php echo icon_megaphone(18); ?> Създай първа промоция
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Quick Stats -->
        <div class="grid grid-4 mb-20">
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Общо</span>
                    <span class="stat-mini-value"><?php echo $total_promotions; ?></span>
                </div>
            </div>
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Активни</span>
                    <span class="stat-mini-value text-success"><?php echo $active_promotions; ?></span>
                </div>
            </div>
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Визуални</span>
                    <span class="stat-mini-value text-info"><?php echo $visual_promotions; ?></span>
                </div>
            </div>
            <div class="card-mini">
                <div class="stat-mini">
                    <span class="stat-mini-label">Кампании</span>
                    <span class="stat-mini-value text-warning"><?php echo $campaign_promotions; ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Заглавие</th>
                            <th>Тип</th>
                            <th>Таргет</th>
                            <th>Показвания</th>
                            <th>Период</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promotions as $promotion): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($promotion['title']); ?></strong>
                                    <?php if (!empty($promotion['description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($promotion['description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                    <?php if ($promotion['featured'] ?? false): ?>
                                        <br><span class="badge badge-warning">⭐ Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeIcons = [
                                        'banner' => '🖼️',
                                        'popup' => '💬',
                                        'notification' => '🔔',
                                        'slider' => '🎠',
                                        'seasonal' => '🌸',
                                        'flash_sale' => '⚡',
                                        'clearance' => '🏷️',
                                        'new_arrival' => '✨',
                                        'bundle_deal' => '📦'
                                    ];
                                    echo $typeIcons[$promotion['type']] ?? '📌';
                                    ?>
                                    <small class="text-muted d-block"><?php echo ucfirst(str_replace('_', ' ', $promotion['type'])); ?></small>
                                </td>
                                <td class="text-sm">
                                    <?php
                                    $targetType = $promotion['target_type'] ?? 'all';
                                    $targetLabels = [
                                        'all' => '🌐 Всички',
                                        'products' => '🏷️ Продукти',
                                        'categories' => '📂 Категории'
                                    ];
                                    echo $targetLabels[$targetType] ?? '—';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $impressions = $promotion['impressions'] ?? 0;
                                    $maxImpressions = $promotion['max_impressions'] ?? 0;
                                    ?>
                                    <span class="text-sm">
                                        <?php echo number_format($impressions); ?> 
                                        <?php if ($maxImpressions > 0): ?>
                                            / <?php echo number_format($maxImpressions); ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="text-sm text-muted">
                                    <?php if (!empty($promotion['start_date']) || !empty($promotion['end_date'])): ?>
                                        <?php if (!empty($promotion['start_date'])): ?>
                                            <?php echo date('d.m.Y', strtotime($promotion['start_date'])); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($promotion['end_date'])): ?>
                                            <br>до <?php echo date('d.m.Y', strtotime($promotion['end_date'])); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        ∞
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $now = time();
                                    $isActive = $promotion['active'] ?? false;
                                    $hasStarted = empty($promotion['start_date']) || strtotime($promotion['start_date']) <= $now;
                                    $notEnded = empty($promotion['end_date']) || strtotime($promotion['end_date']) >= $now;
                                    $notMaxed = ($promotion['max_impressions'] ?? 0) == 0 || ($promotion['impressions'] ?? 0) < $promotion['max_impressions'];
                                    
                                    if ($isActive && $hasStarted && $notEnded && $notMaxed): ?>
                                        <span class="status-pill status-active">✓ Активна</span>
                                    <?php elseif (!$hasStarted): ?>
                                        <span class="status-pill status-scheduled">⏰ Планирана</span>
                                    <?php elseif (!$notEnded): ?>
                                        <span class="status-pill status-expired">⏱️ Изтекла</span>
                                    <?php elseif (!$notMaxed): ?>
                                        <span class="status-pill status-maxed">🔒 Макс</span>
                                    <?php else: ?>
                                        <span class="status-pill status-inactive">⏸️ Неактивна</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <div class="flex-gap-8">
                                        <a href="?section=promotions&action=edit&id=<?php echo urlencode($promotion['id']); ?>"
                                           class="btn-small"title="Редактирай">✏️</a>
                                        <form method="POST"class="inline-form"
                                              onsubmit="return confirm('Сигурни ли сте, че искате да изтриете тази промоция?');">
                                            <input type="hidden"name="action"value="delete_promotion">
                                            <input type="hidden"name="promotion_id"value="<?php echo htmlspecialchars($promotion['id']); ?>">
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
/* Reuse styles from discounts */
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

.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-warning {
    background: #fff3e0;
    color: #e65100;
}

.d-block {
    display: block;
}

/* Media Picker Modal */
.media-picker-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.media-picker-modal.active {
    display: flex;
}

.media-picker-content {
    background: var(--bg-card, #fff);
    border-radius: 12px;
    max-width: 900px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.media-picker-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.media-picker-header h3 {
    margin: 0;
}

.media-picker-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.media-picker-close:hover {
    background: var(--bg-hover, #f5f5f5);
}

.media-picker-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.media-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}

.media-picker-item {
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--bg-hover, #f5f5f5);
}

.media-picker-item:hover {
    border-color: var(--primary, #2196F3);
    transform: scale(1.02);
}

.media-picker-item.selected {
    border-color: var(--success, #4CAF50);
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
}

.media-picker-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.media-picker-item-name {
    padding: 8px;
    font-size: 11px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-picker-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border-color, #e0e0e0);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>
<!-- Media Picker Modal -->
<div class="media-picker-modal" id="mediaPickerModal">
    <div class="media-picker-content">
        <div class="media-picker-header">
            <h3>🖼️ Избери изображение</h3>
            <button class="media-picker-close" onclick="closeMediaPicker()">&times;</button>
        </div>
        <div class="media-picker-body">
            <div class="media-picker-grid" id="mediaPickerGrid">
                <!-- Populated by JavaScript -->
            </div>
        </div>
        <div class="media-picker-footer">
            <button type="button" class="btn-secondary" onclick="closeMediaPicker()">Откажи</button>
            <button type="button" class="btn" onclick="selectMediaImage()">Избери</button>
        </div>
    </div>
</div>

<script>
let selectedMediaPath = null;

function openMediaPicker() {
    const modal = document.getElementById('mediaPickerModal');
    modal.classList.add('active');
    loadMediaFiles();
}

function closeMediaPicker() {
    const modal = document.getElementById('mediaPickerModal');
    modal.classList.remove('active');
    selectedMediaPath = null;
}

async function loadMediaFiles() {
    const grid = document.getElementById('mediaPickerGrid');
    grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">Зареждане...</p>';
    
    try {
        const response = await fetch('?section=media&ajax=list_files');
        if (!response.ok) {
            // Fallback - get files from uploads directory
            const uploadDir = '../uploads';
            const files = <?php 
                $uploadDir = CMS_ROOT . '/uploads';
                $files = is_dir($uploadDir) ? array_diff(scandir($uploadDir), ['.', '..']) : [];
                $imageFiles = array_filter($files, function($f) use ($uploadDir) {
                    return is_file("$uploadDir/$f") && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
                });
                echo json_encode(array_values($imageFiles));
            ?>;
            
            if (files.length === 0) {
                grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">Няма налични изображения. Качете нови от секция Медия.</p>';
                return;
            }
            
            grid.innerHTML = '';
            files.forEach(file => {
                const item = document.createElement('div');
                item.className = 'media-picker-item';
                item.onclick = () => selectMediaItem(item, '/uploads/' + file);
                
                item.innerHTML = `
                    <img src="/uploads/${file}" alt="${file}" loading="lazy">
                    <div class="media-picker-item-name" title="${file}">${file}</div>
                `;
                
                grid.appendChild(item);
            });
        }
    } catch (error) {
        grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: red;">Грешка при зареждане на файлове.</p>';
    }
}

function selectMediaItem(element, path) {
    // Remove previous selection
    document.querySelectorAll('.media-picker-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Mark current selection
    element.classList.add('selected');
    selectedMediaPath = path;
}

function selectMediaImage() {
    if (!selectedMediaPath) {
        alert('Моля, изберете изображение!');
        return;
    }
    
    // Set the image URL in the input field
    document.getElementById('promotion_image').value = selectedMediaPath;
    
    closeMediaPicker();
}

// Close modal on outside click
document.getElementById('mediaPickerModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeMediaPicker();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('mediaPickerModal').classList.contains('active')) {
        closeMediaPicker();
    }
});
</script>