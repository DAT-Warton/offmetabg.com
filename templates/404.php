<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title>404 - Страницата не е намерена | <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></title>
    <link rel="icon"type="image/svg+xml"href="/favicon.svg">
    <link rel="icon"type="image/x-icon"href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/themes.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <h1 class="error-code">404</h1>
            <h2 class="error-title">Страницата не е намерена</h2>
            <p class="error-message">Съжаляваме, но търсената от вас страница не съществува.</p>
            <div class="error-actions">
                <a href="/"class="btn btn-primary">Начална страница</a>
                <a href="javascript:history.back()"class="btn btn-secondary">Назад</a>
            </div>
        </div>
    </div>
    
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .error-content {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #667eea;
            margin: 0;
            line-height: 1;
        }
        
        .error-title {
            font-size: 1.75rem;
            color: #333;
            margin: 1rem 0;
        }
        
        .error-message {
            color: #666;
            margin: 1rem 0 2rem;
            font-size: 1.1rem;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e0e7ff;
            color: #667eea;
        }
        
        .btn-secondary:hover {
            background: #c7d2fe;
        }
    </style>
    
    <script src="/assets/js/theme-manager.min.js"defer></script>
</body>
</html>
