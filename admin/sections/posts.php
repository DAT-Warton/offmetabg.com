<?php
/**
 * Blog Posts Management Section
 */
$posts = get_posts();
$action = $_GET['action'] ?? '';
$editSlug = $_GET['edit'] ?? null;
$editPost = $editSlug ? get_post($editSlug) : null;
?>

<div>
    <div class="section-header">
        <h2 class="section-title">📝 Блог статии</h2>
        <a href="?section=posts&action=new"class="btn">+ Нова статия</a>
    </div>

    <?php if ($editPost || $action === 'new'): ?>
        <form method="POST"class="card form-card">
            <input type="hidden"name="action"value="save_post">
            <input type="hidden"name="slug"value="<?php echo htmlspecialchars($editSlug); ?>">

            <div class="form-group">
                <label>Заглавие на статията</label>
                <input type="text"name="title"value="<?php echo htmlspecialchars($editPost['title'] ?? ''); ?>"required>
            </div>

            <div class="form-group">
                <label>Съдържание</label>
                <textarea name="content"required><?php echo htmlspecialchars($editPost['content'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Кратък опис</label>
                <textarea name="excerpt"class="textarea-sm"><?php echo htmlspecialchars($editPost['excerpt'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label><?php echo __('product.category'); ?></label>
                <input type="text"name="category"value="<?php echo htmlspecialchars($editPost['category'] ?? 'uncategorized'); ?>">
            </div>

            <div class="form-group">
                <label>Мета описание</label>
                <input type="text"name="meta_description"value="<?php echo htmlspecialchars($editPost['meta_description'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label><?php echo __('product.status'); ?></label>
                <select name="status">
                    <option value="published"<?php echo ($editPost['status'] ?? '') === 'published' ? 'selected' : ''; ?>><?php echo __('product.published'); ?></option>
                    <option value="draft"<?php echo ($editPost['status'] ?? '') === 'draft' ? 'selected' : ''; ?>><?php echo __('product.draft'); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label>Дата и час (DD/MM/YYYY HH:MM)</label>
                <?php 
                    $created_dt = '';
                    if (isset($editPost['created']) && !empty($editPost['created'])) {
                        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $editPost['created']);
                        if ($dt) {
                            $created_dt = $dt->format('d/m/Y H:i');
                        }
                    }
                ?>
                <input type="text"name="created_datetime"value="<?php echo htmlspecialchars($created_dt); ?>"placeholder="16/02/2026 14:30"pattern="\d{2}/\d{2}/\d{4} \d{2}:\d{2}">
                <small style="color: var(--text-muted); font-size: 0.85em;">Формат: ДД/ММ/ГГГГ ЧЧ:ММ (напр. 16/02/2026 14:30)</small>
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
                    <td>
                        <?php 
                            if (!empty($post['created'])) {
                                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $post['created']);
                                echo $dt ? $dt->format('d/m/Y H:i') : substr($post['created'], 0, 10);
                            }
                        ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?section=posts&edit=<?php echo $slug; ?>"class="btn-small"><?php echo __('edit'); ?></a>
                            <form method="POST"class="inline-form">
                                <input type="hidden"name="action"value="delete_post">
                                <input type="hidden"name="slug"value="<?php echo $slug; ?>">
                                <button type="submit"class="btn-small btn-delete"onclick="return confirm('Изтрий тази статия?');"><?php echo __('delete'); ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

