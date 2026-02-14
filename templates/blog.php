<?php
/**
 * Blog Listing Template
 */
$posts = get_posts();
$publishedPosts = array_filter($posts, function($p) {
    return ($p['status'] ?? 'published') === 'published';
});
arsort($publishedPosts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - <?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?></title>
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/blog.css">
</head>
<body data-theme="<?php echo htmlspecialchars(db_get_option('active_theme', 'default')); ?>">
    <header>
        <div class="container">
            <h1><?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?></h1>
            <nav>
                <a href="/">Home</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <h1>Blog</h1>
            <p>Read our latest articles and updates</p>
        </div>

        <?php if (empty($publishedPosts)): ?>
            <div class="no-posts">
                <p>No blog posts yet. Check back soon!</p>
            </div>
        <?php else: ?>
            <ul class="posts-list">
                <?php foreach ($publishedPosts as $slug => $post): ?>
                    <li class="post-item">
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <div class="meta">
                            <?php echo substr($post['created'], 0, 10); ?>
                            <span class="category"><?php echo htmlspecialchars($post['category']); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($post['excerpt'] ?? substr($post['content'], 0, 200)) . '...'; ?></p>
                        <a href="/blog/<?php echo htmlspecialchars($slug); ?>">Read More →</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2024 <?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="/assets/js/theme-manager.js"></script>
</body>
</html>

