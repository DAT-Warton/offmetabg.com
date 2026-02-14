<?php
/**
 * Environment Variable Loader
 * Simple .env file parser for PHP
 */

class EnvLoader {
    private static $loaded = false;
    private static $variables = [];
    
    /**
     * Load .env file
     * @param string $path Path to .env file
     */
    public static function load($path = __DIR__ . '/../.env') {
        if (self::$loaded) {
            return;
        }
        
        if (!file_exists($path)) {
            // No .env file, try to use existing environment variables
            self::$loaded = true;
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes
                $value = trim($value, '"\'');
                
                // Don't override existing environment variables
                if (!isset($_ENV[$key]) && !getenv($key)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                    self::$variables[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable
     * @param string $key Variable name
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get($key, $default = null) {
        // Try $_ENV first
        if (isset($_ENV[$key])) {
            return self::parseValue($_ENV[$key]);
        }
        
        // Try getenv
        $value = getenv($key);
        if ($value !== false) {
            return self::parseValue($value);
        }
        
        // Try loaded variables
        if (isset(self::$variables[$key])) {
            return self::parseValue(self::$variables[$key]);
        }
        
        return $default;
    }
    
    /**
     * Parse value to correct type
     * @param string $value
     * @return mixed
     */
    private static function parseValue($value) {
        // Boolean
        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value) === 'true';
        }
        
        // Null
        if (strtolower($value) === 'null') {
            return null;
        }
        
        // Number
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Check if variable exists
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        return isset($_ENV[$key]) || getenv($key) !== false || isset(self::$variables[$key]);
    }
    
    /**
     * Get all loaded variables
     * @return array
     */
    public static function all() {
        return array_merge($_ENV, self::$variables);
    }
}

// Helper function
if (!function_exists('env')) {
    /**
     * Get environment variable value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null) {
        return EnvLoader::get($key, $default);
    }
}

// Auto-load .env file
EnvLoader::load();
