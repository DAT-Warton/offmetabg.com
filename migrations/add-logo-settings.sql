-- Add Logo Management Settings
-- Add new logo-related settings to appearance category

-- Update logo_url to be image type
UPDATE site_settings 
SET setting_type = 'image', 
    description = 'Main site logo (upload image or enter URL)',
    display_order = 1
WHERE setting_key = 'logo_url';

-- Add logo positioning and size settings
INSERT INTO site_settings (category, setting_key, setting_value, setting_type, is_public, label, description, display_order) 
VALUES
('appearance', 'logo_position', 'left', 'select', true, 'Logo Position', 'Logo alignment in header (left/center/right)', 2),
('appearance', 'logo_max_height', '50', 'number', true, 'Logo Max Height (px)', 'Maximum logo height in pixels', 3),
('appearance', 'logo_max_width', '200', 'number', true, 'Logo Max Width (px)', 'Maximum logo width in pixels', 4),
('appearance', 'show_site_name', 'true', 'boolean', true, 'Show Site Name', 'Display site name next to logo', 5)
ON CONFLICT (category, setting_key) DO UPDATE 
SET setting_value = EXCLUDED.setting_value,
    setting_type = EXCLUDED.setting_type,
    label = EXCLUDED.label,
    description = EXCLUDED.description,
    display_order = EXCLUDED.display_order;
