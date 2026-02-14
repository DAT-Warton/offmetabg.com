<?php
/**
 * Products Management Section
 */
$products = get_products_data();
$action = $_GET['action'] ?? '';
$editId = $_GET['edit'] ?? null;
$editProduct = $editId ? ($products[$editId] ?? null) : null;
?>

<div>
    <div class="section-header">
        <h2 class="section-title">🛍️ <?php echo __('admin.manage_products'); ?></h2>
        <a href="?section=products&action=new" class="btn">+ <?php echo __('admin.new_product'); ?></a>
    </div>

    <?php if ($editProduct || $action === 'new'): ?>
        <form method="POST" enctype="multipart/form-data" class="card card-lg form-card">
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
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($editProduct['image']); ?>" alt="Current image">
                        <small class="hint">Current: <?php echo htmlspecialchars($editProduct['image']); ?></small>
                    </div>
                <?php endif; ?>
                
                <!-- Upload New Image -->
                <input type="file" name="product_image" accept="image/*" class="file-input">
                <small class="hint"><?php echo __('admin.image_upload_hint'); ?> (JPG, PNG, GIF, max 5MB)</small>
                
                <!-- OR use URL -->
                <div class="mt-15">
                    <label class="text-sm text-muted">Or enter image URL:</label>
                    <input type="text" name="image" value="<?php echo htmlspecialchars($editProduct['image'] ?? ''); ?>" placeholder="/uploads/product.jpg or https://...">
                    <small class="hint"><?php echo __('admin.image_url_hint'); ?></small>
                </div>
            </div>

            <div class="form-group">
                <label><?php echo __('product.category'); ?></label>
                <?php
                $categories = get_categories_data();
                $currentCategory = $editProduct['category'] ?? '';
                ?>
                <?php if (empty($categories)): ?>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($currentCategory); ?>" placeholder="general">
                    <small class="text-warning">
                        ⚠️ Няма създадени категории. <a href="?section=categories&action=new" class="link-primary">Създайте категория</a> или въведете име ръчно.
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
                    <small class="hint">
                        Изберете категория или <a href="?section=categories&action=new" class="link-primary">създайте нова</a>
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

            <h3 class="section-subtitle">📹 <?php echo __('admin.product_videos'); ?></h3>
            <p class="text-sm text-muted mb-20"><?php echo __('admin.product_videos_hint'); ?></p>

            <div class="form-group">
                <label><?php echo __('admin.youtube_url'); ?></label>
                <input type="url" name="video_youtube" value="<?php echo htmlspecialchars($editProduct['videos']['youtube'] ?? ''); ?>" placeholder="https://www.youtube.com/watch?v=xxxxx or https://youtu.be/xxxxx">
                <small class="hint"><?php echo __('admin.youtube_hint'); ?></small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.tiktok_url'); ?></label>
                <input type="url" name="video_tiktok" value="<?php echo htmlspecialchars($editProduct['videos']['tiktok'] ?? ''); ?>" placeholder="https://www.tiktok.com/@username/video/xxxxx">
                <small class="hint"><?php echo __('admin.tiktok_hint'); ?></small>
            </div>

            <div class="form-group">
                <label><?php echo __('admin.instagram_url'); ?></label>
                <input type="url" name="video_instagram" value="<?php echo htmlspecialchars($editProduct['videos']['instagram'] ?? ''); ?>" placeholder="https://www.instagram.com/p/xxxxx/ or https://www.instagram.com/reel/xxxxx/">
                <small class="hint"><?php echo __('admin.instagram_hint'); ?></small>
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
                    <td colspan="5" class="table-empty">
                        <?php echo icon_box(32); ?><br>
                        <?php echo __('admin.no_products_yet'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $id => $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>€<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock'] ?? 0; ?></td>
                        <td><?php echo ucfirst($product['status'] ?? 'published'); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="?section=products&edit=<?php echo $id; ?>" class="btn-small"><?php echo __('edit'); ?></a>
                                <form method="POST" class="inline-form">
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

