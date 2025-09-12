<?php
/**
 * KaizenAuth API Client
 * Updated to use the new API Gateway system
 */

class KaizenAuthAPI {
    private $apiBase;
    private $appId;
    private $appSecret;
    
    public function __construct() {
        // NEW: Use api.php gateway instead of direct files
        $this->apiBase = KAIZEN_AUTH_URL . '/api.php';
        $this->appId = KAIZEN_APP_ID;
        $this->appSecret = KAIZEN_APP_SECRET;
        
        // KaizenAuth API uses app-level authentication only (no JWT token needed)
        if (empty($this->appId) || empty($this->appSecret)) {
            throw new Exception('Missing KaizenAuth app credentials');
        }
    }
    
    /**
     * Search users for Module Access Management
     * 
     * @param string $query Search query (name, email, username)
     * @param int $limit Number of results (max 50)
     * @return array Search results with users array
     */
    public function searchUsers($query, $limit = 10) {
        return $this->makeRequest('users/search', 'GET', [
            'query' => $query,
            'limit' => min($limit, 50) // API limit is 50
        ]);
    }
    
    /**
     * Get single user details
     * 
     * @param int $userId KaizenAuth user ID
     * @return array User details or error if not found
     */
    public function getUserDetails($userId) {
        return $this->makeRequest('users/detail', 'GET', [
            'user_id' => $userId
        ]);
    }
    
    /**
     * Get multiple users' details efficiently
     * 
     * @param array $userIds Array of user IDs (max 100)
     * @return array Bulk user details with 'users' and 'not_found' arrays
     */
    public function getBulkUserDetails($userIds) {
        return $this->makeRequest('users/bulk', 'POST', [
            'user_ids' => array_slice($userIds, 0, 100) // API limit is 100
        ]);
    }
    
    /**
     * Make HTTP request to KaizenAuth API Gateway
     * 
     * @param string $endpoint API endpoint (e.g., 'users/search')
     * @param string $method HTTP method
     * @param array|null $data Request data
     * @return array Response data with success flag and http_code
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        // Build URL with endpoint parameter (don't encode forward slashes in endpoint)
        $url = $this->apiBase . '?endpoint=' . $endpoint;
        
        // Add other GET parameters if method is GET
        if ($method === 'GET' && $data) {
            $url .= '&' . http_build_query($data);
        }
        
        $headers = [
            'X-App-ID: ' . $this->appId,
            'X-App-Secret: ' . $this->appSecret,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false, // For development only
            CURLOPT_SSL_VERIFYHOST => false, // For development only
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', // Bypass .htaccess cURL blocking
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'http_code' => 0,
                'error' => 'cURL Error: ' . $error,
                'data' => null
            ];
        }
        
        $decodedResponse = json_decode($response, true);
        
        $isSuccess = $httpCode >= 200 && $httpCode < 300;
        
        // If successful, extract the nested data for easier access
        $cleanData = null;
        if ($isSuccess && $decodedResponse && isset($decodedResponse['data'])) {
            $cleanData = $decodedResponse['data'];
        }
        
        return [
            'success' => $isSuccess,
            'http_code' => $httpCode,
            'error' => $httpCode >= 400 ? ($decodedResponse['error']['message'] ?? 'API Error') : null,
            'data' => $cleanData ?? $decodedResponse,
            'raw_response' => $response
        ];
    }
    
    /**
     * Format users for dropdown/selection UI
     * 
     * @param array $users Array of user objects from API
     * @return array Formatted users for HTML select options
     */
    public function formatUsersForSelection($users) {
        $formatted = [];
        foreach ($users as $user) {
            $formatted[] = [
                'id' => $user['id'],
                'name' => $user['name'] ?? $user['username'],
                'email' => $user['email'] ?? '',
                'mobile' => $user['mobile'] ?? '',
                'username' => $user['username'] ?? '',
                'display' => sprintf(
                    '%s (%s) - %s%s',
                    $user['name'] ?? $user['username'],
                    $user['email'] ?? 'No email',
                    $user['username'] ?? 'ID: ' . $user['id'],
                    !empty($user['mobile']) ? ' | ' . $user['mobile'] : ''
                )
            ];
        }
        return $formatted;
    }
    
    /**
     * Check if API is available and working
     * 
     * @return array Status with success flag and message
     */
    public function checkAPIStatus() {
        try {
            $result = $this->searchUsers('test', 1);
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'KaizenAuth API is working' : 'API error: ' . $result['error'],
                'http_code' => $result['http_code']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'API connection failed: ' . $e->getMessage(),
                'http_code' => 0
            ];
        }
    }
    
}
?>