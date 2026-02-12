<?php
/**
 * Installation Script for OffMeta E-Commerce
 * Run this once after uploading to cPanel to initialize the system
 */

define('CMS_ROOT', __DIR__);

// Create necessary directories
$directories = [
    'storage',
    'uploads',
    'logs',
    'cache',
    'config',
    'email-templates',
    'templates'
];

echo "Creating directories...\n";
foreach ($directories as $dir) {
    $path = CMS_ROOT . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✓ Created: $dir\n";
    } else {
        echo "✓ Exists: $dir\n";
    }
}

// Initialize JSON storage files
$jsonFiles = [
    'storage/options.json' => [
        'site_title' => 'OffMeta',
        'site_description' => 'E-commerce platform',
        'admin_email' => 'admin@offmeta.com',
        'currency' => 'EUR',
        'language' => 'bg',
        'timezone' => 'Europe/Sofia'
    ],
    'storage/customers.json' => [],
    'storage/products.json' => [],
    'storage/categories.json' => [],
    'storage/orders.json' => [],
    'storage/pages.json' => [
        'about' => [
            'slug' => 'about',
            'title' => 'За Нас',
            'content' => '<h2>Добре дошли в OffMeta</h2><p>Вашият онлайн магазин за уникални продукти.</p>',
            'meta_description' => 'Научете повече за OffMeta',
            'status' => 'published',
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ]
    ],
    'storage/posts.json' => [],
    'storage/discounts.json' => [],
    'storage/promotions.json' => [],
    'storage/inquiries.json' => [],
    'storage/analytics.json' => [
        'total_visits' => 0,
        'unique_visitors' => 0,
        'page_views' => 0,
        'bounce_rate' => 0,
        'sources' => [
            'direct' => 0,
            'search' => 0,
            'social' => 0,
            'referral' => 0,
            'email' => 0
        ],
        'daily_stats' => [],
        'popular_pages' => [],
        'last_updated' => ''
    ],
    'storage/financial.json' => [
        'total_expenses' => 0,
        'tax_rate' => 20,
        'expenses' => [],
        'expense_categories' => [
            'shipping' => 0,
            'packaging' => 0,
            'marketing' => 0,
            'hosting' => 0,
            'utilities' => 0,
            'salaries' => 0,
            'other' => 0
        ],
        'monthly_breakdown' => [],
        'last_updated' => ''
    ]
];

echo "\nInitializing JSON storage files...\n";
foreach ($jsonFiles as $file => $defaultData) {
    $path = CMS_ROOT . '/' . $file;
    if (!file_exists($path)) {
        file_put_contents($path, json_encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "✓ Created: $file\n";
    } else {
        // Check if file is empty or invalid
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        
        if ($data === null || $data === []) {
            // Re-initialize empty or invalid files
            file_put_contents($path, json_encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "✓ Fixed: $file (was empty/invalid)\n";
        } else {
            echo "✓ Exists: $file\n";
        }
    }
}

// Set proper permissions
echo "\nSetting permissions...\n";
$writableDirs = ['storage', 'uploads', 'logs', 'cache'];
foreach ($writableDirs as $dir) {
    $path = CMS_ROOT . '/' . $dir;
    @chmod($path, 0755);
    echo "✓ Set permissions for: $dir\n";
}

// Check PHP requirements
echo "\nChecking PHP requirements...\n";
$requirements = [
    'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'JSON Extension' => extension_loaded('json'),
    'GD Extension' => extension_loaded('gd'),
    'FileInfo Extension' => extension_loaded('fileinfo'),
    'Session Support' => function_exists('session_start')
];

$optionalRequirements = [
    'cURL Extension (recommended)' => extension_loaded('curl')
];

$allMet = true;
foreach ($requirements as $requirement => $met) {
    if ($met) {
        echo "✓ $requirement\n";
    } else {
        echo "✗ $requirement - MISSING!\n";
        $allMet = false;
    }
}

echo "\nOptional features:\n";
foreach ($optionalRequirements as $requirement => $met) {
    if ($met) {
        echo "✓ $requirement\n";
    } else {
        echo "⚠ $requirement - Will use fallback method\n";
    }
}

// Check .htaccess
echo "\nChecking configuration files...\n";
if (file_exists(CMS_ROOT . '/.htaccess')) {
    echo "✓ .htaccess exists\n";
} else {
    echo "✗ .htaccess missing - URL routing may not work!\n";
}

// Final status
echo "\n" . str_repeat('=', 60) . "\n";
if ($allMet) {
    echo "✓ Installation completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Delete this install.php file for security\n";
    echo "2. Configure email settings in config/email-config.php\n";
    echo "3. (Optional) Configure courier API in config/courier-config.php\n";
    echo "4. Access admin panel at: /admin/ with credentials:\n";
    echo "   Username: Warton\n";
    echo "   Password: Warton2026\n";
    echo "   (Change these in admin/index.php)\n\n";
    echo "5. Start adding products, categories, and pages!\n";
} else {
    echo "✗ Installation completed with errors!\n";
    echo "Please fix the missing requirements above.\n";
}
echo str_repeat('=', 60) . "\n";

// For web access, show HTML output
if (PHP_SAPI !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="bg">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>OffMeta Installation</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
            }
            h1 {
                color: #3498db;
                margin-bottom: 20px;
                text-align: center;
            }
            .success {
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                color: #166534;
                padding: 15px;
                border-radius: 6px;
                margin: 20px 0;
            }
            .info {
                background: #eff6ff;
                border: 1px solid #bfdbfe;
                color: #1e40af;
                padding: 15px;
                border-radius: 6px;
                margin: 20px 0;
            }
            .steps {
                margin: 20px 0;
            }
            .steps li {
                margin: 10px 0;
                margin-left: 20px;
            }
            .btn {
                display: block;
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                text-align: center;
                text-decoration: none;
                margin-top: 20px;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }
            code {
                background: #f3f4f6;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✓ Инсталацията завърши успешно!</h1>
            
            <div class="success">
                OffMeta е успешно инсталиран и готов за употреба!
            </div>
            
            <?php if (!extension_loaded('curl')): ?>
            <div class="info" style="background: #fef3c7; border-color: #fde047; color: #854d0e;">
                <strong>⚠️ Забележка:</strong> cURL extension не е налично. Email системата ще използва <code>file_get_contents</code> fallback метод. За по-добра производителност, помолете хостинг провайдъра да активира cURL.
            </div>
            <?php endif; ?>
            
            <div class="info">
                <strong>Следващи стъпки:</strong>
                <ol class="steps">
                    <li>Изтрийте файла <code>install.php</code> за сигурност</li>
                    <li>Конфигурирайте email в <code>config/email-config.php</code></li>
                    <li>(Опционално) Конфигурирайте куриерски API в <code>config/courier-config.php</code></li>
                    <li>Влезте в админ панела с:
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>Потребител: <code>Warton</code></li>
                            <li>Парола: <code>Warton2026</code></li>
                        </ul>
                    </li>
                    <li>Започнете да добавяте продукти и категории!</li>
                </ol>
            </div>
            
            <a href="admin/" class="btn">Влез в Админ Панела</a>
            <a href="index.php" class="btn" style="background: linear-gradient(135deg, #27ae60 0%, #059669 100%); margin-top: 10px;">Виж Сайта</a>
        </div>
    </body>
    </html>
    <?php
}
?>

