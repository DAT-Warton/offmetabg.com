-- Update currency from BGN to EUR
UPDATE site_settings SET setting_value='EUR' WHERE category='commerce' AND setting_key='currency';
UPDATE site_settings SET setting_value='€' WHERE category='commerce' AND setting_key='currency_symbol';

-- Verify changes
SELECT setting_key, setting_value FROM site_settings WHERE category='commerce' AND (setting_key='currency' OR setting_key='currency_symbol');
