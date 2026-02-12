<?php
/**
 * Blog Posts Management Section
 */
$posts = get_posts();
$editSlug = $_GET['edit'] ?? null;
$editPost = $editSlug ? get_post($editSlug) : null;
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>📝 Блог статии</h2>
        <a href="?section=posts&action=new" style="padding: 10px 20px; background: var(--primary, #667eea); color: white; text-decoration: none; border-radius: 6px;">+ Нова статия</a>
    </div>

    <?php if ($editPost || $_GET['action'] === 'new'): ?>
        <form method="POST" style="margin-bottom: 30px;">
            <input type="hidden" name="action" value="save_post">
            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editSlug); ?>">

            <div class="form-group">
                <label>Заглавие на статията</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($editPost['title'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Съдържание</label>
                <textarea name="content" required><?php echo htmlspecialchars($editPost['content'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Кратък опис</label>
                <textarea name="excerpt" style="min-height: 80px;"><?php echo htmlspecialchars($editPost['excerpt'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label><?php echo __('product.category'); ?></label>
                <input type="text" name="category" value="<?php echo htmlspecialchars($editPost['category'] ?? 'uncategorized'); ?>">
            </div>

            <div class="form-group">
                <label>Мета описание</label>
                <input type="text" name="meta_description" value="<?php echo htmlspecialchars($editPost['meta_description'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label><?php echo __('product.status'); ?></label>
                <select name="status">
                    <option value="published" <?php echo ($editPost['status'] ?? '') === 'published' ? 'selected' : ''; ?>><?php echo __('product.published'); ?></option>
                    <option value="draft" <?php echo ($editPost['status'] ?? '') === 'draft' ? 'selected' : ''; ?>><?php echo __('product.draft'); ?></option>
                </select>
            </div>

            <button type="submit"><?php echo __('save'); ?> статия</button>
        </form>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Заглавие</th>
                <th><?php echo __('product.category'); ?></th>
                <th><?php echo __('product.status'); ?></th>
                <th>Дата</th>
                <th><?php echo __('users.actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $slug => $post): ?>
                <tr>
                    <td><?php echo htmlspecialchars($post['title']); ?></td>
                    <td><?php echo htmlspecialchars($post['category']); ?></td>
                    <td><?php echo ucfirst($post['status']); ?></td>
                    <td><?php echo substr($post['created'], 0, 10); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?section=posts&edit=<?php echo $slug; ?>" class="btn-small" style="padding: 6px 12px; background: var(--primary, #667eea); color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;"><?php echo __('edit'); ?></a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="slug" value="<?php echo $slug; ?>">
                                <button type="submit" class="btn-small btn-delete" onclick="return confirm('Изтрий тази статия?');"><?php echo __('delete'); ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
