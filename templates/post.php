<?php
/**
 * Single Blog Post Template
 */
$post = $post ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title'] ?? 'Post'); ?> - <?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?></title>
    <meta name="description"content="<?php echo htmlspecialchars($post['meta_description'] ?? ''); ?>">
    
    <!-- Favicon -->
    <link rel="icon"type="image/svg+xml"href="/favicon.svg">
    <link rel="icon"type="image/x-icon"href="/favicon.ico">
    
    <link rel="stylesheet" href="/assets/css/themes.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>">
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
            <a href="/">Home</a> / <a href="/blog">Blog</a> / <span><?php echo htmlspecialchars($post['title'] ?? 'Post'); ?></span>
        </div>

        <div class="post-header">
            <h1><?php echo htmlspecialchars($post['title'] ?? 'Untitled Post'); ?></h1>
            <div class="post-meta">
                <span><?php echo substr($post['created'], 0, 10); ?></span>
                <span class="category"><?php echo htmlspecialchars($post['category'] ?? 'Uncategorized'); ?></span>
            </div>
        </div>

        <div class="post-content">
            <?php echo nl2br(htmlspecialchars($post['content'] ?? '')); ?>
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
