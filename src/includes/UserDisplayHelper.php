<?php
/**
 * User Display Helper
 * Handles user name display with support for deleted user detection
 * Works with or without KaizenAuth APIs
 */

class UserDisplayHelper {
    private static $instance = null;
    private $cache = [];
    private $cacheTimeout = 300; // 5 minutes
    private $apiEnabled = false; // Will be enabled when KaizenAuth provides APIs
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check if KaizenAuth APIs are available
        $this->apiEnabled = defined('KAIZEN_AUTH_API_ENABLED') && KAIZEN_AUTH_API_ENABLED;
    }
    
    /**
     * Get display name for a user, handling deleted users
     * 
     * @param int $userId KaizenAuth user ID
     * @param string $fallbackName Fallback name from local data
     * @param array $fallbackData Additional fallback data (email, username, etc.)
     * @return array ['name' => string, 'is_deleted' => bool, 'data' => array]
     */
    public function getUserDisplayInfo($userId, $fallbackName = '', $fallbackData = []) {
        if (!$userId) {
            return [
                'name' => $fallbackName ?: 'Unknown User',
                'is_deleted' => false,
                'data' => $fallbackData
            ];
        }
        
        // Check cache first
        $cacheKey = "user_{$userId}";
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() - $cached['timestamp'] < $this->cacheTimeout) {
                return $cached['data'];
            }
        }
        
        $result = null;
        
        if ($this->apiEnabled) {
            // Try KaizenAuth API
            $result = $this->fetchUserFromAPI($userId, $fallbackName, $fallbackData);
        } else {
            // Use fallback data (current behavior)
            $result = [
                'name' => $fallbackName ?: "User #{$userId}",
                'is_deleted' => false,
                'data' => array_merge(['id' => $userId, 'name' => $fallbackName], $fallbackData)
            ];
        }
        
        // Cache the result
        $this->cache[$cacheKey] = [
            'timestamp' => time(),
            'data' => $result
        ];
        
        return $result;
    }
    
    /**
     * Get display name as simple string
     */
    public function getUserDisplayName($userId, $fallbackName = '') {
        $info = $this->getUserDisplayInfo($userId, $fallbackName);
        return $info['name'];
    }
    
    /**
     * Get multiple users' display info efficiently
     */
    public function getBulkUserDisplayInfo($userIds, $fallbackData = []) {
        if (empty($userIds)) {
            return [];
        }
        
        $results = [];
        $uncachedIds = [];
        
        // Check cache for each user
        foreach ($userIds as $userId) {
            $cacheKey = "user_{$userId}";
            if (isset($this->cache[$cacheKey])) {
                $cached = $this->cache[$cacheKey];
                if (time() - $cached['timestamp'] < $this->cacheTimeout) {
                    $results[$userId] = $cached['data'];
                    continue;
                }
            }
            $uncachedIds[] = $userId;
        }
        
        // Fetch uncached users
        if (!empty($uncachedIds)) {
            if ($this->apiEnabled) {
                $apiResults = $this->fetchBulkUsersFromAPI($uncachedIds, $fallbackData);
                $results = array_merge($results, $apiResults);
            } else {
                // Use fallback data for uncached users
                foreach ($uncachedIds as $userId) {
                    $fallback = $fallbackData[$userId] ?? [];
                    $results[$userId] = [
                        'name' => $fallback['name'] ?? "User #{$userId}",
                        'is_deleted' => false,
                        'data' => array_merge(['id' => $userId], $fallback)
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Fetch user from KaizenAuth API
     */
    private function fetchUserFromAPI($userId, $fallbackName, $fallbackData) {
        try {
            // KaizenAuth API uses app-level authentication only (no Bearer token needed)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => KAIZEN_AUTH_URL . "/api.php?endpoint=users/detail&user_id={$userId}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'X-App-ID: ' . KAIZEN_APP_ID,
                    'X-App-Secret: ' . KAIZEN_APP_SECRET,
                    'Content-Type: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => false, // For development only
                CURLOPT_SSL_VERIFYHOST => false, // For development only
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 404) {
                // User not found - deleted user
                return [
                    'name' => '(Deleted User)',
                    'is_deleted' => true,
                    'data' => ['id' => $userId, 'deleted' => true]
                ];
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data['success'] && isset($data['data']['user'])) {
                    $user = $data['data']['user'];
                    return [
                        'name' => $user['name'] ?? $user['username'] ?? $fallbackName,
                        'is_deleted' => false,
                        'data' => $user
                    ];
                }
            }
            
            // API call failed, use fallback
            return [
                'name' => $fallbackName ?: "User #{$userId}",
                'is_deleted' => false,
                'data' => array_merge(['id' => $userId, 'name' => $fallbackName], $fallbackData)
            ];
            
        } catch (Exception $e) {
            error_log("UserDisplayHelper API error for user $userId: " . $e->getMessage());
            // Return fallback on error
            return [
                'name' => $fallbackName ?: "User #{$userId}",
                'is_deleted' => false,
                'data' => array_merge(['id' => $userId, 'name' => $fallbackName], $fallbackData)
            ];
        }
    }
    
    /**
     * Fetch multiple users from KaizenAuth API
     */
    private function fetchBulkUsersFromAPI($userIds, $fallbackData) {
        try {
            // KaizenAuth API uses app-level authentication only (no Bearer token needed)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => KAIZEN_AUTH_URL . "/api.php?endpoint=users/bulk",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    'X-App-ID: ' . KAIZEN_APP_ID,
                    'X-App-Secret: ' . KAIZEN_APP_SECRET,
                    'Content-Type: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => false, // For development only
                CURLOPT_SSL_VERIFYHOST => false, // For development only
                CURLOPT_POSTFIELDS => json_encode(['user_ids' => $userIds])
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if ($data['success']) {
                    $results = [];
                    
                    // Process found users
                    foreach ($data['data']['users'] as $user) {
                        $userId = $user['id'];
                        $results[$userId] = [
                            'name' => $user['name'] ?? $user['username'] ?? "User #{$userId}",
                            'is_deleted' => false,
                            'data' => $user
                        ];
                        
                        // Cache individual user
                        $this->cache["user_{$userId}"] = [
                            'timestamp' => time(),
                            'data' => $results[$userId]
                        ];
                    }
                    
                    // Process not found users (deleted)
                    foreach ($data['data']['not_found'] as $userId) {
                        $results[$userId] = [
                            'name' => '(Deleted User)',
                            'is_deleted' => true,
                            'data' => ['id' => $userId, 'deleted' => true]
                        ];
                        
                        // Cache deleted user status
                        $this->cache["user_{$userId}"] = [
                            'timestamp' => time(),
                            'data' => $results[$userId]
                        ];
                    }
                    
                    return $results;
                }
            }
            
            // API call failed, use fallback for all users
            $results = [];
            foreach ($userIds as $userId) {
                $fallback = $fallbackData[$userId] ?? [];
                $results[$userId] = [
                    'name' => $fallback['name'] ?? "User #{$userId}",
                    'is_deleted' => false,
                    'data' => array_merge(['id' => $userId], $fallback)
                ];
            }
            return $results;
            
        } catch (Exception $e) {
            error_log("UserDisplayHelper bulk API error: " . $e->getMessage());
            // Return fallback for all users on error
            $results = [];
            foreach ($userIds as $userId) {
                $fallback = $fallbackData[$userId] ?? [];
                $results[$userId] = [
                    'name' => $fallback['name'] ?? "User #{$userId}",
                    'is_deleted' => false,
                    'data' => array_merge(['id' => $userId], $fallback)
                ];
            }
            return $results;
        }
    }
    
    /**
     * Clear cache for a specific user
     */
    public function clearUserCache($userId) {
        unset($this->cache["user_{$userId}"]);
    }
    
    /**
     * Clear all cached data
     */
    public function clearAllCache() {
        $this->cache = [];
    }
    
    /**
     * Manually cache user information
     * Useful for pre-populating with known user data (e.g., from JWT)
     */
    public function cacheUserInfo($userId, $userData) {
        $this->cache[$userId] = [
            'name' => $userData['name'] ?? $userData['username'] ?? "User #{$userId}",
            'is_deleted' => false,
            'data' => array_merge(['id' => $userId], $userData),
            'cached_at' => time()
        ];
    }
    
    /**
     * Get HTML for user display with appropriate styling
     */
    public function getUserDisplayHTML($userId, $fallbackName = '', $showBadge = true) {
        $info = $this->getUserDisplayInfo($userId, $fallbackName);
        
        if ($info['is_deleted']) {
            return '<span class="text-muted user-deleted">' . htmlspecialchars($info['name']) . '</span>';
        }
        
        $html = '<span class="user-name">' . htmlspecialchars($info['name']) . '</span>';
        
        if ($showBadge && isset($info['data']['is_active']) && !$info['data']['is_active']) {
            $html .= ' <span class="badge badge-secondary badge-sm ml-1">Inactive</span>';
        }
        
        return $html;
    }
    
    /**
     * Enable API connectivity (called when KaizenAuth APIs are ready)
     */
    public static function enableAPI($enabled = true) {
        $instance = self::getInstance();
        $instance->apiEnabled = $enabled;
        if ($enabled) {
            $instance->clearAllCache(); // Clear cache when enabling API
        }
    }
    
    /**
     * Check if APIs are currently enabled
     */
    public function isAPIEnabled() {
        return $this->apiEnabled;
    }
}

// Convenience functions for global use
function getUserDisplayName($userId, $fallbackName = '') {
    return UserDisplayHelper::getInstance()->getUserDisplayName($userId, $fallbackName);
}

function getUserDisplayHTML($userId, $fallbackName = '', $showBadge = true) {
    return UserDisplayHelper::getInstance()->getUserDisplayHTML($userId, $fallbackName, $showBadge);
}

function getUserDisplayInfo($userId, $fallbackName = '', $fallbackData = []) {
    return UserDisplayHelper::getInstance()->getUserDisplayInfo($userId, $fallbackName, $fallbackData);
}

function getUserEmail($userId, $fallback = '') {
    $info = UserDisplayHelper::getInstance()->getUserDisplayInfo($userId, '', []);
    return $info['data']['email'] ?? $fallback;
}

function getUserMobile($userId, $fallback = '') {
    $info = UserDisplayHelper::getInstance()->getUserDisplayInfo($userId, '', []);
    return $info['data']['mobile'] ?? $fallback;
}
?>