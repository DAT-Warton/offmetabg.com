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
                <input type="text" name="code" value="<?php echo htmlspecialchars($editDiscount['code'] ?? ''); ?>" required style="text-transform: uppercase; font-weight: 600;">
                <small style="color: var(--text-secondary, #666);">Промо код (напр. SUMMER2026, WELCOME10)</small>
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
                <small id="value_hint" style="color: var(--text-secondary, #666);"></small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label><?php echo __('admin.min_purchase'); ?></label>
                    <input type="number" name="min_purchase" value="<?php echo htmlspecialchars($editDiscount['min_purchase'] ?? '0'); ?>" step="0.01" min="0">
                    <small style="color: var(--text-secondary, #666);">Минимална поръчка (€)</small>
                </div>

                <div class="form-group">
                    <label><?php echo __('admin.max_uses'); ?></label>
                    <input type="number" name="max_uses" value="<?php echo htmlspecialchars($editDiscount['max_uses'] ?? ''); ?>" min="0">
                    <small style="color: var(--text-secondary, #666);">0 за неограничено</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
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
                <label>
                    <input type="checkbox" name="active" value="1" <?php echo ($editDiscount['active'] ?? true) ? 'checked' : ''; ?>>
                    <?php echo __('admin.discount_active'); ?>
                </label>
            </div>

            <div class="form-group">
                <label>
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
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary, #666);">
                            <?php echo icon_percent(32); ?><br>
                            <?php echo __('admin.no_discounts'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td>
                                <code style="background: var(--bg-primary, #f5f7fa); padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 14px;">
                                    <?php echo htmlspecialchars($discount['code']); ?>
                                </code>
                                <?php if (!empty($discount['description'])): ?>
                                    <br><small style="color: var(--text-secondary, #666);"><?php echo htmlspecialchars($discount['description']); ?></small>
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
                                ?>
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: <?php echo $type['color']; ?>15; color: <?php echo $type['color']; ?>; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid <?php echo $type['color']; ?>30;">
                                    <?php echo $type['icon']; ?> <?php echo $type['label']; ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--primary, #3498db); font-size: 16px;">
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
                                <span style="font-size: 13px;">
                                    <?php echo $used; ?> / <?php echo $max > 0 ? $max : '∞'; ?>
                                </span>
                            </td>
                            <td style="font-size: 13px; color: var(--text-secondary, #666);">
                                <?php if (!empty($discount['start_date']) && !empty($discount['end_date'])): ?>
                                    <?php echo date('d.m.Y', strtotime($discount['start_date'])); ?><br><?php echo date('d.m.Y', strtotime($discount['end_date'])); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary, #666);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($discount['active'] ?? true): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #27ae6015; color: #27ae60; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid #27ae6030;">
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
                                    <a href="?section=discounts&action=edit&id=<?php echo urlencode($discount['id']); ?>" class="btn-small" title="Редактирай">
                                        ✏️ Редактирай
                                    </a>
                                    <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('<?php echo __('admin.confirm_delete_discount'); ?>');">
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

