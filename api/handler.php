<?php
/**
 * API Request Handler
 */

// Ensure we're accessed through index.php
if (!defined('CMS_ROOT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

// Set JSON headers
header('Content-Type: application/json');

// Get action from query parameter
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Initialize database
require_once CMS_ROOT . '/includes/database.php';
$db = new Database();

// Parse JSON input for POST requests
$input = [];
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

// Basic API response
$response = [
    'success' => false,
    'message' => 'Unknown action',
    'action' => $action
];

try {
    switch ($action) {
        // ============================================
        // THEME MANAGEMENT ACTIONS
        // ============================================
        
        case 'set-theme':
            // Set active theme
            if (empty($input['theme'])) {
                throw new Exception('Theme name is required');
            }
            
            $db->setOption('active_theme', $input['theme']);
            
            $response = [
                'success' => true,
                'message' => 'Theme activated successfully',
                'theme' => $input['theme']
            ];
            break;
            
        case 'get-active-theme':
            // Get current active theme
            $theme = $db->getOption('active_theme', 'default');
            
            $response = [
                'success' => true,
                'theme' => $theme
            ];
            break;
            
        case 'save-custom-theme':
            // Save custom theme to database
            if (empty($input['name']) || empty($input['slug']) || empty($input['variables'])) {
                throw new Exception('Theme name, slug, and variables are required');
            }
            
            $themeId = $db->insert('themes', [
                'name' => $input['name'],
                'slug' => $input['slug'],
                'description' => $input['description'] ?? '',
                'type' => 'custom',
                'category' => $input['category'] ?? 'light',
                'variables' => json_encode($input['variables']),
                'version' => $input['version'] ?? '1.0',
                'is_active' => false
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Theme saved successfully',
                'theme_id' => $themeId
            ];
            break;
            
        case 'get-custom-theme':
            // Get custom theme by ID
            $themeId = $_GET['id'] ?? null;
            if (!$themeId) {
                throw new Exception('Theme ID is required');
            }
            
            $theme = $db->query("SELECT * FROM themes WHERE id = ?", [$themeId])->fetch();
            
            if (!$theme) {
                throw new Exception('Theme not found');
            }
            
            // Parse variables JSON
            $theme['variables'] = json_decode($theme['variables'], true);
            
            $response = [
                'success' => true,
                'theme' => $theme
            ];
            break;
            
        case 'delete-custom-theme':
            // Delete custom theme
            if (empty($input['id'])) {
                throw new Exception('Theme ID is required');
            }
            
            $db->delete('themes', ['id' => $input['id']]);
            
            $response = [
                'success' => true,
                'message' => 'Theme deleted successfully'
            ];
            break;
            
        case 'list-custom-themes':
            // List all custom themes
            $themes = $db->query("SELECT * FROM themes ORDER BY created_at DESC")->fetchAll();
            
            // Parse variables JSON for each theme
            foreach ($themes as &$theme) {
                $theme['variables'] = json_decode($theme['variables'], true);
            }
            
            $response = [
                'success' => true,
                'themes' => $themes
            ];
            break;
            
        // ============================================
        // FALLBACK
        // ============================================
        
        default:
            // Parse the API path
            $apiPath = str_replace('api/', '', $_GET['route'] ?? '');
            
            $response = [
                'success' => false,
                'message' => 'API endpoint not implemented',
                'endpoint' => $apiPath,
                'action' => $action
            ];
            break;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'action' => $action
    ];
    
    // Log error
    error_log("API Error [{$action}]: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
exit;
