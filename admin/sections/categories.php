<?php
/**
 * Categories Management Section
 */

$categories = load_json('storage/categories.json');
$editCategory = null;

// Handle edit mode
if ($action === 'edit' && isset($_GET['id'])) {
    $editId = $_GET['id'];
    foreach ($categories as $cat) {
        if ($cat['id'] === $editId) {
            $editCategory = $cat;
            break;
        }
    }
}
?>

<div class="section-header">
    <h2><?php echo icon_folder(24); ?> <?php echo __('admin.categories'); ?></h2>
    <?php if ($action !== 'new' && $action !== 'edit'): ?>
        <a href="?section=categories&action=new" class="btn"><?php echo icon_folder(18); ?> <?php echo __('admin.add_category'); ?></a>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <!-- Create/Edit Category Form -->
    <div class="card">
        <h3><?php echo $action === 'edit' ? __('admin.edit_category') : __('admin.add_category'); ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($editCategory['id'] ?? ''); ?>">
            
            <div class="form-group">
                <label><?php echo __('admin.category_name'); ?></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.category_slug'); ?></label>
                <input type="text" name="slug" value="<?php echo htmlspecialchars($editCategory['slug'] ?? ''); ?>" required>
                <small style="color: var(--text-secondary, #666);">URL-friendly идентификатор (напр. electronics, clothing)</small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.category_description'); ?></label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.parent_category'); ?></label>
                <select name="parent_id">
                    <option value=""><?php echo __('admin.no_parent'); ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['id'] !== ($editCategory['id'] ?? '')): ?>
                            <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($editCategory['parent_id'] ?? '') === $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-secondary, #666);">Изберете родителска категория за подкатегории</small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.category_icon'); ?></label>
                <input type="text" name="icon" value="<?php echo htmlspecialchars($editCategory['icon'] ?? ''); ?>" placeholder="📱">
                <small style="color: var(--text-secondary, #666);">Emoji или HTML код</small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.display_order'); ?></label>
                <input type="number" name="order" value="<?php echo htmlspecialchars($editCategory['order'] ?? '0'); ?>" min="0">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="active" value="1" <?php echo ($editCategory['active'] ?? true) ? 'checked' : ''; ?>>
                    <?php echo __('admin.category_active'); ?>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn"><?php echo icon_check(18); ?> Запази</button>
                <a href="?section=categories" class="btn-secondary"><?php echo icon_x(18); ?> Отказ</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Categories List -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th><?php echo __('admin.category_name'); ?></th>
                    <th><?php echo __('admin.category_slug'); ?></th>
                    <th><?php echo __('admin.parent_category'); ?></th>
                    <th><?php echo __('admin.products_count'); ?></th>
                    <th><?php echo __('admin.status'); ?></th>
                    <th><?php echo __('admin.actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary, #666);">
                            <?php echo icon_folder(32); ?><br>
                            <?php echo __('admin.no_categories'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <?php if (!empty($category['icon'])): ?>
                                    <span style="margin-right: 8px;"><?php echo $category['icon']; ?></span>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                            </td>
                            <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                            <td>
                                <?php 
                                if (!empty($category['parent_id'])) {
                                    foreach ($categories as $parent) {
                                        if ($parent['id'] === $category['parent_id']) {
                                            echo htmlspecialchars($parent['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '<span style="color: var(--text-secondary, #666);">—</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <span style="background: var(--bg-primary, #f5f7fa); padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                    <?php echo $category['product_count'] ?? 0; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($category['active'] ?? true): ?>
                                    <span style="color: #27ae60; font-weight: 600;"><?php echo icon_check_circle(16); ?> <?php echo __('admin.active'); ?></span>
                                <?php else: ?>
                                    <span style="color: #6b7280;"><?php echo icon_x_circle(16); ?> <?php echo __('admin.inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?section=categories&action=edit&id=<?php echo urlencode($category['id']); ?>" 
                                   style="padding: 6px 12px; background: var(--primary, #3498db); color: white; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 13px; display: inline-block; transition: all 0.2s;">
                                    <?php echo __('admin.edit'); ?>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo __('admin.confirm_delete_category'); ?>');">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <button type="submit" class="btn-delete"><?php echo __('admin.delete'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

