<?php
/**
 * OAuth Social Login Handler
 * Supports: Google, Facebook, Instagram, Discord, Twitch, TikTok, Kick
 */

class OAuth {
    private $provider;
    private $config;
    
    // OAuth endpoints
    private static $endpoints = [
        'google' => [
            'auth' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token' => 'https://oauth2.googleapis.com/token',
            'user' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'scope' => 'openid email profile'
        ],
        'facebook' => [
            'auth' => 'https://www.facebook.com/v18.0/dialog/oauth',
            'token' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'user' => 'https://graph.facebook.com/v18.0/me',
            'scope' => 'email,public_profile'
        ],
        'instagram' => [
            'auth' => 'https://api.instagram.com/oauth/authorize',
            'token' => 'https://api.instagram.com/oauth/access_token',
            'user' => 'https://graph.instagram.com/me',
            'scope' => 'user_profile,user_media'
        ],
        'discord' => [
            'auth' => 'https://discord.com/api/oauth2/authorize',
            'token' => 'https://discord.com/api/oauth2/token',
            'user' => 'https://discord.com/api/users/@me',
            'scope' => 'identify email'
        ],
        'twitch' => [
            'auth' => 'https://id.twitch.tv/oauth2/authorize',
            'token' => 'https://id.twitch.tv/oauth2/token',
            'user' => 'https://api.twitch.tv/helix/users',
            'scope' => 'user:read:email'
        ],
        'tiktok' => [
            'auth' => 'https://www.tiktok.com/auth/authorize/',
            'token' => 'https://open-api.tiktok.com/oauth/access_token/',
            'user' => 'https://open-api.tiktok.com/oauth/userinfo/',
            'scope' => 'user.info.basic'
        ],
        'kick' => [
            'auth' => 'https://kick.com/oauth/authorize',
            'token' => 'https://kick.com/oauth/token',
            'user' => 'https://kick.com/api/v1/user',
            'scope' => 'user:read'
        ]
    ];
    
    public function __construct($provider) {
        $this->provider = strtolower($provider);
        
        if (!isset(self::$endpoints[$this->provider])) {
            throw new Exception("Unsupported OAuth provider: {$provider}");
        }
        
        $this->config = $this->loadConfig($this->provider);
    }
    
    /**
     * Load OAuth configuration from environment
     */
    private function loadConfig($provider) {
        $prefix = strtoupper($provider);
        
        // Special cases
        if ($provider === 'tiktok') {
            $clientKey = env("{$prefix}_CLIENT_KEY");
        } else {
            $clientKey = env("{$prefix}_CLIENT_ID");
            if (!$clientKey) {
                $clientKey = env("{$prefix}_APP_ID"); // Facebook uses APP_ID
            }
        }
        
        $clientSecret = env("{$prefix}_CLIENT_SECRET");
        if (!$clientSecret) {
            $clientSecret = env("{$prefix}_APP_SECRET"); // Facebook uses APP_SECRET
        }
        
        $redirectUri = env("{$prefix}_REDIRECT_URI");
        
        if (!$clientKey || !$clientSecret) {
            throw new Exception("OAuth credentials not configured for {$provider}");
        }
        
        return [
            'client_id' => $clientKey,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri
        ];
    }
    
    /**
     * Generate authorization URL
     */
    public function getAuthUrl($state = null) {
        $endpoint = self::$endpoints[$this->provider];
        
        if (!$state) {
            $state = bin2hex(random_bytes(16));
            $_SESSION['oauth_state'] = $state;
        }
        
        $params = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $endpoint['scope'],
            'state' => $state
        ];
        
        // Provider-specific parameters
        if ($this->provider === 'discord') {
            $params['prompt'] = 'none';
        } elseif ($this->provider === 'twitch') {
            $params['force_verify'] = 'true';
        } elseif ($this->provider === 'tiktok') {
            $params['client_key'] = $this->config['client_id'];
            unset($params['client_id']);
        }
        
        return $endpoint['auth'] . '?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code) {
        $endpoint = self::$endpoints[$this->provider];
        
        $params = [
            'code' => $code,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];
        
        // TikTok uses different parameter names
        if ($this->provider === 'tiktok') {
            $params['client_key'] = $this->config['client_id'];
            $params['client_secret'] = $this->config['client_secret'];
            unset($params['client_id']);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint['token']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        // Twitch requires Client-ID header
        if ($this->provider === 'twitch') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Client-ID: ' . $this->config['client_id']
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get access token from {$this->provider}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception("No access token in response from {$this->provider}");
        }
        
        return $data['access_token'];
    }
    
    /**
     * Get user information using access token
     */
    public function getUserInfo($accessToken) {
        $endpoint = self::$endpoints[$this->provider];
        $userUrl = $endpoint['user'];
        
        // Add fields for Facebook
        if ($this->provider === 'facebook') {
            $userUrl .= '?fields=id,name,email,picture';
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        // Set authorization header
        $headers = ["Authorization: Bearer {$accessToken}"];
        
        // Twitch requires Client-ID header
        if ($this->provider === 'twitch') {
            $headers[] = 'Client-ID: ' . $this->config['client_id'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to get user info from {$this->provider}");
        }
        
        $data = json_decode($response, true);
        
        // Normalize user data across providers
        return $this->normalizeUserData($data);
    }
    
    /**
     * Normalize user data to common format
     */
    private function normalizeUserData($data) {
        $normalized = [
            'provider' => $this->provider,
            'provider_id' => null,
            'email' => null,
            'name' => null,
            'username' => null,
            'avatar' => null,
            'raw' => $data
        ];
        
        switch ($this->provider) {
            case 'google':
                $normalized['provider_id'] = $data['id'] ?? null;
                $normalized['email'] = $data['email'] ?? null;
                $normalized['name'] = $data['name'] ?? null;
                $normalized['avatar'] = $data['picture'] ?? null;
                break;
                
            case 'facebook':
                $normalized['provider_id'] = $data['id'] ?? null;
                $normalized['email'] = $data['email'] ?? null;
                $normalized['name'] = $data['name'] ?? null;
                $normalized['avatar'] = $data['picture']['data']['url'] ?? null;
                break;
                
            case 'instagram':
                $normalized['provider_id'] = $data['id'] ?? null;
                $normalized['username'] = $data['username'] ?? null;
                $normalized['name'] = $data['username'] ?? null;
                break;
                
            case 'discord':
                $normalized['provider_id'] = $data['id'] ?? null;
                $normalized['email'] = $data['email'] ?? null;
                $normalized['username'] = $data['username'] ?? null;
                $normalized['name'] = ($data['username'] ?? '') . '#' . ($data['discriminator'] ?? '');
                $normalized['avatar'] = isset($data['avatar']) 
                    ? "https://cdn.discordapp.com/avatars/{$data['id']}/{$data['avatar']}.png"
                    : null;
                break;
                
            case 'twitch':
                $userData = $data['data'][0] ?? [];
                $normalized['provider_id'] = $userData['id'] ?? null;
                $normalized['email'] = $userData['email'] ?? null;
                $normalized['username'] = $userData['login'] ?? null;
                $normalized['name'] = $userData['display_name'] ?? null;
                $normalized['avatar'] = $userData['profile_image_url'] ?? null;
                break;
                
            case 'tiktok':
                $normalized['provider_id'] = $data['open_id'] ?? null;
                $normalized['username'] = $data['display_name'] ?? null;
                $normalized['name'] = $data['display_name'] ?? null;
                $normalized['avatar'] = $data['avatar_url'] ?? null;
                break;
                
            case 'kick':
                $normalized['provider_id'] = $data['id'] ?? null;
                $normalized['username'] = $data['username'] ?? null;
                $normalized['name'] = $data['username'] ?? null;
                $normalized['email'] = $data['email'] ?? null;
                break;
        }
        
        return $normalized;
    }
    
    /**
     * Complete OAuth flow
     */
    public static function handleCallback($provider, $code, $state = null) {
        // Verify state to prevent CSRF
        if ($state && isset($_SESSION['oauth_state'])) {
            if ($state !== $_SESSION['oauth_state']) {
                throw new Exception('Invalid OAuth state');
            }
            unset($_SESSION['oauth_state']);
        }
        
        $oauth = new self($provider);
        $accessToken = $oauth->getAccessToken($code);
        $userInfo = $oauth->getUserInfo($accessToken);
        
        return $userInfo;
    }
    
    /**
     * Find or create user from OAuth data
     */
    public static function findOrCreateUser($oauthData) {
        $db = get_database();
        
        // Try to find existing user by provider ID
        $stmt = $db->prepare("
            SELECT * FROM customers 
            WHERE oauth_provider = ? AND oauth_provider_id = ?
            LIMIT 1
        ");
        $stmt->execute([$oauthData['provider'], $oauthData['provider_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update last login
            $updateStmt = $db->prepare("UPDATE customers SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            return $user;
        }
        
        // Try to find by email if available
        if (!empty($oauthData['email'])) {
            $stmt = $db->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
            $stmt->execute([$oauthData['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Link OAuth provider to existing account
                $updateStmt = $db->prepare("
                    UPDATE customers 
                    SET oauth_provider = ?, oauth_provider_id = ?, last_login = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $oauthData['provider'],
                    $oauthData['provider_id'],
                    $user['id']
                ]);
                return $user;
            }
        }
        
        // Create new user
        $userId = 'cust_' . bin2hex(random_bytes(8));
        $username = $oauthData['username'] ?? $oauthData['email'] ?? $userId;
        $email = $oauthData['email'] ?? "{$userId}@oauth.local";
        
        // OAuth users are automatically verified (provider verified their email)
        $insertStmt = $db->prepare("
            INSERT INTO customers 
            (id, username, email, full_name, oauth_provider, oauth_provider_id, avatar_url, 
             status, role, activated, email_verified, activated_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 'customer', true, true, NOW(), NOW())
        ");
        
        $insertStmt->execute([
            $userId,
            $username,
            $email,
            $oauthData['name'] ?? $username,
            $oauthData['provider'],
            $oauthData['provider_id'],
            $oauthData['avatar']
        ]);
        
        // Return newly created user
        $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
