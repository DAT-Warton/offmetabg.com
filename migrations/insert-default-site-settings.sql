-- Insert Default Site Settings
-- Run this after creating the site_settings table

-- General Settings
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) VALUES
('general', 'site_name', 'OffMeta BG', 'text', true, 'Site Name', 'The name of your website', 1),
('general', 'site_tagline', 'Your Premium Digital Products Store', 'text', true, 'Site Tagline', 'A brief description of your site', 2),
('general', 'site_description', 'Shop premium digital products including social media services, gaming accounts, and more', 'text', true, 'Site Description', 'Used for SEO meta description', 3),
('general', 'site_url', 'https://offmetabg.com', 'url', true, 'Site URL', 'The main URL of your website', 4),
('general', 'contact_email', 'contact@offmetabg.com', 'email', true, 'Contact Email', 'Main contact email address', 5),
('general', 'support_email', 'support@offmetabg.com', 'email', true, 'Support Email', 'Customer support email', 6),
('general', 'phone_number', '', 'text', true, 'Phone Number', 'Contact phone number', 7),
('general', 'company_address', '', 'text', true, 'Company Address', 'Physical business address', 8),
('general', 'business_hours', 'Mon-Fri: 9AM-6PM', 'text', true, 'Business Hours', 'Operating hours', 9),
('general', 'timezone', 'Europe/Sofia', 'text', false, 'Timezone', 'Server timezone', 10);

-- Email Settings
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_encrypted, is_public, label, description, display_order) VALUES
('email', 'smtp_host', '', 'text', false, false, 'SMTP Host', 'SMTP server hostname', 1),
('email', 'smtp_port', '587', 'number', false, false, 'SMTP Port', 'SMTP server port', 2),
('email', 'smtp_username', '', 'text', false, false, 'SMTP Username', 'SMTP authentication username', 3),
('email', 'smtp_password', '', 'password', true, false, 'SMTP Password', 'SMTP authentication password', 4),
('email', 'smtp_encryption', 'tls', 'text', false, false, 'SMTP Encryption', 'TLS or SSL', 5),
('email', 'from_email', 'noreply@offmetabg.com', 'email', false, false, 'From Email', 'Default sender email', 6),
('email', 'from_name', 'OffMeta BG', 'text', false, false, 'From Name', 'Default sender name', 7),
('email', 'admin_notification_email', 'admin@offmetabg.com', 'email', false, false, 'Admin Email', 'Receive order notifications', 8);

-- Social Media
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) VALUES
('social', 'facebook_url', '', 'url', true, 'Facebook URL', 'Facebook page URL', 1),
('social', 'instagram_url', '', 'url', true, 'Instagram URL', 'Instagram profile URL', 2),
('social', 'twitter_url', '', 'url', true, 'Twitter URL', 'Twitter profile URL', 3),
('social', 'youtube_url', '', 'url', true, 'YouTube URL', 'YouTube channel URL', 4),
('social', 'linkedin_url', '', 'url', true, 'LinkedIn URL', 'LinkedIn page URL', 5),
('social', 'discord_url', '', 'url', true, 'Discord URL', 'Discord server invite', 6),
('social', 'tiktok_url', '', 'url', true, 'TikTok URL', 'TikTok profile URL', 7),
('social', 'twitch_url', '', 'url', true, 'Twitch URL', 'Twitch channel URL', 8),
('social', 'kick_url', '', 'url', true, 'Kick URL', 'Kick.com channel URL', 9);

-- API Keys (encrypted)
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_encrypted, is_public, label, description, display_order) VALUES
('api', 'cloudflare_api_key', '', 'password', true, false, 'Cloudflare API Key', 'Cloudflare API authentication', 1),
('api', 'cloudflare_zone_id', '', 'text', false, false, 'Cloudflare Zone ID', 'Cloudflare zone identifier', 2),
('api', 'google_analytics_id', '', 'text', false, true, 'Google Analytics ID', 'GA tracking ID', 3),
('api', 'recaptcha_site_key', '', 'text', false, true, 'reCAPTCHA Site Key', 'Google reCAPTCHA site key', 4),
('api', 'recaptcha_secret_key', '', 'password', true, false, 'reCAPTCHA Secret', 'Google reCAPTCHA secret', 5),
('api', 'courier_api_key', '', 'password', true, false, 'Courier API Key', 'Shipping courier API key', 6);

-- Appearance Settings
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) VALUES
('appearance', 'logo_url', '/assets/img/logo.png', 'text', true, 'Logo URL', 'Main site logo path', 1),
('appearance', 'favicon_url', '/favicon.ico', 'text', true, 'Favicon URL', 'Site favicon path', 2),
('appearance', 'default_theme', 'purple-dreams', 'text', true, 'Default Theme', 'Default color theme', 3),
('appearance', 'enable_dark_mode', 'true', 'boolean', true, 'Enable Dark Mode', 'Allow users to switch to dark mode', 4),
('appearance', 'products_per_page', '12', 'number', true, 'Products Per Page', 'Number of products to show per page', 5),
('appearance', 'show_stock_count', 'true', 'boolean', true, 'Show Stock Count', 'Display stock quantities', 6),
('appearance', 'enable_wishlist', 'false', 'boolean', true, 'Enable Wishlist', 'Allow users to save favorites', 7),
('appearance', 'show_related_products', 'true', 'boolean', true, 'Show Related Products', 'Display related items', 8);

-- Commerce Settings
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) VALUES
('commerce', 'currency', 'BGN', 'text', true, 'Currency', 'Default currency code', 1),
('commerce', 'currency_symbol', 'лв', 'text', true, 'Currency Symbol', 'Currency display symbol', 2),
('commerce', 'tax_rate', '0.20', 'number', true, 'Tax Rate', 'VAT/Tax percentage (0.20 = 20%)', 3),
('commerce', 'enable_tax', 'true', 'boolean', true, 'Enable Tax', 'Apply tax to orders', 4),
('commerce', 'free_shipping_threshold', '100.00', 'number', true, 'Free Shipping Threshold', 'Minimum order for free shipping', 5),
('commerce', 'min_order_amount', '10.00', 'number', true, 'Minimum Order', 'Minimum order amount', 6),
('commerce', 'enable_guest_checkout', 'false', 'boolean', true, 'Guest Checkout', 'Allow checkout without account', 7),
('commerce', 'order_prefix', 'ORD-', 'text', false, 'Order Prefix', 'Prefix for order numbers', 8),
('commerce', 'low_stock_threshold', '5', 'number', false, 'Low Stock Alert', 'Alert when stock is below this', 9),
('commerce', 'payment_methods', '["bank_transfer", "paypal", "stripe"]', 'json', true, 'Payment Methods', 'Enabled payment methods', 10);

-- SEO & Marketing
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) VALUES
('seo', 'meta_keywords', 'digital products, social media, gaming accounts, bulgaria', 'text', true, 'Meta Keywords', 'SEO keywords (comma separated)', 1),
('seo', 'og_image', '/assets/img/og-image.jpg', 'text', true, 'OG Image', 'Open Graph share image', 2),
('seo', 'enable_sitemap', 'true', 'boolean', false, 'Enable Sitemap', 'Generate XML sitemap', 3),
('seo', 'robots_txt', 'User-agent: *\nAllow: /', 'text', false, 'Robots.txt', 'Robots.txt content', 4),
('seo', 'google_site_verification', '', 'text', true, 'Google Verification', 'Google Search Console verification code', 5);

-- Footer Content
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) VALUES
('footer', 'footer_about', 'OffMeta BG is your trusted source for premium digital products and services.', 'text', true, 'About Text', 'Footer about section', 1),
('footer', 'footer_copyright', '© 2026 OffMeta BG. All rights reserved.', 'text', true, 'Copyright Text', 'Copyright notice', 2),
('footer', 'show_payment_icons', 'true', 'boolean', true, 'Show Payment Icons', 'Display payment method logos', 3),
('footer', 'show_social_links', 'true', 'boolean', true, 'Show Social Links', 'Display social media icons', 4);

-- Maintenance
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) VALUES
('maintenance', 'maintenance_mode', 'false', 'boolean', false, 'Maintenance Mode', 'Enable to take site offline', 1),
('maintenance', 'maintenance_message', 'We are currently performing maintenance. Please check back soon!', 'text', false, 'Maintenance Message', 'Message shown during maintenance', 2),
('maintenance', 'enable_cache', 'true', 'boolean', false, 'Enable Cache', 'Enable page caching', 3),
('maintenance', 'cache_duration', '3600', 'number', false, 'Cache Duration', 'Cache lifetime in seconds', 4);

-- Insert complete message
INSERT INTO options (option_key, option_value) VALUES 
('site_settings_initialized', 'true'),
('site_settings_version', '1.0')
ON CONFLICT (option_key) DO UPDATE SET option_value = EXCLUDED.option_value;
