<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); padding: 30px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .content h2 { color: #333; margin-top: 0; }
        .content p { margin: 15px 0; }
        .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; font-size: 16px; }
        .button:hover { opacity: 0.9; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
        .token-box { background: #f0f7ff; border: 2px dashed #3498db; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; }
        .token { font-size: 18px; font-weight: bold; color: #3498db; letter-spacing: 2px; }
        .warning-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Активирайте профила си</h1>
        </div>
        <div class="content">
            <h2>Здравейте, <?= htmlspecialchars($user_name) ?>!</h2>
            
            <p>Благодарим Ви за регистрацията в <strong><?= htmlspecialchars($site_name) ?></strong>!</p>
            
            <p>За да завършите процеса на регистрация и да активирате профила си, моля кликнете на бутона по-долу:</p>
            
            <center>
                <a href="<?= htmlspecialchars($activation_link) ?>" class="button">✓ Активирай профила</a>
            </center>
            
            <div class="warning-box">
                <strong>⚠️ Важно:</strong> Този линк за активация е валиден 24 часа. След това ще трябва да поискате нов.
            </div>
            
            <p>Ако бутонът не работи, копирайте и поставете следния линк в браузъра си:</p>
            
            <div class="token-box">
                <small>Линк за активация:</small><br>
                <span style="font-size: 12px; word-break: break-all;"><?= htmlspecialchars($activation_link) ?></span>
            </div>
            
            <p><strong>Защо е необходима активация?</strong><br>
            Активацията потвърждава, че имейл адресът принадлежи на Вас и помага да защитим Вашия акаунт от неоторизиран достъп.</p>
            
            <p>След активацията ще можете да:</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>✓ Пазарувате и поръчвате продукти</li>
                <li>✓ Следите статуса на поръчките си</li>
                <li>✓ Изпращате запитвания към нас</li>
                <li>✓ Управлявате профила си</li>
            </ul>
            
            <p>Ако не сте се регистрирали в <?= htmlspecialchars($site_name) ?>, моля игнорирайте този имейл.</p>
            
            <p>С поздрави,<br>
            <strong>Екипът на <?= htmlspecialchars($site_name) ?></strong></p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. Всички права запазени.</p>
            <p>Този имейл беше изпратен до <?= htmlspecialchars($user_email) ?></p>
        </div>
    </div>
</body>
</html>

