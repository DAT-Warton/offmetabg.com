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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['title'] ?? 'Page'); ?> - <?php echo htmlspecialchars(get_option('site_title', 'My CMS')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page['meta_description'] ?? ''); ?>">
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
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header h1 {
            font-size: 24px;
            color: #3498db;
        }

        nav {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        nav a {
            display: inline-block;
            margin-right: 20px;
            color: #3498db;
            text-decoration: none;
        }

        .breadcrumb {
            margin-bottom: 30px;
            font-size: 14px;
            color: #6b7280;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .page-content {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            line-height: 1.8;
        }

        .page-content h1 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 40px;
        }

        .page-content h2 {
            color: #3498db;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .page-content h3 {
            color: #2980b9;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .page-content p {
            margin-bottom: 15px;
            color: #4b5563;
        }

        .page-content img {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border-radius: 8px;
        }

        footer {
            background: #2c3e50;
            color: white;
            padding: 30px 0;
            text-align: center;
        }
    </style>
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
</body>
</html>
