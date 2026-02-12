<?php
/**
 * Email Configuration for MailerSend
 * 
 * IMPORTANT: Update the from_email and from_name with your verified sender details
 */

return [
    // MailerSend API Token
    'api_token' => 'mlsn.f58d240cc26650e7d7b9bd00e70b65b2a9c5185127eaee816ddf1344f518cf8e',
    
    // From Email (must be verified in MailerSend)
    // Example: noreply@yourdomain.com
    // IMPORTANT: Change this to your verified domain!
    'from_email' => 'noreply@trial-351ndgwp1jo4zqx8.mlsender.net', // MailerSend trial domain
    
    // From Name (displayed to recipients)
    'from_name' => 'OffMeta',
    
    // Reply-To Email (optional)
    'reply_to_email' => '',
    'reply_to_name' => '',
    
    // Site Settings
    'site_name' => 'OffMeta',
    'site_url' => 'http://localhost:8000', // Change to your domain when deploying
    
    // Email Features
    'enable_email_verification' => true,
    'enable_password_reset' => true,
    'enable_order_confirmation' => true,
    
    // API Endpoint
    'api_url' => 'https://api.mailersend.com/v1/email',
];
