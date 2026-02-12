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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f9fafb;
            color: #1f2937;
        }

        header {
            background: white;
            padding: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header h1 {
            font-size: 24px;
            color: #667eea;
        }

        nav {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        nav a {
            display: inline-block;
            margin-right: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 40px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #6b7280;
        }

        .posts-list {
            list-style: none;
        }

        .post-item {
            background: white;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }

        .post-item h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .post-item .meta {
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .post-item .category {
            display: inline-block;
            background: #f3f4f6;
            color: #667eea;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
        }

        .post-item p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .post-item a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .no-posts {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        footer {
            background: #2c3e50;
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>
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
</body>
</html>

