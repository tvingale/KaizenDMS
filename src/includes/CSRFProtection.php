<?php
/**
 * CSRF Protection Helper
 * Simple CSRF token generation and validation
 */

class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';
    
    /**
     * Generate a new CSRF token
     */
    public static function generateToken($formName = 'default') {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        
        // Store token in session with timestamp
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        $_SESSION[self::$sessionKey][$formName] = [
            'token' => $token,
            'time' => time()
        ];
        
        // Clean old tokens (older than 1 hour)
        self::cleanOldTokens();
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token, $formName = 'default') {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (empty($token) || !isset($_SESSION[self::$sessionKey][$formName])) {
            return false;
        }
        
        $storedData = $_SESSION[self::$sessionKey][$formName];
        
        // Check if token is expired (1 hour)
        if (time() - $storedData['time'] > 3600) {
            unset($_SESSION[self::$sessionKey][$formName]);
            return false;
        }
        
        // Validate token
        $isValid = hash_equals($storedData['token'], $token);
        
        // Keep token valid for multiple uses within the session
        // Don't remove the token immediately - let it expire naturally
        // This prevents issues with page refreshes and back button
        // if ($isValid) {
        //     unset($_SESSION[self::$sessionKey][$formName]);
        // }
        
        return $isValid;
    }
    
    /**
     * Get CSRF token from POST data
     */
    public static function getTokenFromPost() {
        return $_POST[self::$tokenName] ?? '';
    }
    
    /**
     * Generate HTML input field for CSRF token
     */
    public static function getTokenField($formName = 'default') {
        $token = self::generateToken($formName);
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Clean old tokens from session
     */
    private static function cleanOldTokens() {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        
        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $formName => $data) {
            if ($currentTime - $data['time'] > 3600) { // 1 hour
                unset($_SESSION[self::$sessionKey][$formName]);
            }
        }
    }
    
    /**
     * Validate POST request with CSRF protection
     * Returns true if valid, false if invalid
     */
    public static function validatePOST($formName = 'default') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true; // Not a POST request
        }
        
        $token = self::getTokenFromPost();
        return self::validateToken($token, $formName);
    }
    
    /**
     * Handle CSRF validation failure
     */
    public static function handleCSRFFailure($redirectUrl = null) {
        if ($redirectUrl && !headers_sent()) {
            $_SESSION['flash_message'] = 'Security token invalid. Please try again.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            if (!headers_sent()) {
                http_response_code(403);
            }
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
}

// Convenience functions
function csrf_token($formName = 'default') {
    return CSRFProtection::getTokenField($formName);
}

function csrf_validate($formName = 'default') {
    return CSRFProtection::validatePOST($formName);
}
?>