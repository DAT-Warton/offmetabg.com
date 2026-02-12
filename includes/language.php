<?php
// Language System for Multi-language Support
// Default: Bulgarian (BG)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set default language to Bulgarian
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}

// Handle language switch
if (isset($_GET['lang'])) {
    $requested_lang = $_GET['lang'];
    if (in_array($requested_lang, ['bg', 'en'])) {
        $_SESSION['lang'] = $requested_lang;
        
        // Redirect to remove the ?lang parameter but keep other params
        $current_params = $_GET;
        unset($current_params['lang']);
        
        $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
        if (!empty($current_params)) {
            $redirect_url .= '?' . http_build_query($current_params);
        }
        
        header("Location: $redirect_url");
        exit;
    }
}

// Load language file
$current_lang = $_SESSION['lang'];
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';

if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    // Fallback to Bulgarian if file doesn't exist
    require_once __DIR__ . '/../lang/bg.php';
}

// Translation function
function __($key) {
    global $translations;
    
    // Handle nested keys like "menu.dashboard"
    if (strpos($key, '.') !== false) {
        $keys = explode('.', $key);
        $value = $translations;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $key; // Return key if translation not found
            }
        }
        
        return $value;
    }
    
    return isset($translations[$key]) ? $translations[$key] : $key;
}

// Get current language
function current_lang() {
    return $_SESSION['lang'];
}

// Get opposite language (for switcher)
function opposite_lang() {
    return $_SESSION['lang'] === 'bg' ? 'en' : 'bg';
}

// Get language name
function lang_name($lang = null) {
    $lang = $lang ?? current_lang();
    return $lang === 'bg' ? 'Български' : 'English';
}

// Get language flag emoji
function lang_flag($lang = null) {
    $lang = $lang ?? current_lang();
    return $lang === 'bg' ? '🇧🇬' : '🇬🇧';
}
