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
                <label>Избери файлове</label>
                <input type="file" name="media[]" accept="image/*" multiple required>
                <small class="hint">Можете да изберете множество файлове наведнъж. Поддържани формати: JPG, PNG, GIF, WEBP. HEIC/HEIF се конвертира, ако сървърът го поддържа.</small>
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
            $filePath = $uploadDir . '/' . $file;
            $fileSize = is_file($filePath) ? filesize($filePath) : 0;
            $fileSizeFormatted = $fileSize > 1048576 ? round($fileSize / 1048576, 2) . ' MB' : round($fileSize / 1024, 2) . ' KB';
            
            echo '<div class="media-card">';
            echo '<img src="' . htmlspecialchars($path) . '" alt="' . htmlspecialchars($file) . '">';
            echo '<p class="media-name" title="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . '</p>';
            echo '<small class="text-muted">' . $fileSizeFormatted . '</small>';
            echo '<form method="POST" style="margin-top: 10px;" onsubmit="return confirm(\'Сигурни ли сте, че искате да изтриете ' . htmlspecialchars($file, ENT_QUOTES) . '?\');">';
            echo '<input type="hidden" name="action" value="delete_media">';
            echo '<input type="hidden" name="filename" value="' . htmlspecialchars($file) . '">';
            echo '<button type="submit" class="btn-delete btn-sm">' . icon_trash(16) . ' Изтрий</button>';
            echo '</form>';
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
</div>

