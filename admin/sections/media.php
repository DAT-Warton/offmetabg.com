<?php
/**
 * Media Management Section
 */
?>

<div>
    <h2>🖼️ Медийна библиотека</h2>

    <div style="margin-bottom: 20px;">
        <h3>Качи нова медия</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_media">
            <div class="form-group">
                <label>Избери файл</label>
                <input type="file" name="media" accept="image/*" required>
            </div>
            <button type="submit"><?php echo __('upload'); ?></button>
        </form>
    </div>

    <h3>Качени файлове</h3>
    <?php
    $uploadDir = CMS_ROOT . '/uploads';
    $files = is_dir($uploadDir) ? array_diff(scandir($uploadDir), ['.', '..']) : [];

    if (empty($files)) {
        echo '<p style="color: var(--text-secondary, #666);">Все още няма качени файлове.</p>';
    } else {
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">';
        foreach ($files as $file) {
            $path = '/uploads/' . $file;
            echo '<div style="border: 1px solid var(--border-color, #ddd); border-radius: 6px; padding: 10px; text-align: center; background: var(--bg-secondary, white);">';
            echo '<img src="' . $path . '" style="max-width: 100%; max-height: 100px; margin-bottom: 10px; border-radius: 4px;">';
            echo '<p style="font-size: 12px; word-break: break-all; color: var(--text-primary, #333); margin: 0;">' . htmlspecialchars($file) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
</div>

