<?php
/**
 * Tools & System Section
 */
?>

<div>
    <h2><?php echo icon_tool(24); ?> Системни инструменти</h2>

    <h3>Системна информация</h3>
    <table>
        <tr>
            <td><strong>PHP версия:</strong></td>
            <td><?php echo PHP_VERSION; ?></td>
        </tr>
        <tr>
            <td><strong>CMS версия:</strong></td>
            <td><?php echo CMS_VERSION; ?></td>
        </tr>
        <tr>
            <td><strong>Среда:</strong></td>
            <td><?php echo CMS_ENV; ?></td>
        </tr>
        <tr>
            <td><strong>Сървър:</strong></td>
            <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
        </tr>
    </table>

    <h3>Статус на базата данни</h3>
    <p>Драйвър: <?php echo Database::getInstance()->getDriver(); ?></p>

    <h3>Използване на дисково пространство</h3>
    <?php
    $storageSize = 0;
    $uploadsDir = CMS_ROOT . '/uploads';
    if (is_dir($uploadsDir)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir)) as $file) {
            if ($file->isFile()) $storageSize += $file->getSize();
        }
    }
    echo '<p>Качени файлове: ' . round($storageSize / 1024 / 1024, 2) . ' MB</p>';
    ?>
</div>
