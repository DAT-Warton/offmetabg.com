<?php
/**
 * Courier API Configuration
 * Bulgarian Couriers: Econt and Speedy
 * 
 * Setup Instructions:
 * 
 * 1. ECONT EXPRESS
 *    - Visit: https://www.econt.com/
 *    - Register for business account
 *    - Get API credentials from client panel
 *    - Documentation: https://www.econt.com/services/web-services
 * 
 * 2. SPEEDY
 *    - Visit: https://www.speedy.bg/
 *    - Register for business account
 *    - Request API access from account manager
 *    - Documentation: https://www.speedy.bg/bg/services-integrations
 */

return [
    // Econt Express Credentials
    'econt_username' => '', // Your Econt business account username
    'econt_password' => '', // Your Econt API password
    'econt_client_id' => '', // Your Econt client ID
    'econt_api_url' => 'https://ee.econt.com/services/', // Production API URL
    'econt_test_mode' => true, // Set to false for production
    
    // Speedy Credentials
    'speedy_username' => '', // Your Speedy business account username
    'speedy_password' => '', // Your Speedy API password
    'speedy_client_id' => '', // Your Speedy client ID
    'speedy_api_url' => 'https://api.speedy.bg/v1/', // Production API URL
    'speedy_test_mode' => true, // Set to false for production
    
    // Sender Information (Your Business)
    'sender_name' => 'OffMeta Store',
    'sender_phone' => '+359 888 000 000',
    'sender_email' => 'orders@offmeta.bg',
    'sender_city' => 'Sofia', // Your business city
    'sender_address' => 'Your business address', // Your business address
    'sender_postal_code' => '1000', // Your business postal code
    
    // Default Settings
    'default_weight' => 0.5, // Default package weight in kg if not specified
    'auto_create_shipment' => false, // Automatically create shipment when order is confirmed
    'send_tracking_emails' => true, // Send tracking updates to customers
];
