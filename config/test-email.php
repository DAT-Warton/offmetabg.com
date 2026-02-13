<?php
/**
 * CLI Email Test Script
 * Quick test of email configuration
 */

require_once __DIR__ . '/../includes/email.php';

echo "\n";
echo "╔══════════════════════════════════════════════════╗\n";
echo "║          Email System Test - OffMeta            ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

// Load config
$config = require __DIR__ . '/email-config.php';

echo "📧 Email Configuration:\n";
echo "   API Token: " . substr($config['api_token'], 0, 20) . "...\n";
echo "   From: {$config['from_name']} <{$config['from_email']}>\n";
echo "   Site URL: {$config['site_url']}\n\n";

// Get recipient email
if (php_sapi_name() === 'cli') {
    echo "Въведете email за тест: ";
    $recipient = trim(fgets(STDIN));
    
    if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        echo "\n❌ Невалиден email адрес!\n\n";
        exit(1);
    }
} else {
    echo "❌ Този скрипт трябва да се стартира от командния ред!\n\n";
    exit(1);
}

echo "\n📤 Изпращам тестов имейл до: {$recipient}\n\n";

// Initialize email sender
$emailSender = new EmailSender();

// Send test email
$subject = "🎉 Тестов имейл от OffMeta";
$html_body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                  color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .success { background: #4CAF50; color: white; padding: 15px; 
                   border-radius: 5px; text-align: center; margin: 20px 0; }
        .info { background: #e3f2fd; border-left: 4px solid #2196F3; 
                padding: 15px; margin: 15px 0; }
        .footer { text-align: center; color: #777; margin-top: 20px; 
                  padding-top: 20px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>✅ Имейл системата работи!</h1>
        </div>
        <div class='content'>
            <div class='success'>
                <h2>🎉 Поздравления!</h2>
                <p>Имейл системата на OffMeta е конфигурирана успешно.</p>
            </div>
            
            <div class='info'>
                <h3>📋 Информация за теста:</h3>
                <ul>
                    <li><strong>Изпратено от:</strong> {$config['from_email']}</li>
                    <li><strong>Сайт:</strong> {$config['site_url']}</li>
                    <li><strong>Дата:</strong> " . date('d.m.Y H:i:s') . "</li>
                </ul>
            </div>
            
            <p>Тази конфигурация позволява изпращане на:</p>
            <ul>
                <li>✉️ Welcome emails при регистрация</li>
                <li>🔑 Password reset emails</li>
                <li>📦 Order confirmation emails</li>
                <li>✅ Account activation emails</li>
            </ul>
            
            <p><strong>Следващи стъпки:</strong></p>
            <ol>
                <li>Проверете дали имейлът е получен правилно</li>
                <li>Проверете spam папката ако не го виждате</li>
                <li>Качете кода на production сървъра</li>
            </ol>
            
            <div class='footer'>
                <p>OffMeta E-commerce System<br>
                <a href='{$config['site_url']}'>{$config['site_url']}</a></p>
            </div>
        </div>
    </div>
</body>
</html>
";

$text_body = "
Имейл системата работи!

Поздравления! Имейл системата на OffMeta е конфигурирана успешно.

Изпратено от: {$config['from_email']}
Сайт: {$config['site_url']}
Дата: " . date('d.m.Y H:i:s') . "

Тази конфигурация позволява изпращане на:
- Welcome emails при регистрация
- Password reset emails
- Order confirmation emails
- Account activation emails

OffMeta E-commerce System
{$config['site_url']}
";

$result = $emailSender->send(
    $recipient,
    'Test User',
    $subject,
    $html_body,
    $text_body
);

echo str_repeat("=", 50) . "\n";

if ($result['success']) {
    echo "✅ УСПЕХ!\n\n";
    echo "Имейлът е изпратен успешно до {$recipient}\n";
    echo "Проверете входящата си кутия (и spam папката).\n\n";
    
    if (isset($result['message_id'])) {
        echo "Message ID: {$result['message_id']}\n\n";
    }
} else {
    echo "❌ ГРЕШКА!\n\n";
    echo "Имейлът НЕ беше изпратен.\n";
    echo "Причина: {$result['message']}\n\n";
    
    if (isset($result['errors'])) {
        echo "Детайли:\n";
        print_r($result['errors']);
        echo "\n";
    }
}

echo str_repeat("=", 50) . "\n\n";
