<?php
/**
 * KaizenMaster Integration Code Samples
 * Examples of how to integrate with KaizenMaster from other modules
 */

// ============================================================================
// PATTERN 1: DIRECT DATABASE ACCESS (RECOMMENDED FOR INTERNAL MODULES)
// ============================================================================

class KaizenMasterIntegration {
    private $db;
    private $accessControl;
    
    public function __construct($database, $accessControl) {
        $this->db = $database;
        $this->accessControl = $accessControl;
    }
    
    /**
     * Example: Get all active sites for task assignment
     */
    public function getAvailableSites($userId) {
        // Check permission first
        if (!$this->accessControl->hasPermission($userId, 'sites.view')) {
            throw new AccessDeniedException('Insufficient permissions to view sites');
        }
        
        $stmt = $this->db->prepare("
            SELECT id, site_code, site_name, timezone, 
                   operating_hours_start, operating_hours_end
            FROM master_sites 
            WHERE is_active = 1 
            AND (valid_to IS NULL OR valid_to >= CURDATE())
            ORDER BY site_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Example: Get departments for task routing
     */
    public function getDepartmentsForTaskRouting($siteId = null) {
        $sql = "
            SELECT id, department_code, department_name, description
            FROM master_departments 
            WHERE is_active = 1
        ";
        $params = [];
        
        if ($siteId) {
            // If you have site-department relationships
            $sql .= " AND site_id = ?";
            $params[] = $siteId;
        }
        
        $sql .= " ORDER BY department_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Example: Create new task category (if permissions allow)
     */
    public function createTaskCategory($categoryData, $userId) {
        // Validate permissions
        if (!$this->accessControl->hasPermission($userId, 'task_categories.create')) {
            throw new AccessDeniedException('Cannot create task categories');
        }
        
        // Validate required fields
        if (empty($categoryData['category_code']) || empty($categoryData['category_name'])) {
            throw new InvalidArgumentException('Category code and name are required');
        }
        
        try {
            $this->db->beginTransaction();
            
            // Insert new category
            $stmt = $this->db->prepare("
                INSERT INTO master_task_categories 
                (category_code, category_name, default_priority, escalation_time_hours, 
                 requires_approval, description, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())
            ");
            
            $stmt->execute([
                $categoryData['category_code'],
                $categoryData['category_name'], 
                $categoryData['default_priority'] ?? 'Medium',
                $categoryData['escalation_time_hours'] ?? 24,
                $categoryData['requires_approval'] ?? false,
                $categoryData['description'] ?? null,
                $userId
            ]);
            
            $categoryId = $this->db->lastInsertId();
            
            // Log activity (standard audit pattern)
            $this->logActivity($userId, 'CREATE', 'master_task_categories', $categoryId);
            
            $this->db->commit();
            return $categoryId;
            
        } catch (PDOException $e) {
            $this->db->rollback();
            if ($e->getCode() === '23000') { // Duplicate key
                throw new DuplicateKeyException('Task category code already exists');
            }
            throw $e;
        }
    }
    
    /**
     * Example: Validate foreign key references before creating records
     */
    public function validateReferences($data) {
        $errors = [];
        
        // Validate site_id if provided
        if (!empty($data['site_id'])) {
            $stmt = $this->db->prepare("
                SELECT id FROM master_sites 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$data['site_id']]);
            if (!$stmt->fetch()) {
                $errors[] = 'Invalid site ID or site is inactive';
            }
        }
        
        // Validate department_id if provided
        if (!empty($data['department_id'])) {
            $stmt = $this->db->prepare("
                SELECT id FROM master_departments 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$data['department_id']]);
            if (!$stmt->fetch()) {
                $errors[] = 'Invalid department ID or department is inactive';
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Validation failed: ' . implode(', ', $errors));
        }
        
        return true;
    }
    
    /**
     * Standard activity logging pattern
     */
    private function logActivity($userId, $action, $entityType, $entityId) {
        $stmt = $this->db->prepare("
            INSERT INTO master_activity_log 
            (user_id, action, entity_type, entity_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $action, $entityType, $entityId]);
    }
}

// ============================================================================
// PATTERN 2: API-BASED ACCESS (FOR EXTERNAL OR LOOSELY COUPLED MODULES)
// ============================================================================

class KaizenMasterApiClient {
    private $baseUrl;
    private $apiKey;
    private $timeout;
    
    public function __construct($baseUrl, $apiKey, $timeout = 30) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }
    
    /**
     * Example: Get sites via API
     */
    public function getSites($filters = []) {
        $url = $this->baseUrl . '/api/master/sites';
        if (!empty($filters)) {
            $url .= '?' . http_build_query($filters);
        }
        
        return $this->makeApiCall('GET', $url);
    }
    
    /**
     * Example: Create task category via API
     */
    public function createTaskCategory($categoryData) {
        $url = $this->baseUrl . '/api/master/task-categories';
        return $this->makeApiCall('POST', $url, $categoryData);
    }
    
    /**
     * Generic API call method
     */
    private function makeApiCall($method, $url, $data = null) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new ConnectionException('cURL Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new ApiException(
                $result['error']['message'] ?? 'API Error',
                $httpCode,
                $result
            );
        }
        
        return $result;
    }
}

// ============================================================================
// PATTERN 3: HYBRID APPROACH (MIXED USAGE)
// ============================================================================

class HybridMasterDataService {
    private $dbAccess;
    private $apiClient;
    private $cache;
    
    public function __construct($dbAccess, $apiClient, $cache) {
        $this->dbAccess = $dbAccess;
        $this->apiClient = $apiClient;
        $this->cache = $cache;
    }
    
    /**
     * Read operations: Direct DB for speed
     */
    public function getMasterData($type, $filters = []) {
        $cacheKey = "master_{$type}_" . md5(serialize($filters));
        
        return $this->cache->remember($cacheKey, 300, function() use ($type, $filters) {
            return $this->dbAccess->get($type, $filters);
        });
    }
    
    /**
     * Write operations: API for validation and audit
     */
    public function createMasterData($type, $data) {
        $result = $this->apiClient->create($type, $data);
        
        // Invalidate relevant caches
        $this->cache->tags(["master_data", $type])->flush();
        
        return $result;
    }
    
    /**
     * Reference data: Cached reads
     */
    public function getReferenceData($type) {
        return $this->cache->remember("ref_{$type}", 3600, function() use ($type) {
            return $this->dbAccess->get($type, ['is_active' => 1]);
        });
    }
}

// ============================================================================
// CACHING STRATEGIES
// ============================================================================

class MasterDataCache {
    private $cache;
    
    public function __construct($cache) {
        $this->cache = $cache;
    }
    
    /**
     * Cache stable reference data (sites, departments, etc.)
     */
    public function getStableData($type) {
        return $this->cache->remember("stable_{$type}", 3600, function() use ($type) {
            // Fetch from database
            return $this->fetchStableData($type);
        });
    }
    
    /**
     * Don't cache frequently changing data
     */
    public function getLiveData($type, $userId) {
        // Always fetch fresh for user-specific or frequently changing data
        return $this->fetchLiveData($type, $userId);
    }
    
    /**
     * Cache invalidation on updates
     */
    public function invalidateCache($type, $id = null) {
        // Clear specific item
        if ($id) {
            $this->cache->forget("item_{$type}_{$id}");
        }
        
        // Clear all items of this type
        $this->cache->tags(["master_data", $type])->flush();
        
        // Clear stable data cache
        $this->cache->forget("stable_{$type}");
    }
}

// ============================================================================
// ERROR HANDLING
// ============================================================================

class MasterDataException extends Exception {}
class AccessDeniedException extends MasterDataException {}
class ValidationException extends MasterDataException {}
class DuplicateKeyException extends MasterDataException {}
class ConnectionException extends MasterDataException {}
class ApiException extends MasterDataException {
    private $apiResponse;
    
    public function __construct($message, $code = 0, $apiResponse = null) {
        parent::__construct($message, $code);
        $this->apiResponse = $apiResponse;
    }
    
    public function getApiResponse() {
        return $this->apiResponse;
    }
}

// ============================================================================
// USAGE EXAMPLES
// ============================================================================

try {
    // Direct database access example
    $integration = new KaizenMasterIntegration($db, $accessControl);
    
    // Get sites for dropdown
    $sites = $integration->getAvailableSites($userId);
    
    // Create a new task category
    $newCategory = $integration->createTaskCategory([
        'category_code' => 'URGENT',
        'category_name' => 'Urgent Tasks',
        'default_priority' => 'High',
        'escalation_time_hours' => 4,
        'requires_approval' => true,
        'description' => 'High priority urgent tasks'
    ], $userId);
    
    echo "Created task category with ID: " . $newCategory;
    
} catch (AccessDeniedException $e) {
    echo "Access denied: " . $e->getMessage();
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
} catch (DuplicateKeyException $e) {
    echo "Duplicate data: " . $e->getMessage();
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage();
}

// API-based access example
try {
    $apiClient = new KaizenMasterApiClient(
        'https://masters.kaizen.com',
        'your-api-key-here'
    );
    
    $sites = $apiClient->getSites(['is_active' => 1]);
    $newCategory = $apiClient->createTaskCategory([
        'category_code' => 'API_TEST',
        'category_name' => 'API Test Category'
    ]);
    
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage();
    echo "Response: " . json_encode($e->getApiResponse());
}
?>