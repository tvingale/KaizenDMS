<?php
/**
 * KaizenAuth JWT-Based SSO Integration Library
 * Updated to handle JWT tokens in cookies (real KaizenAuth behavior)
 */

class KaizenSSO {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
        
        // Validate required configuration
        $required = ['auth_domain', 'app_id', 'app_secret'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new Exception("Missing required configuration: $key");
            }
        }
    }
    
    /**
     * Decode JWT token
     */
    private function decodeJWT($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        
        try {
            // Decode header and payload
            $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            
            if (!$header || !$payload) {
                return null;
            }
            
            return [
                'header' => $header,
                'payload' => $payload,
                'signature' => $parts[2]
            ];
        } catch (Exception $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("JWT decode error: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Get JWT token from cookies
     */
    private function getJWTFromCookies() {
        // Check for kaizen_refresh token first
        if (isset($_COOKIE['kaizen_refresh'])) {
            return $_COOKIE['kaizen_refresh'];
        }
        
        // Check for other possible KaizenAuth token names (updated based on findings)
        $possibleTokens = ['kaizen_refresh', 'kaizen_token', 'kaizen_access', 'kaizen_auth'];
        foreach ($possibleTokens as $tokenName) {
            if (isset($_COOKIE[$tokenName])) {
                return $_COOKIE[$tokenName];
            }
        }
        
        return null;
    }
    
    /**
     * Check if user is authenticated via JWT token in cookies
     */
    public function isAuthenticated() {
        $jwt = $this->getJWTFromCookies();
        
        if (!$jwt) {
            return false;
        }
        
        $decoded = $this->decodeJWT($jwt);
        
        if (!$decoded || !isset($decoded['payload'])) {
            return false;
        }
        
        $payload = $decoded['payload'];
        
        // Check token expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("KaizenAuth: JWT token expired");
            }
            return false;
        }
        
        // Check if token has required user info
        if (!isset($payload['user_id']) || !isset($payload['username'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get authenticated user information from JWT
     */
    public function getUserInfo() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $jwt = $this->getJWTFromCookies();
        $decoded = $this->decodeJWT($jwt);
        $payload = $decoded['payload'];
        
        
        // Enhanced JWT now contains complete user information - no parsing needed!
        return [
            'id' => $payload['user_id'],
            'username' => $payload['username'],
            'email' => $payload['email'] ?? $payload['username'] . '@' . parse_url($this->config['auth_domain'], PHP_URL_HOST),
            'name' => $payload['name'] ?? $payload['username'],
            'first_name' => $payload['first_name'] ?? $payload['username'],
            'last_name' => $payload['last_name'] ?? '',
            'display_name' => $payload['name'] ?? trim(($payload['first_name'] ?? '') . ' ' . ($payload['last_name'] ?? '')) ?: $payload['username'],
            'role' => $payload['role'] ?? 'user',
            'mobile' => $payload['mobile'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
            'email_verified' => $payload['email_verified'] ?? false,
            'mobile_verified' => $payload['mobile_verified'] ?? false,
            'profile_picture' => $payload['profile_picture'] ?? null
        ];
    }
    
    /**
     * Redirect user to KaizenAuth login page
     */
    public function redirectToLogin() {
        // Store return URL for redirect back
        $_SESSION['kaizen_return_url'] = $this->getCurrentUrl();
        $_SESSION['kaizen_app_id'] = $this->config['app_id'];
        
        $loginUrl = $this->config['auth_domain'] . '/index.php';
        
        // Debug logging
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("KaizenAuth: Redirecting to login: " . $loginUrl);
            error_log("KaizenAuth: Return URL set: " . $_SESSION['kaizen_return_url']);
        }
        
        // Ensure no output before redirect
        if (ob_get_length()) {
            ob_clean();
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }
    
    /**
     * Handle successful authentication
     */
    public function handleAuthCallback() {
        if (!$this->isAuthenticated()) {
            return [
                'success' => false, 
                'error' => 'Authentication failed - no valid JWT token'
            ];
        }
        
        $user = $this->getUserInfo();
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Failed to get user information from JWT'
            ];
        }
        
        // Store user info in session for compatibility
        $_SESSION['kaizen_authenticated'] = true;
        $_SESSION['kaizen_user'] = $user;
        $_SESSION['kaizen_token'] = $this->getJWTFromCookies();
        
        // Legacy session variables for backward compatibility
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        $_SESSION['token'] = $this->getJWTFromCookies();
        
        // Log successful authentication
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("KaizenAuth: JWT Authentication successful for user: " . json_encode($user));
        }
        
        return [
            'success' => true,
            'user' => $user,
            'token' => $this->getJWTFromCookies()
        ];
    }
    
    /**
     * Clear authentication session
     */
    public function clearSession() {
        unset($_SESSION['kaizen_authenticated']);
        unset($_SESSION['kaizen_user']);
        unset($_SESSION['kaizen_token']);
        unset($_SESSION['kaizen_return_url']);
        unset($_SESSION['kaizen_app_id']);
        unset($_SESSION['user_id']);
        unset($_SESSION['user']);
        unset($_SESSION['token']);
    }
    
    /**
     * Logout user and redirect to KaizenAuth logout
     */
    public function logout() {
        $this->clearSession();
        
        // Clear local session
        session_unset();
        session_destroy();
        
        // Redirect to KaizenAuth logout page
        $logoutUrl = $this->config['auth_domain'] . '/logout.php?' . http_build_query([
            'app_id' => $this->config['app_id'],
            'return_url' => $this->config['app_url'] ?? $this->getCurrentUrl()
        ]);
        
        header('Location: ' . $logoutUrl);
        exit;
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    public function requireAuthentication() {
        if (!$this->isAuthenticated()) {
            $this->redirectToLogin();
        }
        
        return true;
    }
    
    /**
     * Validate JWT token (basic validation)
     */
    public function validateToken() {
        return $this->isAuthenticated();
    }
    
    /**
     * Get current URL for return redirect
     */
    private function getCurrentUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        return $protocol . $host . $uri;
    }
    
    /**
     * Get debug information
     */
    public function getDebugInfo() {
        $jwt = $this->getJWTFromCookies();
        $decoded = $jwt ? $this->decodeJWT($jwt) : null;
        
        return [
            'has_jwt_cookie' => !empty($jwt),
            'jwt_valid' => !empty($decoded),
            'jwt_payload' => $decoded ? $decoded['payload'] : null,
            'is_authenticated' => $this->isAuthenticated(),
            'cookies' => $_COOKIE
        ];
    }
    
    /**
     * Validate app credentials
     */
    public function validateCredentials() {
        // This would typically make an API call to validate credentials
        // For now, we assume they're valid if properly configured
        return !empty($this->config['app_id']) && !empty($this->config['app_secret']);
    }
}
?>