<?php
/**
 * API Request Handler
 */

// Define CMS_ROOT if not already defined
if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

// Set JSON headers
header('Content-Type: application/json');

// Get action from query parameter
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Initialize database
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';
$db = Database::getInstance();

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
            
            // Save to database/options
            $result = $db->setOption('active_theme', $input['theme']);
            
            if (!$result) {
                throw new Exception('Failed to save theme to database');
            }
            
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
            // Save custom theme to database (insert or update)
            if (empty($input['name']) || empty($input['slug']) || empty($input['variables'])) {
                throw new Exception('Theme name, slug, and variables are required');
            }
            
            // Check if theme with this slug already exists
            $pdo = $db->getPDO();
            $existingTheme = null;
            
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT id FROM themes WHERE slug = ?");
                $stmt->execute([$input['slug']]);
                $existingTheme = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // JSON fallback
                $existingTheme = $db->table('themes')->find('slug', $input['slug']);
            }
            
            $themeData = [
                'name' => $input['name'],
                'slug' => $input['slug'],
                'description' => $input['description'] ?? '',
                'type' => 'custom',
                'category' => $input['category'] ?? 'light',
                'variables' => json_encode($input['variables']),
                'version' => $input['version'] ?? '1.0'
            ];
            
            if ($existingTheme) {
                // Update existing theme
                $themeId = $existingTheme['id'];
                
                if ($pdo) {
                    $stmt = $pdo->prepare("
                        UPDATE themes 
                        SET name = ?, description = ?, category = ?, variables = ?, version = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $themeData['name'],
                        $themeData['description'],
                        $themeData['category'],
                        $themeData['variables'],
                        $themeData['version'],
                        $themeId
                    ]);
                } else {
                    $db->table('themes')->update($themeId, $themeData);
                }
                
                $message = 'Theme updated successfully';
            } else {
                // Insert new theme
                $themeData['is_active'] = false;
                $themeId = $db->insert('themes', $themeData);
                $message = 'Theme saved successfully';
            }
            
            $response = [
                'success' => true,
                'message' => $message,
                'theme_id' => $themeId,
                'updated' => $existingTheme !== null
            ];
            break;
            
        case 'get-custom-theme':
            // Get custom theme by ID
            $themeId = $_GET['id'] ?? null;
            if (!$themeId) {
                throw new Exception('Theme ID is required');
            }
            
            // Get theme from database
            $pdo = $db->getPDO();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM themes WHERE id = ?");
                $stmt->execute([$themeId]);
                $theme = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // JSON fallback
                $theme = $db->table('themes')->find('id', $themeId);
            }
            
            if (!$theme) {
                throw new Exception('Theme not found');
            }
            
            // Parse variables JSON
            if (isset($theme['variables']) && is_string($theme['variables'])) {
                $theme['variables'] = json_decode($theme['variables'], true);
            }
            
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
            $pdo = $db->getPDO();
            if ($pdo) {
                $stmt = $pdo->query("SELECT * FROM themes ORDER BY created_at DESC");
                $themes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // JSON fallback
                $themes = $db->table('themes')->all();
            }
            
            // Parse variables JSON for each theme
            foreach ($themes as &$theme) {
                if (isset($theme['variables']) && is_string($theme['variables'])) {
                    $theme['variables'] = json_decode($theme['variables'], true);
                }
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
