<?php
/**
 * Media Management Section
 */
?>

<div>
    <h2>🖼️ Медийна библиотека</h2>

    <div class="mb-20">
        <h3>Качи нова медия</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_media">
            <div class="form-group">
                <label>Избери файл</label>
                <input type="file" name="media" accept="image/*" required>
                <small class="hint">Поддържани формати: JPG, PNG, GIF, WEBP. HEIC/HEIF се конвертира, ако сървърът го поддържа.</small>
            </div>
            <button type="submit"><?php echo __('upload'); ?></button>
        </form>
    </div>

    <h3>Качени файлове</h3>
    <?php
    $uploadDir = CMS_ROOT . '/uploads';
    $files = is_dir($uploadDir) ? array_diff(scandir($uploadDir), ['.', '..']) : [];

    if (empty($files)) {
        echo '<p class="text-muted">Все още няма качени файлове.</p>';
    } else {
        echo '<div class="media-grid">';
        foreach ($files as $file) {
            $path = '/uploads/' . $file;
            echo '<div class="media-card">';
            echo '<img src="' . $path . '">';
            echo '<p class="media-name">' . htmlspecialchars($file) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
</div>

