<?php
/**
 * Single Page Template
 */
$page = $page ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title'] ?? 'Page'); ?> - <?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?></title>
    <meta name="description"content="<?php echo htmlspecialchars($page['meta_description'] ?? ''); ?>">
    <link rel="stylesheet"href="/assets/css/themes.min.css">
    <link rel="stylesheet"href="/assets/css/page.css">
    <?php echo get_custom_theme_css(); ?>
</head>
<body>
    <header>
        <div class="container">
            <h1><?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?></h1>
            <nav>
                <a href="/">Home</a>
                <a href="/blog">Blog</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> / <span><?php echo htmlspecialchars($page['title'] ?? 'Page'); ?></span>
        </div>

        <div class="page-content">
            <h1><?php echo htmlspecialchars($page['title'] ?? 'Page'); ?></h1>
            <div>
                <?php echo nl2br(htmlspecialchars($page['content'] ?? '')); ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2024 <?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="/assets/js/theme-manager.min.js"defer></script>
</body>
</html>
