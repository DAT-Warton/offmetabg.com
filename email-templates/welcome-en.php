<!DOCTYPE html>
<html lang="en">
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
        .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
        .feature-box { background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome!</h1>
        </div>
        <div class="content">
            <h2>Hello, <?= htmlspecialchars($user_name) ?>!</h2>
            
            <p>We're excited to have you join <strong><?= htmlspecialchars($site_name) ?></strong>!</p>
            
            <p>Your account has been successfully created and you can now start shopping.</p>
            
            <div class="feature-box">
                <strong>What you can do now:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Browse our products</li>
                    <li>Add items to your cart</li>
                    <li>Track your orders</li>
                    <li>Manage your profile</li>
                </ul>
            </div>
            
            <center>
                <a href="<?= htmlspecialchars($site_url) ?>"class="button">Start Shopping</a>
            </center>
            
            <p>If you have any questions or need assistance, don't hesitate to contact us.</p>
            
            <p>Thank you for choosing <?= htmlspecialchars($site_name) ?>!</p>
            
            <p>Best regards,<br>
            The <?= htmlspecialchars($site_name) ?> Team</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. All rights reserved.</p>
            <p>This email was sent automatically. Please do not reply.</p>
        </div>
    </div>
</body>
</html>

