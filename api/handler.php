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

// Parse the API path
$apiPath = str_replace('api/', '', $_GET['route'] ?? '');
$parts = explode('/', trim($apiPath, '/'));

// Basic API response
$response = [
    'success' => false,
    'message' => 'API endpoint not implemented',
    'endpoint' => $apiPath
];

// Return JSON response
echo json_encode($response);
exit;
