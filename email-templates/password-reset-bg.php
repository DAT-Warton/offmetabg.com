<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); padding: 30px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .content h2 { color: #333; margin-top: 0; }
        .content p { margin: 15px 0; }
        .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .button:hover { opacity: 0.9; }
        .token-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 14px; margin: 15px 0; word-break: break-all; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($site_name) ?></h1>
        </div>
        <div class="content">
            <h2>Здравейте, <?= htmlspecialchars($user_name) ?>!</h2>
            
            <p>Получихме заявка за нулиране на паролата за вашия акаунт.</p>
            
            <p>Ако сте направили тази заявка, моля кликнете на бутона по-долу, за да създадете нова парола:</p>
            
            <center>
                <a href="<?= htmlspecialchars($reset_link) ?>"class="button">Нулиране на паролата</a>
            </center>
            
            <p>Или копирайте и поставете този линк в браузъра си:</p>
            <div class="token-box"><?= htmlspecialchars($reset_link) ?></div>
            
            <div class="warning">
                <strong>⚠️ Важно:</strong> Този линк е валиден за 1 час. След това ще трябва да поискате нов линк за нулиране.
            </div>
            
            <p><strong>Ако не сте заявили нулиране на паролата,</strong> моля игнорирайте този имейл. Вашата парола ще остане непроменена и акаунтът ви е сигурен.</p>
            
            <p>За вашата сигурност, никога не споделяйте този линк с други хора.</p>
            
            <p>Поздрави,<br>
            Екипът на <?= htmlspecialchars($site_name) ?></p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. Всички права запазени.</p>
            <p>Този имейл беше изпратен автоматично. Моля, не отговаряйте.</p>
        </div>
    </div>
</body>
</html>

