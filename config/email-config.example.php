<?php
/**
 * Email Configuration for MailerSend
 * 
 * IMPORTANT: Copy this file to email-config.php and update with your credentials
 */

return [
    // MailerSend API Token
    'api_token' => 'YOUR_MAILERSEND_API_TOKEN',
    
    // From Email (must be verified in MailerSend)
    'from_email' => 'noreply@yourdomain.com',
    
    // From Name (displayed to recipients)
    'from_name' => 'Your Site Name',
    
    // Reply-To Email (optional)
    'reply_to_email' => '',
    'reply_to_name' => '',
    
    // Site Settings
    'site_name' => 'Your Site Name',
    'site_url' => 'https://yourdomain.com',
    
    // Email Features
    'enable_email_verification' => true,
    'enable_password_reset' => true,
    'enable_order_confirmation' => true,
    
    // API Endpoint
    'api_url' => 'https://api.mailersend.com/v1/email',
];
