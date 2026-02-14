<?php
/**
 * Site Settings Helper Functions
 * Database-driven configuration management
 */

/**
 * Get a site setting value
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @param string $category Optional category filter
 * @return mixed Setting value
 */
function get_site_setting($key, $default = null, $category = null) {
    static $cache = [];
    
    $cache_key = $category ? "{$category}.{$key}" : $key;
    
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    
    try {
        $db = get_database();
        
        // Return default if no database connection
        if (!$db) {
            return $default;
        }
        
        if ($category) {
            $stmt = $db->prepare("SELECT setting_value, setting_type FROM site_settings WHERE category = ? AND setting_key = ? LIMIT 1");
            $stmt->execute([$category, $key]);
        } else {
            $stmt = $db->prepare("SELECT setting_value, setting_type FROM site_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
        }
        
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($setting) {
            $value = convert_setting_value($setting['setting_value'], $setting['setting_type']);
            $cache[$cache_key] = $value;
            return $value;
        }
        
    } catch (Exception $e) {
        error_log("Failed to get site setting '{$key}': " . $e->getMessage());
    }
    
    return $default;
}

/**
 * Set a site setting value
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $category Optional category
 * @return bool Success
 */
function set_site_setting($key, $value, $category = null) {
    try {
        $db = get_database();
        
        // Return false if no database connection
        if (!$db) {
            return false;
        }
        
        // Convert value to string for storage
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }
        
        if ($category) {
            $stmt = $db->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE category = ? AND setting_key = ?");
            $result = $stmt->execute([$value, $category, $key]);
        } else {
            $stmt = $db->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $result = $stmt->execute([$value, $key]);
        }
        
        return $result && $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        error_log("Failed to set site setting '{$key}': " . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings in a category
 * @param string $category Category name
 * @param bool $public_only Only return public settings
 * @return array Settings array
 */
function get_settings_by_category($category, $public_only = false) {
    static $cache = [];
    
    $cache_key = $category . ($public_only ? '_public' : '_all');
    
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    
    try {
        $db = get_database();
        
        if ($public_only) {
            $stmt = $db->prepare("SELECT setting_key, setting_value, setting_type FROM site_settings WHERE category = ? AND is_public = true ORDER BY display_order");
        } else {
            $stmt = $db->prepare("SELECT setting_key, setting_value, setting_type FROM site_settings WHERE category = ? ORDER BY display_order");
        }
        
        $stmt->execute([$category]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = convert_setting_value($row['setting_value'], $row['setting_type']);
        }
        
        $cache[$cache_key] = $settings;
        return $settings;
        
    } catch (Exception $e) {
        error_log("Failed to get settings for category '{$category}': " . $e->getMessage());
        return [];
    }
}

/**
 * Get all public settings (for frontend)
 * @return array Associative array of all public settings
 */
function get_public_settings() {
    static $cache = null;
    
    if ($cache !== null) {
        return $cache;
    }
    
    try {
        $db = get_database();
        $stmt = $db->query("SELECT category, setting_key, setting_value, setting_type FROM site_settings WHERE is_public = true ORDER BY category, display_order");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach ($results as $row) {
            if (!isset($settings[$row['category']])) {
                $settings[$row['category']] = [];
            }
            $settings[$row['category']][$row['setting_key']] = convert_setting_value($row['setting_value'], $row['setting_type']);
        }
        
        $cache = $settings;
        return $settings;
        
    } catch (Exception $e) {
        error_log("Failed to get public settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Convert setting value based on type
 * @param string $value Raw value from database
 * @param string $type Setting type
 * @return mixed Converted value
 */
function convert_setting_value($value, $type) {
    if ($value === null || $value === '') {
        return null;
    }
    
    switch ($type) {
        case 'boolean':
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        
        case 'number':
            return is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : 0;
        
        case 'json':
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        
        default:
            return $value;
    }
}

/**
 * Check if maintenance mode is enabled
 * @return bool
 */
function is_maintenance_mode() {
    return get_site_setting('maintenance_mode', false, 'maintenance');
}

/**
 * Get site name
 * @return string
 */
function get_site_name() {
    return get_site_setting('site_name', 'OffMeta BG', 'general');
}

/**
 * Get site URL
 * @return string
 */
function get_site_url() {
    return get_site_setting('site_url', 'https://offmetabg.com', 'general');
}

/**
 * Get contact email
 * @return string
 */
function get_contact_email() {
    return get_site_setting('contact_email', 'contact@offmetabg.com', 'general');
}

/**
 * Get currency settings
 * @return array [code, symbol]
 */
function get_currency_settings() {
    return [
        'code' => get_site_setting('currency', 'EUR', 'commerce'),
        'symbol' => get_site_setting('currency_symbol', '€', 'commerce')
    ];
}

/**
 * Check if feature is enabled
 * @param string $feature Feature key
 * @param string $category Category name
 * @return bool
 */
function is_feature_enabled($feature, $category = 'appearance') {
    return get_site_setting($feature, false, $category);
}

/**
 * Clear settings cache
 */
function clear_settings_cache() {
    // This would be more sophisticated in production with Redis/Memcached
    // For now, just a placeholder
    return true;
}

/**
 * Export settings as JSON
 * @param string $category Optional category filter
 * @return string JSON string
 */
function export_settings($category = null) {
    try {
        $db = get_database();
        
        if ($category) {
            $stmt = $db->prepare("SELECT * FROM site_settings WHERE category = ? ORDER BY display_order");
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query("SELECT * FROM site_settings ORDER BY category, display_order");
        }
        
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($settings, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        error_log("Failed to export settings: " . $e->getMessage());
        return '[]';
    }
}

/**
 * Import settings from JSON
 * @param string $json JSON string
 * @return array [success, errors]
 */
function import_settings($json) {
    $errors = [];
    $success = 0;
    
    try {
        $settings = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [0, ['Invalid JSON format']];
        }
        
        $db = get_database();
        
        foreach ($settings as $setting) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO site_settings 
                    (category, setting_key, setting_value, setting_type, is_encrypted, is_public, label, description, default_value, display_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON CONFLICT (category, setting_key) 
                    DO UPDATE SET 
                        setting_value = EXCLUDED.setting_value,
                        setting_type = EXCLUDED.setting_type,
                        is_encrypted = EXCLUDED.is_encrypted,
                        is_public = EXCLUDED.is_public,
                        label = EXCLUDED.label,
                        description = EXCLUDED.description,
                        default_value = EXCLUDED.default_value,
                        display_order = EXCLUDED.display_order,
                        updated_at = NOW()
                ");
                
                $stmt->execute([
                    $setting['category'],
                    $setting['setting_key'],
                    $setting['setting_value'] ?? '',
                    $setting['setting_type'] ?? 'text',
                    $setting['is_encrypted'] ?? false,
                    $setting['is_public'] ?? true,
                    $setting['label'] ?? '',
                    $setting['description'] ?? '',
                    $setting['default_value'] ?? '',
                    $setting['display_order'] ?? 0
                ]);
                
                $success++;
                
            } catch (Exception $e) {
                $errors[] = "Failed to import {$setting['setting_key']}: " . $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        $errors[] = "Import failed: " . $e->getMessage();
    }
    
    return [$success, $errors];
}
