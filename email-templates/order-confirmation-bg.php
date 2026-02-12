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
        .order-box { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .order-items { margin: 15px 0; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .order-item:last-child { border-bottom: none; }
        .order-total { background: #f0f7ff; padding: 15px; margin-top: 15px; border-radius: 5px; font-size: 18px; font-weight: bold; text-align: right; }
        .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
        .button:hover { opacity: 0.9; }
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Потвърждение за поръчка</h1>
        </div>
        <div class="content">
            <h2>Здравейте, <?= htmlspecialchars($user_name) ?>!</h2>
            
            <p>Благодарим ви за вашата поръчка! Вашата поръчка беше успешно приета и обработва се.</p>
            
            <div class="order-box">
                <p><strong>Номер на поръчка:</strong> #<?= htmlspecialchars($order_id) ?></p>
                <p><strong>Дата:</strong> <?= htmlspecialchars(date('d.m.Y H:i', strtotime($order_date))) ?></p>
                
                <div class="order-items">
                    <h3>Поръчани артикули:</h3>
                    <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div>
                            <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                            <small>Количество: <?= htmlspecialchars($item['quantity']) ?></small>
                        </div>
                        <div>
                            <?= number_format($item['price'] * $item['quantity'], 2) ?> лв.
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-total">
                    Обща сума: <?= number_format($order_total, 2) ?> лв.
                </div>
            </div>
            
            <p><strong>Какво следва?</strong></p>
            <ul>
                <li>Ще получите имейл, когато поръчката ви бъде изпратена</li>
                <li>Можете да следите статуса на поръчката си в профила си</li>
                <li>Доставката обикновено отнема 2-5 работни дни</li>
            </ul>
            
            <center>
                <a href="<?= htmlspecialchars($site_url) ?>/orders.php" class="button">Преглед на поръчката</a>
            </center>
            
            <p>Ако имате въпроси относно поръчката си, моля свържете се с нас.</p>
            
            <p>Благодарим ви, че пазарувате при нас!</p>
            
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

