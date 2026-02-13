<?php
/**
 * Discounts & Coupon Codes Management Section
 */

$discounts = get_discounts_data();
$editDiscount = null;

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
?>

<div class="section-header">
    <h2><?php echo icon_percent(24); ?> <?php echo __('admin.discounts'); ?></h2>
    <?php if ($action !== 'new' && $action !== 'edit'): ?>
        <a href="?section=discounts&action=new" class="btn"><?php echo icon_percent(18); ?> <?php echo __('admin.add_discount'); ?></a>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <!-- Create/Edit Discount Form -->
    <div class="card">
        <h3><?php echo $action === 'edit' ? __('admin.edit_discount') : __('admin.add_discount'); ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="save_discount">
            <input type="hidden" name="discount_id" value="<?php echo htmlspecialchars($editDiscount['id'] ?? ''); ?>">
            
            <div class="form-group">
                <label><?php echo __('admin.discount_code'); ?></label>
                <input type="text" name="code" value="<?php echo htmlspecialchars($editDiscount['code'] ?? ''); ?>" required class="input-uppercase">
                <small class="hint">Промо код (напр. SUMMER2026, WELCOME10)</small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.discount_description'); ?></label>
                <textarea name="description" rows="2"><?php echo htmlspecialchars($editDiscount['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.discount_type'); ?></label>
                <select name="type" id="discount_type" required onchange="updateDiscountFields()">
                    <option value="percentage" <?php echo ($editDiscount['type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Процент (%)</option>
                    <option value="fixed" <?php echo ($editDiscount['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Фиксирана сума (€)</option>
                    <option value="free_shipping" <?php echo ($editDiscount['type'] ?? '') === 'free_shipping' ? 'selected' : ''; ?>>Безплатна доставка</option>
                </select>
            </div>

            <div class="form-group" id="value_field">
                <label id="value_label"><?php echo __('admin.discount_value'); ?></label>
                <input type="number" name="value" id="value_input" value="<?php echo htmlspecialchars($editDiscount['value'] ?? ''); ?>" step="0.01" min="0">
                <small id="value_hint" class="hint"></small>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label><?php echo __('admin.min_purchase'); ?></label>
                    <input type="number" name="min_purchase" value="<?php echo htmlspecialchars($editDiscount['min_purchase'] ?? '0'); ?>" step="0.01" min="0">
                    <small class="hint">Минимална поръчка (€)</small>
                </div>

                <div class="form-group">
                    <label><?php echo __('admin.max_uses'); ?></label>
                    <input type="number" name="max_uses" value="<?php echo htmlspecialchars($editDiscount['max_uses'] ?? ''); ?>" min="0">
                    <small class="hint">0 за неограничено</small>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label><?php echo __('admin.start_date'); ?></label>
                    <input type="datetime-local" name="start_date" value="<?php echo htmlspecialchars($editDiscount['start_date'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label><?php echo __('admin.end_date'); ?></label>
                    <input type="datetime-local" name="end_date" value="<?php echo htmlspecialchars($editDiscount['end_date'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="active" value="1" <?php echo ($editDiscount['active'] ?? true) ? 'checked' : ''; ?>>
                    <?php echo __('admin.discount_active'); ?>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="first_purchase_only" value="1" <?php echo ($editDiscount['first_purchase_only'] ?? false) ? 'checked' : ''; ?>>
                    <?php echo __('admin.first_purchase_only'); ?>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn"><?php echo icon_check(18); ?> Запази отстъпка</button>
                <a href="?section=discounts" class="btn-secondary"><?php echo icon_x(18); ?> Отказ</a>
            </div>
        </form>
    </div>
    
    <script src="assets/js/discounts.js"></script>
<?php else: ?>
    <!-- Discounts List -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><?php echo __('admin.discount_code'); ?></th>
                    <th><?php echo __('admin.discount_type'); ?></th>
                    <th><?php echo __('admin.discount_value'); ?></th>
                    <th><?php echo __('admin.usage'); ?></th>
                    <th><?php echo __('admin.period'); ?></th>
                    <th><?php echo __('admin.status'); ?></th>
                    <th><?php echo __('admin.actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($discounts)): ?>
                    <tr>
                        <td colspan="7" class="table-empty">
                            <?php echo icon_percent(32); ?><br>
                            <?php echo __('admin.no_discounts'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td>
                                <code class="code-pill">
                                    <?php echo htmlspecialchars($discount['code']); ?>
                                </code>
                                <?php if (!empty($discount['description'])): ?>
                                    <br><small class="text-muted text-sm"><?php echo htmlspecialchars($discount['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $typeData = [
                                    'percentage' => ['icon' => '📊', 'label' => 'Процент', 'color' => '#8b5cf6'],
                                    'fixed' => ['icon' => '💶', 'label' => 'Фиксирана', 'color' => '#f97316'],
                                    'free_shipping' => ['icon' => '🚚', 'label' => 'Безплатна доставка', 'color' => '#27ae60']
                                ];
                                $type = $typeData[$discount['type']] ?? ['icon' => '🏷️', 'label' => $discount['type'], 'color' => '#6b7280'];
                                $typeClass = 'discount-type-' . str_replace('_', '-', $discount['type'] ?? 'default');
                                ?>
                                <span class="pill <?php echo htmlspecialchars($typeClass); ?>">
                                    <?php echo $type['icon']; ?> <?php echo $type['label']; ?>
                                </span>
                            </td>
                            <td>
                                <strong class="text-primary text-lg">
                                    <?php 
                                    if ($discount['type'] === 'percentage') {
                                        echo $discount['value'] . '%';
                                    } elseif ($discount['type'] === 'fixed') {
                                        echo '€' . number_format($discount['value'], 2);
                                    } else {
                                        echo '✓';
                                    }
                                    ?>
                                </strong>
                            </td>
                            <td>
                                <?php 
                                $used = $discount['used_count'] ?? 0;
                                $max = $discount['max_uses'] ?? 0;
                                ?>
                                <span class="text-sm">
                                    <?php echo $used; ?> / <?php echo $max > 0 ? $max : '∞'; ?>
                                </span>
                            </td>
                            <td class="text-sm text-muted">
                                <?php if (!empty($discount['start_date']) && !empty($discount['end_date'])): ?>
                                    <?php echo date('d.m.Y', strtotime($discount['start_date'])); ?><br><?php echo date('d.m.Y', strtotime($discount['end_date'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($discount['active'] ?? true): ?>
                                    <span class="status-pill status-active">
                                        <?php echo icon_check_circle(14); ?> Активна
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-inactive">
                                        <?php echo icon_x_circle(14); ?> Неактивна
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <div class="flex-gap-8">
                                    <a href="?section=discounts&action=edit&id=<?php echo urlencode($discount['id']); ?>" class="btn-small" title="Редактирай">
                                        ✏️ Редактирай
                                    </a>
                                    <form method="POST" class="inline-form" onsubmit="return confirm('<?php echo __('admin.confirm_delete_discount'); ?>');">
                                        <input type="hidden" name="action" value="delete_discount">
                                        <input type="hidden" name="discount_id" value="<?php echo htmlspecialchars($discount['id']); ?>">
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

