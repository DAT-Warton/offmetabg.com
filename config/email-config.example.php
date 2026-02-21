<?php
/**
 * Email Configuration (Local PHP mail / Postfix)
 *
 * IMPORTANT: Copy this file to `config/email-config.php` and update values.
 * This project uses PHP's `mail()` function with a local MTA (Postfix) by default.
 */

return [
    // Driver: 'postfix' uses local PHP `mail()` (recommended)
    // Other options (if you configure external SMTP) could be 'smtp'.
    'driver' => 'postfix',

    // From Email and Name used in outgoing messages
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'Your Site Name',

    // Optional Reply-To
    'reply_to_email' => '',
    'reply_to_name' => '',

    // Site Settings
    'site_name' => 'Your Site Name',
    'site_url' => 'https://yourdomain.com',

    // Email Features (toggle as needed)
    'enable_email_verification' => true,
    'enable_password_reset' => true,
    'enable_order_confirmation' => true,

    // If you later use external SMTP, add settings here (example fields):
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',
    'smtp_encryption' => 'tls',
];
