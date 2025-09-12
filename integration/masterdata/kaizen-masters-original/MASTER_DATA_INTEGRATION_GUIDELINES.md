# KaizenMaster Integration Guidelines

## Overview
This document defines the principles, guidelines, and standards for creating and integrating master data within the KaizenFlow ecosystem. It ensures consistency, reusability, and maintainability across all modules.

---

## Core Principles

### 1. **Single Source of Truth**
- KaizenMaster is the authoritative source for all organizational master data
- No duplication of master data across modules
- Other modules consume master data, they don't create competing versions

### 2. **Loose Coupling with Tight Cohesion**
- Modules depend on master data structure, not implementation details
- Master data changes should not break existing modules
- Clear contracts and interfaces between modules

### 3. **Audit-Ready Integrity**
- Every change is tracked with user attribution
- No direct data deletion - use soft deletes and effective dating
- Complete audit trail for compliance requirements

### 4. **Stability First**
- Master data schema changes are carefully planned and communicated
- Backward compatibility maintained whenever possible
- ID values are never reused once assigned

---

## Master Data Creation Guidelines

### When to Create New Master Data

#### ✅ **CREATE when:**
1. **Cross-Module Usage**: Data will be used by 2+ modules
2. **Organizational Hierarchy**: Represents organizational structure (sites, departments)
3. **Business Partners**: External entities (customers, suppliers)
4. **Standard Categories**: Classification systems used enterprise-wide
5. **Compliance Requirements**: Data required for regulatory reporting

**Examples:**
- Task categories (used by TaskEngine, QualityModule, MaintenanceModule)
- Equipment types (used by MaintenanceModule, ProductionModule, QualityModule)
- Document types (used by DMS, QualityModule, AuditModule)

#### ❌ **DON'T CREATE when:**
1. **Module-Specific Logic**: Business rules specific to one module
2. **Transactional Data**: Orders, tasks, documents (content, not categories)
3. **Configuration Settings**: Module-specific configurations
4. **Derived Data**: Can be calculated from existing masters
5. **Temporary Classifications**: Short-lived or project-specific categories

**Examples (keep in your module):**
- Task assignment logic
- Specific workflow states
- Module configuration settings
- Performance metrics calculations

### Master Data Design Standards

#### **Naming Conventions**
```sql
-- Table Names
master_{entity_name}           -- master_sites, master_task_categories

-- Field Names  
{entity}_code                  -- site_code, customer_code
{entity}_name                  -- site_name, customer_name
{entity}_type                  -- supplier_type, document_type
{descriptive_field_name}       -- operating_hours_start, escalation_sla_hours

-- Enum Values
UPPERCASE_WITH_UNDERSCORES     -- 'MATERIAL_SUPPLIER', 'CRITICAL_TASK'
```

#### **Required Standard Fields**
Every master table MUST include:
```sql
-- Primary Key
id INT PRIMARY KEY AUTO_INCREMENT,

-- Business Keys
{entity}_code VARCHAR(20) NOT NULL UNIQUE,
{entity}_name VARCHAR(100) NOT NULL,

-- Status Management
is_active BOOLEAN NOT NULL DEFAULT 1,
valid_from DATE NULL,
valid_to DATE NULL,

-- Audit Trail
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_by INT NULL,
updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

-- Indexes
INDEX idx_{table}_code ({entity}_code),
INDEX idx_{table}_active (is_active),
INDEX idx_{table}_valid (valid_from, valid_to),
INDEX idx_{table}_created (created_at)
```

#### **Field Design Guidelines**

1. **Codes**: 
   - Always UPPERCASE
   - 3-20 characters
   - Meaningful abbreviations
   - Never reused once assigned

2. **Names**:
   - Human-readable display names
   - 100 characters maximum
   - No special characters or formatting

3. **Types/Categories**:
   - Use ENUM for fixed lists
   - VARCHAR for extensible categories
   - Document all possible values

4. **Relationships**:
   - Use foreign keys with proper constraints
   - Name consistently: `{parent_entity}_id`
   - Always reference the `id` field, not codes

#### **Data Types Standards**
```sql
-- Text Fields
{entity}_code       VARCHAR(20)     -- Codes
{entity}_name       VARCHAR(100)    -- Names  
description         TEXT            -- Long descriptions
email               VARCHAR(255)    -- Email addresses
phone               VARCHAR(20)     -- Phone numbers
address             TEXT            -- Addresses

-- Numeric Fields
id                  INT             -- Primary keys
{entity}_id         INT             -- Foreign keys
amounts             DECIMAL(15,2)   -- Currency/quantities
percentages         DECIMAL(5,2)    -- Percentages (0.00-100.00)
counts              INT             -- Whole number counts

-- Date/Time Fields
dates               DATE            -- Dates only
timestamps          TIMESTAMP       -- Date and time
duration_hours      INT             -- Duration in hours
duration_minutes    INT             -- Duration in minutes

-- Boolean Fields  
is_{attribute}      BOOLEAN         -- Status flags
has_{attribute}     BOOLEAN         -- Capability flags
requires_{action}   BOOLEAN         -- Requirement flags
```

---

## Integration Implementation Patterns

### Pattern 1: Direct Database Access (Recommended)

**Best for:** High-frequency access, real-time requirements, trusted modules

```php
// Example: Task module accessing sites
class TaskService {
    private $db;
    
    public function getAvailableSites($userId) {
        // Direct access to master_sites
        $stmt = $this->db->prepare("
            SELECT id, site_code, site_name, timezone
            FROM master_sites 
            WHERE is_active = 1 
            AND (valid_to IS NULL OR valid_to >= CURDATE())
            ORDER BY site_name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function createTask($taskData) {
        // Validate site exists and is active
        $stmt = $this->db->prepare("
            SELECT id FROM master_sites 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$taskData['site_id']]);
        
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException('Invalid site ID');
        }
        
        // Create task with valid site reference
        // ... task creation logic
    }
}
```

**Permissions Required:**
```json
{
  "required_permissions": [
    "sites.view",           // Read site data
    "task_categories.view", // Read task categories
    "task_categories.create" // Create new categories if needed
  ]
}
```

### Pattern 2: API-Based Access

**Best for:** External integrations, modules with different tech stacks

```javascript
// Example: External system integration
class MasterDataClient {
    constructor(baseUrl, apiKey) {
        this.baseUrl = baseUrl;
        this.apiKey = apiKey;
    }
    
    async getSites(filters = {}) {
        const response = await fetch(`${this.baseUrl}/api/master/sites`, {
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            },
            method: 'GET'
        });
        return response.json();
    }
    
    async createTaskCategory(categoryData) {
        const response = await fetch(`${this.baseUrl}/api/master/task-categories`, {
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            },
            method: 'POST',
            body: JSON.stringify(categoryData)
        });
        return response.json();
    }
}
```

### Pattern 3: Hybrid Approach

**Best for:** Complex scenarios with mixed requirements

```php
class HybridMasterDataService {
    
    // Read operations: Direct DB (fast)
    public function getMasterData($type, $filters = []) {
        return $this->directDbAccess->get($type, $filters);
    }
    
    // Write operations: API (validated, logged)
    public function createMasterData($type, $data) {
        return $this->apiClient->create($type, $data);
    }
    
    // Cached reads for reference data
    public function getCachedReferenceData($type) {
        return $this->cache->remember("master_{$type}", 3600, function() use ($type) {
            return $this->directDbAccess->get($type, ['is_active' => 1]);
        });
    }
}
```

---

## Permission and Security Model

### Permission Naming Convention
```
{resource}.{action}
{resource}.{action}.{scope}    // For granular permissions

Examples:
sites.view                     // View all sites
sites.create                  // Create new sites  
sites.edit                    // Edit existing sites
sites.delete                  // Delete (soft) sites
task_categories.view          // View task categories
task_categories.manage        // Full management access
```

### Access Control Implementation
```php
class MasterDataAccessControl {
    
    public function checkAccess($resource, $action, $userId) {
        $permission = "{$resource}.{$action}";
        
        // Check user has required permission
        if (!$this->userHasPermission($userId, $permission)) {
            throw new AccessDeniedException("Missing permission: {$permission}");
        }
        
        // Log access for audit
        $this->logAccess($userId, $resource, $action);
        
        return true;
    }
    
    public function validateMasterDataOperation($operation, $data, $userId) {
        // Validate permissions
        $this->checkAccess($data['resource'], $operation, $userId);
        
        // Validate data integrity
        $this->validateDataIntegrity($data);
        
        // Check business rules
        $this->validateBusinessRules($operation, $data);
        
        return true;
    }
}
```

---

## Change Management Process

### Schema Changes

#### **Minor Changes (Non-Breaking)**
- Adding nullable fields
- Adding new enum values
- Creating new indexes
- Adding new tables

**Process:**
1. Design and document change
2. Create migration script
3. Test on staging environment
4. Deploy during maintenance window
5. Update documentation
6. Notify affected modules

#### **Major Changes (Potentially Breaking)**
- Removing fields
- Changing data types
- Modifying relationships
- Renaming tables/fields

**Process:**
1. **Assessment Phase** (2 weeks)
   - Identify all affected modules
   - Design backward-compatible approach
   - Create migration and rollback plans
   
2. **Communication Phase** (1 week)
   - Notify all module teams
   - Schedule coordination meetings
   - Finalize migration timeline
   
3. **Implementation Phase** (1-2 weeks)
   - Deploy changes in staging
   - Module teams update and test
   - Coordinated production deployment
   
4. **Validation Phase** (1 week)
   - Monitor system performance
   - Validate data integrity
   - Confirm module functionality

### Data Changes

#### **Master Data Updates**
```sql
-- Good: Soft delete with audit trail
UPDATE master_sites 
SET is_active = 0, 
    valid_to = CURDATE(), 
    updated_by = :user_id,
    updated_at = NOW()
WHERE id = :site_id;

-- Bad: Hard delete
DELETE FROM master_sites WHERE id = :site_id;
```

#### **Bulk Data Changes**
```php
class BulkMasterDataUpdater {
    
    public function bulkUpdate($updates, $userId) {
        $this->db->beginTransaction();
        
        try {
            foreach ($updates as $update) {
                // Validate each change
                $this->validateUpdate($update);
                
                // Apply update with audit
                $this->applyUpdate($update, $userId);
                
                // Log activity
                $this->logActivity($update, $userId);
            }
            
            $this->db->commit();
            return ['status' => 'success', 'updated' => count($updates)];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
```

---

## Performance Guidelines

### Query Optimization

#### **Efficient Queries**
```sql
-- Good: Use indexes effectively
SELECT id, site_code, site_name 
FROM master_sites 
WHERE is_active = 1 
  AND valid_to IS NULL
ORDER BY site_name
LIMIT 50;

-- Good: Specific field selection
SELECT s.site_name, a.area_name
FROM master_sites s
JOIN master_areas a ON s.id = a.site_id
WHERE s.is_active = 1 AND a.is_active = 1;

-- Bad: SELECT * on large tables
SELECT * FROM master_suppliers;

-- Bad: No WHERE clause on large tables  
SELECT supplier_name FROM master_suppliers ORDER BY supplier_name;
```

#### **Caching Strategy**
```php
class MasterDataCache {
    
    // Cache stable reference data
    public function getStableData($type) {
        return $this->cache->remember("master_{$type}_stable", 3600, function() use ($type) {
            return $this->fetchStableData($type);
        });
    }
    
    // Don't cache frequently changing data
    public function getLiveData($type, $filters) {
        // Direct database query for live data
        return $this->fetchLiveData($type, $filters);
    }
    
    // Cache invalidation on updates
    public function invalidateCache($type) {
        $this->cache->forget("master_{$type}_stable");
        $this->cache->tags(["master_data", $type])->flush();
    }
}
```

### Indexing Strategy
```sql
-- Standard indexes for every master table
ALTER TABLE master_{entity} ADD INDEX idx_{entity}_code ({entity}_code);
ALTER TABLE master_{entity} ADD INDEX idx_{entity}_active (is_active);
ALTER TABLE master_{entity} ADD INDEX idx_{entity}_valid (valid_from, valid_to);
ALTER TABLE master_{entity} ADD INDEX idx_{entity}_created (created_at);

-- Specific indexes based on usage patterns
ALTER TABLE master_sites ADD INDEX idx_sites_timezone (timezone);
ALTER TABLE master_suppliers ADD INDEX idx_suppliers_type (supplier_type);
ALTER TABLE master_customers ADD INDEX idx_customers_country (country);

-- Composite indexes for common filters
ALTER TABLE master_areas ADD INDEX idx_areas_site_active (site_id, is_active);
```

---

## Testing and Validation Framework

### Unit Testing
```php
class MasterDataTest extends TestCase {
    
    public function testCreateSite() {
        $siteData = [
            'site_code' => 'TEST01',
            'site_name' => 'Test Site',
            'timezone' => 'Asia/Kolkata'
        ];
        
        $siteId = $this->masterDataService->createSite($siteData, $this->testUserId);
        
        $this->assertNotNull($siteId);
        $this->assertDatabaseHas('master_sites', [
            'id' => $siteId,
            'site_code' => 'TEST01',
            'is_active' => 1
        ]);
    }
    
    public function testSiteCodeUniqueness() {
        $this->expectException(DuplicateKeyException::class);
        
        // Create first site
        $this->masterDataService->createSite([
            'site_code' => 'DUP01',
            'site_name' => 'First Site'
        ], $this->testUserId);
        
        // Attempt to create duplicate - should fail
        $this->masterDataService->createSite([
            'site_code' => 'DUP01',  // Same code
            'site_name' => 'Second Site'
        ], $this->testUserId);
    }
}
```

### Integration Testing
```php
class ModuleIntegrationTest extends TestCase {
    
    public function testTaskModuleCanAccessSites() {
        // Create test site in KaizenMaster
        $siteId = $this->createTestSite();
        
        // Test TaskModule can read site
        $taskModule = new TaskModule();
        $sites = $taskModule->getAvailableSites();
        
        $this->assertContains($siteId, array_column($sites, 'id'));
    }
    
    public function testCascadingDeletes() {
        // Create site with areas
        $siteId = $this->createTestSiteWithAreas();
        
        // Soft delete site
        $this->masterDataService->deleteSite($siteId, $this->testUserId);
        
        // Verify areas are also deactivated
        $areas = $this->getAreasBySite($siteId);
        foreach ($areas as $area) {
            $this->assertEquals(0, $area['is_active']);
        }
    }
}
```

### Data Validation
```php
class MasterDataValidator {
    
    public function validateSite($data) {
        $rules = [
            'site_code' => 'required|string|max:20|unique:master_sites',
            'site_name' => 'required|string|max:100',
            'timezone' => 'nullable|string|timezone',
            'country' => 'nullable|string|max:50'
        ];
        
        return $this->validate($data, $rules);
    }
    
    public function validateBusinessRules($entity, $data) {
        switch ($entity) {
            case 'sites':
                return $this->validateSiteBusinessRules($data);
            case 'suppliers':
                return $this->validateSupplierBusinessRules($data);
            // ... other entities
        }
    }
    
    private function validateSiteBusinessRules($data) {
        // Custom business logic validation
        if (!empty($data['timezone']) && !in_array($data['timezone'], timezone_identifiers_list())) {
            throw new ValidationException('Invalid timezone specified');
        }
        
        return true;
    }
}
```

---

## Error Handling and Logging

### Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "Site code already exists",
    "field": "site_code",
    "details": {
      "existing_site_id": 123,
      "conflicting_code": "B-75"
    }
  },
  "request_id": "req_12345",
  "timestamp": "2025-01-15T10:30:00Z"
}
```

### Logging Strategy
```php
class MasterDataLogger {
    
    public function logDataChange($operation, $entity, $data, $userId) {
        $logEntry = [
            'timestamp' => now(),
            'operation' => $operation,
            'entity_type' => $entity,
            'entity_id' => $data['id'] ?? null,
            'user_id' => $userId,
            'changes' => $this->getChanges($operation, $data),
            'request_id' => request()->id
        ];
        
        // Log to activity table
        $this->logToDatabase($logEntry);
        
        // Log to external system for compliance
        $this->logToComplianceSystem($logEntry);
    }
    
    public function logPerformanceMetric($operation, $duration, $recordCount) {
        $this->metricsLogger->timing("master_data.{$operation}.duration", $duration);
        $this->metricsLogger->increment("master_data.{$operation}.count", $recordCount);
    }
}
```

---

## Migration and Deployment

### Database Migration Template
```sql
-- Migration: 2025_01_15_120000_add_task_categories_table.sql

-- Create new master table
CREATE TABLE master_task_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(100) NOT NULL,
    default_priority ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    escalation_time_hours INT NOT NULL DEFAULT 24,
    requires_approval BOOLEAN NOT NULL DEFAULT FALSE,
    description TEXT,
    
    -- Standard audit fields
    is_active BOOLEAN NOT NULL DEFAULT 1,
    valid_from DATE NULL,
    valid_to DATE NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT NULL,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_task_categories_code (category_code),
    INDEX idx_task_categories_active (is_active),
    INDEX idx_task_categories_valid (valid_from, valid_to)
);

-- Insert default permissions
INSERT INTO master_permissions (name, description, resource, action, is_active, created_at) VALUES
('task_categories.view', 'View task categories', 'task_categories', 'view', 1, NOW()),
('task_categories.create', 'Create task categories', 'task_categories', 'create', 1, NOW()),
('task_categories.edit', 'Edit task categories', 'task_categories', 'edit', 1, NOW()),
('task_categories.delete', 'Delete task categories', 'task_categories', 'delete', 1, NOW());

-- Seed initial data
INSERT INTO master_task_categories (category_code, category_name, default_priority, escalation_time_hours, created_by) VALUES
('MAINT', 'Maintenance Tasks', 'Medium', 48, 1),
('QUAL', 'Quality Control', 'High', 12, 1),
('PROD', 'Production Tasks', 'Medium', 24, 1),
('SAFE', 'Safety Tasks', 'Critical', 4, 1);
```

### Rollback Script
```sql
-- Rollback: 2025_01_15_120000_add_task_categories_table.sql

-- Remove permissions (cascade will handle role_permissions)
DELETE FROM master_permissions WHERE resource = 'task_categories';

-- Drop table (this will also drop all data)
DROP TABLE IF EXISTS master_task_categories;
```

### Deployment Checklist
```bash
#!/bin/bash
# Master Data Deployment Script

echo "=== KaizenMaster Deployment ==="

# 1. Backup current database
echo "Creating backup..."
mysqldump kaizenap_flow_db > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Run migrations
echo "Running migrations..."
php artisan migrate

# 3. Seed permissions
echo "Seeding permissions..."
php artisan db:seed --class=MasterPermissionsSeeder

# 4. Clear caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear

# 5. Validate deployment
echo "Validating deployment..."
php artisan master:validate

# 6. Run integration tests
echo "Running tests..."
php artisan test --testsuite=MasterDataIntegration

echo "=== Deployment Complete ==="
```

---

## Monitoring and Maintenance

### Performance Monitoring
```php
class MasterDataMonitor {
    
    public function checkPerformance() {
        $metrics = [
            'query_response_time' => $this->measureQueryTime(),
            'concurrent_users' => $this->getCurrentUserCount(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'database_connections' => $this->getDbConnectionCount()
        ];
        
        foreach ($metrics as $metric => $value) {
            $this->sendMetric("master_data.{$metric}", $value);
            
            if ($this->isThresholdExceeded($metric, $value)) {
                $this->sendAlert("Master data {$metric} exceeded threshold: {$value}");
            }
        }
    }
    
    public function validateDataIntegrity() {
        $checks = [
            'orphaned_records' => $this->checkOrphanedRecords(),
            'duplicate_codes' => $this->checkDuplicateCodes(),
            'invalid_references' => $this->checkInvalidReferences(),
            'audit_completeness' => $this->checkAuditCompleteness()
        ];
        
        return $checks;
    }
}
```

### Health Checks
```php
class MasterDataHealthCheck {
    
    public function runHealthCheck() {
        $results = [];
        
        // Database connectivity
        $results['database'] = $this->checkDatabaseHealth();
        
        // Data consistency
        $results['data_consistency'] = $this->checkDataConsistency();
        
        // Performance metrics
        $results['performance'] = $this->checkPerformanceMetrics();
        
        // Integration status
        $results['integrations'] = $this->checkIntegrationHealth();
        
        return $results;
    }
    
    public function generateHealthReport() {
        $health = $this->runHealthCheck();
        
        return [
            'timestamp' => now(),
            'overall_status' => $this->calculateOverallStatus($health),
            'checks' => $health,
            'recommendations' => $this->generateRecommendations($health)
        ];
    }
}
```

These comprehensive guidelines ensure that KaizenMaster maintains its role as the reliable, performant, and secure foundation for all KaizenFlow master data needs while enabling seamless integration across modules.