<?php
/**
 * Pages Management Section
 */
$pages = get_pages();
$editSlug = $_GET['edit'] ?? null;
$editPage = $editSlug ? get_page($editSlug) : null;
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><?php echo icon_home(24); ?> Управление на страници</h2>
        <a href="?section=pages&action=new" style="padding: 10px 20px; background: var(--primary, #667eea); color: white; text-decoration: none; border-radius: 6px;">+ Нова страница</a>
    </div>

    <?php if ($editPage || $_GET['action'] === 'new'): ?>
        <form method="POST" style="margin-bottom: 30px;">
            <input type="hidden" name="action" value="save_page">
            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($editSlug); ?>">

            <div class="form-group">
                <label>Заглавие на страницата</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($editPage['title'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Съдържание</label>
                <textarea name="content" required><?php echo htmlspecialchars($editPage['content'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Мета описание</label>
                <input type="text" name="meta_description" value="<?php echo htmlspecialchars($editPage['meta_description'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label><?php echo __('product.status'); ?></label>
                <select name="status">
                    <option value="published" <?php echo ($editPage['status'] ?? '') === 'published' ? 'selected' : ''; ?>><?php echo __('product.published'); ?></option>
                    <option value="draft" <?php echo ($editPage['status'] ?? '') === 'draft' ? 'selected' : ''; ?>><?php echo __('product.draft'); ?></option>
                </select>
            </div>

            <button type="submit"><?php echo __('save'); ?> страница</button>
        </form>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Заглавие</th>
                <th><?php echo __('product.status'); ?></th>
                <th>Актуализирана</th>
                <th><?php echo __('users.actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $slug => $page): ?>
                <tr>
                    <td><?php echo htmlspecialchars($page['title']); ?></td>
                    <td><?php echo ucfirst($page['status']); ?></td>
                    <td><?php echo $page['updated']; ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?section=pages&edit=<?php echo $slug; ?>" class="btn-small" style="padding: 6px 12px; background: var(--primary, #667eea); color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;"><?php echo __('edit'); ?></a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_page">
                                <input type="hidden" name="slug" value="<?php echo $slug; ?>">
                                <button type="submit" class="btn-small btn-delete" onclick="return confirm('Изтрий тази страница?');"><?php echo __('delete'); ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
