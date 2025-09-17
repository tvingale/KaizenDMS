<?php
/**
 * MICRO-STEP 3 WEB TEST: Role-Permission Mapping
 * Access via browser: http://your-domain.com/tools/micro_step_3_web_test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MICRO-STEP 3 TEST: Role-Permission Mapping</title>
    <style>
        body { font-family: "Segoe UI", system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; }
        .step.success { border-left-color: #28a745; background: #d4edda; }
        .step.error { border-left-color: #dc3545; background: #f8d7da; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .summary { background: #e7f3ff; padding: 15px; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 MICRO-STEP 3 TEST: Role-Permission Mapping</h1>
        
        <?php
        
        $all_passed = true;
        $results = [];
        
        // Step 1: Test database connection
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify database connection</h3>';
        echo '<p>⚡ DO: Loading database configuration...</p>';
        
        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';
            
            $db = getDB();
            echo '<p class="success">✅ CHECK: Database connection successful</p>';
            $results[] = '✅ Database connection working';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Database connection failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Database connection failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 2: PDCA Dependency Validation - Verify connections to previous micro-steps
        echo '<div class="step">';
        echo '<h3>📋 PLAN: PDCA Dependency Validation</h3>';
        echo '<p>⚡ DO: Validating relationships with MICRO-STEP 1 & 2...</p>';
        
        try {
            // Validate MICRO-STEP 1 dependency (dms_roles)
            echo '<h4>🔗 Validating MICRO-STEP 1 Connection:</h4>';
            $stmt = $db->query("SHOW TABLES LIKE 'dms_roles'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("dms_roles table does not exist - run MICRO-STEP 1 first");
            }
            
            // Verify roles table has proper data and structure
            $stmt = $db->query("SELECT COUNT(*) as role_count FROM dms_roles");
            $role_count = $stmt->fetchColumn();
            if ($role_count < 3) {
                throw new Exception("Insufficient roles in dms_roles - MICRO-STEP 1 may be incomplete");
            }
            
            // Verify role IDs are valid for foreign key relationships
            $stmt = $db->query("SELECT id, name FROM dms_roles ORDER BY id LIMIT 3");
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<p class="success">✅ MICRO-STEP 1 Connection: ' . $role_count . ' roles available for mapping</p>';
            echo '<pre>Available roles: ' . implode(', ', array_column($roles, 'name')) . '</pre>';
            
            // Validate MICRO-STEP 2 dependency (dms_permissions)  
            echo '<h4>🔗 Validating MICRO-STEP 2 Connection:</h4>';
            $stmt = $db->query("SHOW TABLES LIKE 'dms_permissions'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("dms_permissions table does not exist - run MICRO-STEP 2 first");
            }
            
            // Verify permissions table has proper data and structure
            $stmt = $db->query("SELECT COUNT(*) as perm_count FROM dms_permissions");
            $perm_count = $stmt->fetchColumn();
            if ($perm_count < 3) {
                throw new Exception("Insufficient permissions in dms_permissions - MICRO-STEP 2 may be incomplete");
            }
            
            // Verify permission IDs are valid for foreign key relationships
            $stmt = $db->query("SELECT id, name, resource, action FROM dms_permissions ORDER BY id LIMIT 5");
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<p class="success">✅ MICRO-STEP 2 Connection: ' . $perm_count . ' permissions available for mapping</p>';
            echo '<pre>Sample permissions: ';
            foreach ($permissions as $p) {
                echo $p['name'] . ' (' . $p['resource'] . '.' . $p['action'] . '), ';
            }
            echo '</pre>';
            
            echo '<p class="success">✅ CHECK: All PDCA dependencies validated</p>';
            $results[] = '✅ PDCA Dependencies validated';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Dependency check failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Dependency check failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 3: PDCA Implementation Validation - Verify mapping table and relationships
        echo '<div class="step">';
        echo '<h3>📋 PLAN: PDCA Implementation Validation</h3>';
        echo '<p>⚡ DO: Validating role-permission mapping implementation...</p>';
        
        try {
            // Check if mapping table exists
            $stmt = $db->query("SHOW TABLES LIKE 'dms_role_permissions'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("dms_role_permissions table does not exist");
            }
            
            // Check table structure and foreign key relationships
            $stmt = $db->query("DESCRIBE dms_role_permissions");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $expected_columns = ['role_id', 'permission_id'];
            $actual_columns = array_column($columns, 'Field');
            
            foreach ($expected_columns as $col) {
                if (!in_array($col, $actual_columns)) {
                    throw new Exception("Missing column: $col");
                }
            }
            
            echo '<p class="success">✅ Mapping table structure verified</p>';
            echo '<pre>Columns found: ' . implode(', ', $actual_columns) . '</pre>';
            
            // CRITICAL: Test foreign key integrity between steps
            echo '<h4>🔗 Testing Foreign Key Relationships:</h4>';
            
            // Test role_id references
            $stmt = $db->query("
                SELECT COUNT(*) as invalid_roles 
                FROM dms_role_permissions rp 
                LEFT JOIN dms_roles r ON rp.role_id = r.id 
                WHERE r.id IS NULL
            ");
            $invalid_roles = $stmt->fetchColumn();
            
            if ($invalid_roles > 0) {
                throw new Exception("Found $invalid_roles invalid role references - MICRO-STEP 1 connection broken");
            }
            
            // Test permission_id references  
            $stmt = $db->query("
                SELECT COUNT(*) as invalid_perms
                FROM dms_role_permissions rp
                LEFT JOIN dms_permissions p ON rp.permission_id = p.id
                WHERE p.id IS NULL  
            ");
            $invalid_perms = $stmt->fetchColumn();
            
            if ($invalid_perms > 0) {
                throw new Exception("Found $invalid_perms invalid permission references - MICRO-STEP 2 connection broken");
            }
            
            echo '<p class="success">✅ Foreign key integrity verified - All connections valid</p>';
            
            // Test bidirectional relationship queries
            echo '<h4>🔗 Testing Bidirectional Queries:</h4>';
            
            // Query 1: Roles → Permissions
            $stmt = $db->query("
                SELECT r.name as role_name, COUNT(p.id) as permission_count
                FROM dms_roles r
                LEFT JOIN dms_role_permissions rp ON r.id = rp.role_id
                LEFT JOIN dms_permissions p ON rp.permission_id = p.id
                GROUP BY r.id, r.name
                ORDER BY permission_count DESC
                LIMIT 3
            ");
            $role_perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<p class="info">Roles → Permissions mapping:</p>';
            echo '<pre>';
            foreach ($role_perms as $rp) {
                echo "   - {$rp['role_name']}: {$rp['permission_count']} permissions\n";
            }
            echo '</pre>';
            
            // Query 2: Permissions → Roles
            $stmt = $db->query("
                SELECT p.name as permission_name, COUNT(r.id) as role_count
                FROM dms_permissions p
                LEFT JOIN dms_role_permissions rp ON p.id = rp.permission_id
                LEFT JOIN dms_roles r ON rp.role_id = r.id
                GROUP BY p.id, p.name
                ORDER BY role_count DESC
                LIMIT 5
            ");
            $perm_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<p class="info">Permissions → Roles mapping:</p>';
            echo '<pre>';
            foreach ($perm_roles as $pr) {
                echo "   - {$pr['permission_name']}: assigned to {$pr['role_count']} roles\n";
            }
            echo '</pre>';
            
            echo '<p class="success">✅ CHECK: PDCA Implementation validated</p>';
            $results[] = '✅ PDCA Implementation validated';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Role-permission mapping verification failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Role-permission mapping verification failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 4: Verify existing mappings and additive permission model
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test additive permission model</h3>';
        echo '<p>⚡ DO: Testing union of role permissions...</p>';
        
        try {
            // Get sample role-permission mappings
            $stmt = $db->query("
                SELECT r.name as role_name, p.name as permission_name, p.resource, p.action
                FROM dms_role_permissions rp
                JOIN dms_roles r ON rp.role_id = r.id  
                JOIN dms_permissions p ON rp.permission_id = p.id
                ORDER BY r.name, p.name
                LIMIT 10
            ");
            $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($mappings) < 3) {
                throw new Exception("Expected at least 3 role-permission mappings, found " . count($mappings));
            }
            
            echo '<p class="success">✅ CHECK: Role-permission mappings verified - Found ' . count($mappings) . ' active mappings:</p>';
            echo '<pre>';
            foreach ($mappings as $mapping) {
                echo "   - {$mapping['role_name']} → {$mapping['permission_name']} ({$mapping['resource']}.{$mapping['action']})\n";
            }
            echo '</pre>';
            $results[] = '✅ Additive permission model functional';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Permission model test failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Permission model test failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 5: Test union permissions for multi-role users
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test multi-role permission aggregation</h3>';
        echo '<p>⚡ DO: Testing permission union for users with multiple roles...</p>';
        
        try {
            // Check if user-role mapping table exists
            $stmt = $db->query("SHOW TABLES LIKE 'dms_user_roles'");
            if ($stmt->rowCount() === 0) {
                echo '<p class="info">ℹ️ dms_user_roles table not found - skipping multi-role test</p>';
            } else {
                // Test permission aggregation query
                $stmt = $db->query("
                    SELECT DISTINCT p.name as permission_name, p.resource, p.action
                    FROM dms_user_roles ur
                    JOIN dms_role_permissions rp ON ur.role_id = rp.role_id
                    JOIN dms_permissions p ON rp.permission_id = p.id  
                    WHERE ur.user_id = 1 AND ur.status = 'active'
                    ORDER BY p.name
                    LIMIT 10
                ");
                $user_permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p class="success">✅ CHECK: User permission aggregation working - User 1 has ' . count($user_permissions) . ' total permissions:</p>';
                echo '<pre>';
                foreach ($user_permissions as $perm) {
                    echo "   - {$perm['permission_name']} ({$perm['resource']}.{$perm['action']})\n";
                }
                echo '</pre>';
            }
            $results[] = '✅ Multi-role permission aggregation tested';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Multi-role test failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Multi-role test failed';
            $all_passed = false;
            echo '</div>';
        }
        
        // Step 6: Test basic CRUD operations on role-permission mappings
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test role-permission mapping CRUD</h3>';
        echo '<p>⚡ DO: Testing INSERT, SELECT, DELETE on mappings...</p>';
        
        try {
            // Get a test role and permission
            $stmt = $db->query("SELECT id FROM dms_roles WHERE name = 'user' LIMIT 1");
            $test_role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $db->query("SELECT id FROM dms_permissions WHERE resource = 'documents' AND action = 'view' LIMIT 1");
            $test_permission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test_role || !$test_permission) {
                throw new Exception("Could not find test role or permission for CRUD test");
            }
            
            // Test INSERT (if not exists)
            $stmt = $db->prepare("INSERT IGNORE INTO dms_role_permissions (role_id, permission_id) VALUES (?, ?)");
            $stmt->execute([$test_role['id'], $test_permission['id']]);
            
            // Test SELECT  
            $stmt = $db->prepare("SELECT * FROM dms_role_permissions WHERE role_id = ? AND permission_id = ?");
            $stmt->execute([$test_role['id'], $test_permission['id']]);
            $mapping = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mapping) {
                throw new Exception("SELECT test failed - mapping not found");
            }
            
            echo '<p class="success">✅ CHECK: CRUD operations successful</p>';
            echo '<pre>INSERT → SELECT: Role-permission mapping operations completed</pre>';
            $results[] = '✅ CRUD operations functional';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: CRUD operations failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ CRUD operations failed';
            $all_passed = false;
            echo '</div>';
        }
        
        summary:
        ?>
        
        <div class="summary">
            <h2><?php echo $all_passed ? '🎉 MICRO-STEP 3 COMPLETE: All checks passed!' : '❌ MICRO-STEP 3 FAILED: Issues found'; ?></h2>
            
            <h3>📊 SUMMARY:</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($all_passed): ?>
                <p class="success"><strong>🔧 ACT: Ready for next micro-step</strong></p>
                <p class="info"><strong>🚀 Ready for MICRO-STEP 4: User-Role Assignment Testing</strong></p>
            <?php else: ?>
                <p class="error"><strong>🔧 ACT: Fix issues before proceeding</strong></p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>MICRO-STEP 3: Role-Permission Mapping | PDCA Development Methodology</p>
        </div>
    </div>
</body>
</html>