<?php
/**
 * Products Management Section
 */
$products = load_json('storage/products.json');
$editId = $_GET['edit'] ?? null;
$editProduct = $editId ? ($products[$editId] ?? null) : null;
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--text-primary, #1f2937);">🛍️ <?php echo __('admin.manage_products'); ?></h2>
        <a href="?section=products&action=new" style="padding: 10px 20px; background: var(--primary, #667eea); color: white; text-decoration: none; border-radius: 6px;">+ <?php echo __('admin.new_product'); ?></a>
    </div>

    <?php if ($editProduct || $_GET['action'] === 'new'): ?>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px; background: var(--bg-secondary, white); padding: 25px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($editId ?? ''); ?>">

            <div class="form-group">
                <label><?php echo __('admin.product_name'); ?></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label><?php echo __('product.description'); ?></label>
                <textarea name="description" required><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label><?php echo __('product.price'); ?> ($)</label>
                <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($editProduct['price'] ?? '0.00'); ?>" required>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.image_url'); ?> (<?php echo __('admin.optional'); ?>)</label>
                
                <!-- Current Image Preview -->
                <?php if (!empty($editProduct['image'])): ?>
                    <div style="margin-bottom: 10px; padding: 10px; background: var(--bg-primary, #f5f7fa); border-radius: 6px; border: 1px solid var(--border-color, #e0e0e0);">
                        <img src="<?php echo htmlspecialchars($editProduct['image']); ?>" style="max-width: 100%; max-height: 300px; width: auto; height: auto; object-fit: contain; border-radius: 4px; display: block; margin-bottom: 5px;" alt="Current image">
                        <small style="color: var(--text-secondary, #666);">Current: <?php echo htmlspecialchars($editProduct['image']); ?></small>
                    </div>
                <?php endif; ?>
                
                <!-- Upload New Image -->
                <input type="file" name="product_image" accept="image/*" style="margin-bottom: 10px; padding: 8px; border: 1px solid var(--border-color, #ddd); border-radius: 6px; background: var(--bg-secondary, white); color: var(--text-primary, #333); width: 100%;">
                <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);"><?php echo __('admin.image_upload_hint'); ?> (JPG, PNG, GIF, max 5MB)</small>
                
                <!-- OR use URL -->
                <div style="margin-top: 15px;">
                    <label style="font-size: 14px; color: var(--text-secondary, #666);">Or enter image URL:</label>
                    <input type="text" name="image" value="<?php echo htmlspecialchars($editProduct['image'] ?? ''); ?>" placeholder="/uploads/product.jpg or https://...">
                    <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);"><?php echo __('admin.image_url_hint'); ?></small>
                </div>
            </div>

            <div class="form-group">
                <label><?php echo __('product.category'); ?></label>
                <?php
                $categories = load_json('storage/categories.json');
                $currentCategory = $editProduct['category'] ?? '';
                ?>
                <?php if (empty($categories)): ?>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($currentCategory); ?>" placeholder="general">
                    <small style="display: block; margin-top: 5px; color: #f59e0b;">
                        ⚠️ Няма създадени категории. <a href="?section=categories&action=new" style="color: #667eea; font-weight: 600;">Създайте категория</a> или въведете име ръчно.
                    </small>
                <?php else: ?>
                    <select name="category" required>
                        <option value="">-- Изберете категория --</option>
                        <?php foreach ($categories as $cat): ?>
                            <?php if ($cat['active'] ?? true): ?>
                                <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo $currentCategory === $cat['slug'] ? 'selected' : ''; ?>>
                                    <?php echo !empty($cat['icon']) ? $cat['icon'] . ' ' : ''; ?>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 5px; color: var(--text-secondary, #666);">
                        Изберете категория или <a href="?section=categories&action=new" style="color: #667eea; font-weight: 600;">създайте нова</a>
                    </small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.stock_quantity'); ?></label>
                <input type="number" name="stock" min="0" value="<?php echo htmlspecialchars($editProduct['stock'] ?? '0'); ?>" required>
            </div>

            <div class="form-group">
                <label><?php echo __('product.status'); ?></label>
                <select name="status">
                    <option value="published" <?php echo ($editProduct['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>><?php echo __('product.published'); ?></option>
                    <option value="draft" <?php echo ($editProduct['status'] ?? '') === 'draft' ? 'selected' : ''; ?>><?php echo __('product.draft'); ?></option>
                    <option value="out_of_stock" <?php echo ($editProduct['status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>><?php echo __('admin.out_of_stock'); ?></option>
                </select>
            </div>

            <h3 style="margin-top: 30px; margin-bottom: 15px; color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px;">📹 <?php echo __('admin.product_videos'); ?></h3>
            <p style="color: #666; margin-bottom: 20px; font-size: 14px;"><?php echo __('admin.product_videos_hint'); ?></p>

            <div class="form-group">
                <label><?php echo __('admin.youtube_url'); ?></label>
                <input type="url" name="video_youtube" value="<?php echo htmlspecialchars($editProduct['videos']['youtube'] ?? ''); ?>" placeholder="https://www.youtube.com/watch?v=xxxxx or https://youtu.be/xxxxx">
                <small style="display: block; margin-top: 5px; color: #666;"><?php echo __('admin.youtube_hint'); ?></small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.tiktok_url'); ?></label>
                <input type="url" name="video_tiktok" value="<?php echo htmlspecialchars($editProduct['videos']['tiktok'] ?? ''); ?>" placeholder="https://www.tiktok.com/@username/video/xxxxx">
                <small style="display: block; margin-top: 5px; color: #666;"><?php echo __('admin.tiktok_hint'); ?></small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.instagram_url'); ?></label>
                <input type="url" name="video_instagram" value="<?php echo htmlspecialchars($editProduct['videos']['instagram'] ?? ''); ?>" placeholder="https://www.instagram.com/p/xxxxx/ or https://www.instagram.com/reel/xxxxx/">
                <small style="display: block; margin-top: 5px; color: #666;"><?php echo __('admin.instagram_hint'); ?></small>
            </div>

            <button type="submit"><?php echo __('admin.save_product'); ?></button>
        </form>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th><?php echo __('admin.product_name'); ?></th>
                <th><?php echo __('product.price'); ?></th>
                <th><?php echo __('product.stock'); ?></th>
                <th><?php echo __('product.status'); ?></th>
                <th><?php echo __('users.actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <?php echo icon_box(32); ?><br>
                        <?php echo __('admin.no_products_yet'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $id => $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock'] ?? 0; ?></td>
                        <td><?php echo ucfirst($product['status'] ?? 'published'); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="?section=products&edit=<?php echo $id; ?>" class="btn-small" style="padding: 6px 12px; background: var(--primary, #667eea); color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;"><?php echo __('edit'); ?></a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                    <button type="submit" class="btn-small btn-delete" onclick="return confirm('Изтрий този продукт?');"><?php echo __('delete'); ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
