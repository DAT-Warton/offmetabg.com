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
            <h2>Hello, <?= htmlspecialchars($user_name) ?>!</h2>
            
            <p>We received a request to reset the password for your account.</p>
            
            <p>If you made this request, please click the button below to create a new password:</p>
            
            <center>
                <a href="<?= htmlspecialchars($reset_link) ?>"class="button">Reset Password</a>
            </center>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class="token-box"><?= htmlspecialchars($reset_link) ?></div>
            
            <div class="warning">
                <strong>⚠️ Important:</strong> This link is valid for 1 hour. After that, you'll need to request a new reset link.
            </div>
            
            <p><strong>If you didn't request a password reset,</strong> please ignore this email. Your password will remain unchanged and your account is secure.</p>
            
            <p>For your security, never share this link with others.</p>
            
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

