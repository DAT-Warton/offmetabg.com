<?php
/**
 * OAuth Callback Handler
 * Handles OAuth authentication callbacks from social providers
 */

session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/oauth.php';

// Get provider from query parameter
$provider = $_GET['provider'] ?? null;
$code = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;
$state = $_GET['state'] ?? null;

// Handle error from OAuth provider
if ($error) {
    $error_description = $_GET['error_description'] ?? $error;
    $_SESSION['auth_error'] = "OAuth Error: " . htmlspecialchars($error_description);
    header('Location: auth.php?action=login');
    exit;
}

// Validate provider
$supported_providers = ['google', 'facebook', 'instagram', 'discord', 'twitch', 'tiktok', 'kick'];
if (!$provider || !in_array($provider, $supported_providers)) {
    $_SESSION['auth_error'] = "Invalid OAuth provider";
    header('Location: auth.php?action=login');
    exit;
}

// Handle OAuth redirect (no code yet - redirect to provider)
if (!$code) {
    try {
        $oauth = new OAuth($provider);
        $authUrl = $oauth->getAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    } catch (Exception $e) {
        error_log("OAuth init error ({$provider}): " . $e->getMessage());
        $_SESSION['auth_error'] = "Failed to initialize OAuth: " . $e->getMessage();
        header('Location: auth.php?action=login');
        exit;
    }
}

// Handle OAuth callback (with code - exchange for token and get user info)
try {
    $oauth = new OAuth($provider);
    
    // Verify state to prevent CSRF
    if ($state && isset($_SESSION['oauth_state'])) {
        if ($state !== $_SESSION['oauth_state']) {
            throw new Exception('Invalid state parameter - possible CSRF attack');
        }
        unset($_SESSION['oauth_state']);
    }
    
    // Exchange authorization code for access token
    $tokenData = $oauth->getAccessToken($code);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        throw new Exception('Failed to obtain access token');
    }
    
    // Get user info from provider
    $userData = $oauth->getUserInfo($tokenData['access_token']);
    
    if (!$userData || !isset($userData['email'])) {
        throw new Exception('Failed to obtain user information');
    }
    
    // Find or create user in database
    $user = $oauth->findOrCreateUser($userData, $provider);
    
    if (!$user) {
        throw new Exception('Failed to create user account');
    }
    
    // Set session variables
    $_SESSION['customer_id'] = $user['id'];
    $_SESSION['customer_username'] = $user['username'];
    $_SESSION['customer_email'] = $user['email'];
    $_SESSION['auth_method'] = 'oauth';
    $_SESSION['oauth_provider'] = $provider;
    
    // Success message
    $_SESSION['success_message'] = "Successfully logged in with " . ucfirst($provider);
    
    // Redirect to homepage or intended destination
    $redirect = $_SESSION['oauth_redirect'] ?? '/';
    unset($_SESSION['oauth_redirect']);
    
    header('Location: ' . $redirect);
    exit;
    
} catch (Exception $e) {
    error_log("OAuth callback error ({$provider}): " . $e->getMessage());
    $_SESSION['auth_error'] = "Authentication failed: " . $e->getMessage();
    header('Location: auth.php?action=login');
    exit;
}
