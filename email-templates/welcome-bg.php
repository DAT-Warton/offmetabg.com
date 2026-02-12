<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .content h2 { color: #333; margin-top: 0; }
        .content p { margin: 15px 0; }
        .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .button:hover { opacity: 0.9; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
        .feature-box { background: #f0f7ff; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Добре дошли!</h1>
        </div>
        <div class="content">
            <h2>Здравейте, <?= htmlspecialchars($user_name) ?>!</h2>
            
            <p>Радваме се, че се присъединихте към <strong><?= htmlspecialchars($site_name) ?></strong>!</p>
            
            <p>Вашият акаунт беше успешно създаден и вече можете да започнете да пазарувате.</p>
            
            <div class="feature-box">
                <strong>Какво можете да правите сега:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Преглеждайте нашите продукти</li>
                    <li>Добавяйте артикули в количката</li>
                    <li>Следете вашите поръчки</li>
                    <li>Управлявайте профила си</li>
                </ul>
            </div>
            
            <center>
                <a href="<?= htmlspecialchars($site_url) ?>" class="button">Започнете пазаруването</a>
            </center>
            
            <p>Ако имате въпроси или нужда от помощ, не се колебайте да се свържете с нас.</p>
            
            <p>Благодарим ви, че избрахте <?= htmlspecialchars($site_name) ?>!</p>
            
            <p>С поздрави,<br>
            Екипът на <?= htmlspecialchars($site_name) ?></p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. Всички права запазени.</p>
            <p>Този имейл беше изпратен автоматично. Моля, не отговаряйте.</p>
        </div>
    </div>
</body>
</html>
