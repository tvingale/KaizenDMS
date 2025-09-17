<?php
/**
 * MICRO-STEP 4 WEB TEST: User-Role Assignment Testing
 * Access via browser: http://your-domain.com/tools/micro_step_4_web_test.php
 */

// Start output buffering to prevent header issues
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables before including config
$all_passed = true;
$results = [];
$db = null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MICRO-STEP 4 TEST: User-Role Assignment</title>
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
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 MICRO-STEP 4 TEST: User-Role Assignment Testing</h1>
        
        <?php
        
        // Step 1: Test database connection (with session warning suppression)
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify database connection</h3>';
        echo '<p>⚡ DO: Loading database configuration (with session warning fixes)...</p>';
        
        try {
            // Suppress session warnings temporarily
            $old_error_level = error_reporting(E_ALL & ~E_WARNING);
            
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';
            
            // Restore error reporting
            error_reporting($old_error_level);
            
            $db = getDB();
            echo '<p class="success">✅ CHECK: Database connection successful (session warnings suppressed)</p>';
            $results[] = '✅ Database connection working';
            echo '</div>';
        } catch (Exception $e) {
            error_reporting($old_error_level);
            echo '<p class="error">❌ CHECK FAILED: Database connection failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Database connection failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 2: PDCA Dependency Validation - Verify connections to previous micro-steps
        echo '<div class="step">';
        echo '<h3>📋 PLAN: PDCA Dependency Validation</h3>';
        echo '<p>⚡ DO: Validating relationships with MICRO-STEPS 1, 2 & 3...</p>';
        
        try {
            // Validate MICRO-STEP 1 dependency (dms_roles)
            echo '<h4>🔗 Validating MICRO-STEP 1 Connection:</h4>';
            $stmt = $db->query("SELECT COUNT(*) as role_count FROM dms_roles");
            $role_count = $stmt->fetchColumn();
            if ($role_count < 3) {
                throw new Exception("Insufficient roles in dms_roles - MICRO-STEP 1 may be incomplete");
            }
            echo '<p class="success">✅ MICRO-STEP 1 Connection: ' . $role_count . ' roles available</p>';
            
            // Validate MICRO-STEP 2 dependency (dms_permissions)  
            echo '<h4>🔗 Validating MICRO-STEP 2 Connection:</h4>';
            $stmt = $db->query("SELECT COUNT(*) as perm_count FROM dms_permissions");
            $perm_count = $stmt->fetchColumn();
            if ($perm_count < 3) {
                throw new Exception("Insufficient permissions in dms_permissions - MICRO-STEP 2 may be incomplete");
            }
            echo '<p class="success">✅ MICRO-STEP 2 Connection: ' . $perm_count . ' permissions available</p>';
            
            // Validate MICRO-STEP 3 dependency (dms_role_permissions)
            echo '<h4>🔗 Validating MICRO-STEP 3 Connection:</h4>';
            $stmt = $db->query("SELECT COUNT(*) as mapping_count FROM dms_role_permissions");
            $mapping_count = $stmt->fetchColumn();
            if ($mapping_count < 3) {
                throw new Exception("Insufficient role-permission mappings - MICRO-STEP 3 may be incomplete");
            }
            
            // Test foreign key integrity from MICRO-STEP 3
            $stmt = $db->query("
                SELECT COUNT(*) as invalid_mappings
                FROM dms_role_permissions rp
                LEFT JOIN dms_roles r ON rp.role_id = r.id
                LEFT JOIN dms_permissions p ON rp.permission_id = p.id
                WHERE r.id IS NULL OR p.id IS NULL
            ");
            $invalid_mappings = $stmt->fetchColumn();
            
            if ($invalid_mappings > 0) {
                throw new Exception("Found $invalid_mappings invalid role-permission mappings - MICRO-STEP 3 connection broken");
            }
            
            echo '<p class="success">✅ MICRO-STEP 3 Connection: ' . $mapping_count . ' valid role-permission mappings</p>';
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
        
        // Step 3: PDCA Implementation Validation - Verify user-role mapping
        echo '<div class="step">';
        echo '<h3>📋 PLAN: PDCA Implementation Validation</h3>';
        echo '<p>⚡ DO: Validating user-role assignment implementation...</p>';
        
        try {
            // Check if user-role mapping table exists
            $stmt = $db->query("SHOW TABLES LIKE 'dms_user_roles'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("dms_user_roles table does not exist");
            }
            
            // Check table structure
            $stmt = $db->query("DESCRIBE dms_user_roles");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $expected_columns = ['user_id', 'role_id'];
            $actual_columns = array_column($columns, 'Field');
            
            foreach ($expected_columns as $col) {
                if (!in_array($col, $actual_columns)) {
                    throw new Exception("Missing column: $col");
                }
            }
            
            echo '<p class="success">✅ User-role mapping table structure verified</p>';
            echo '<pre>Columns found: ' . implode(', ', $actual_columns) . '</pre>';
            
            // CRITICAL: Test foreign key integrity with MICRO-STEP 1 (roles)
            echo '<h4>🔗 Testing Foreign Key Relationships:</h4>';
            
            $stmt = $db->query("
                SELECT COUNT(*) as invalid_role_refs
                FROM dms_user_roles ur
                LEFT JOIN dms_roles r ON ur.role_id = r.id
                WHERE r.id IS NULL
            ");
            $invalid_role_refs = $stmt->fetchColumn();
            
            if ($invalid_role_refs > 0) {
                throw new Exception("Found $invalid_role_refs invalid role references - MICRO-STEP 1 connection broken");
            }
            
            echo '<p class="success">✅ Foreign key integrity verified - All role references valid</p>';
            
            echo '<p class="success">✅ CHECK: PDCA Implementation validated</p>';
            $results[] = '✅ PDCA Implementation validated';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: User-role mapping verification failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ User-role mapping verification failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 4: Test additive permission model across all micro-steps
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test Complete RBAC Integration</h3>';
        echo '<p>⚡ DO: Testing end-to-end RBAC across all micro-steps...</p>';
        
        try {
            // Test complete permission aggregation: User → Roles → Permissions
            $stmt = $db->query("
                SELECT 
                    ur.user_id,
                    COUNT(DISTINCT ur.role_id) as role_count,
                    COUNT(DISTINCT p.id) as total_permissions
                FROM dms_user_roles ur
                JOIN dms_roles r ON ur.role_id = r.id
                JOIN dms_role_permissions rp ON r.id = rp.role_id
                JOIN dms_permissions p ON rp.permission_id = p.id
                WHERE ur.status = 'active'
                GROUP BY ur.user_id
                ORDER BY total_permissions DESC
                LIMIT 5
            ");
            $user_aggregation = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($user_aggregation) === 0) {
                throw new Exception("No active user-role assignments found");
            }
            
            echo '<p class="success">✅ Complete RBAC integration working - Found ' . count($user_aggregation) . ' users with role assignments:</p>';
            echo '<pre>';
            foreach ($user_aggregation as $ua) {
                echo "   - User {$ua['user_id']}: {$ua['role_count']} roles → {$ua['total_permissions']} total permissions\n";
            }
            echo '</pre>';
            
            // Test specific user permission lookup (the additive model in action)
            $test_user_id = $user_aggregation[0]['user_id'];
            $stmt = $db->prepare("
                SELECT DISTINCT 
                    p.name as permission_name,
                    p.resource,
                    p.action,
                    r.name as granted_by_role
                FROM dms_user_roles ur
                JOIN dms_roles r ON ur.role_id = r.id
                JOIN dms_role_permissions rp ON r.id = rp.role_id
                JOIN dms_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ? AND ur.status = 'active'
                ORDER BY p.resource, p.action, p.name
                LIMIT 10
            ");
            $stmt->execute([$test_user_id]);
            $user_permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<p class="info">Sample permissions for User ' . $test_user_id . ' (additive model in action):</p>';
            echo '<pre>';
            foreach ($user_permissions as $up) {
                echo "   - {$up['permission_name']} ({$up['resource']}.{$up['action']}) ← via {$up['granted_by_role']} role\n";
            }
            echo '</pre>';
            
            echo '<p class="success">✅ CHECK: Complete RBAC integration functional</p>';
            $results[] = '✅ Complete RBAC integration tested';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: RBAC integration test failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ RBAC integration test failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 5: Test user-role assignment CRUD operations
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test User-Role Assignment CRUD</h3>';
        echo '<p>⚡ DO: Testing INSERT, SELECT, UPDATE, DELETE on user-role assignments...</p>';
        
        try {
            // Get test data
            $stmt = $db->query("SELECT id FROM dms_roles WHERE name = 'user' LIMIT 1");
            $test_role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test_role) {
                throw new Exception("Could not find 'user' role for CRUD test");
            }
            
            $test_user_id = 9999; // Use a test user ID that won't conflict
            
            // Test INSERT
            $stmt = $db->prepare("INSERT IGNORE INTO dms_user_roles (user_id, role_id, status, granted_by, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$test_user_id, $test_role['id'], 'active', 1, 'MICRO-STEP 4 test assignment']);
            
            // Test SELECT
            $stmt = $db->prepare("SELECT * FROM dms_user_roles WHERE user_id = ? AND role_id = ?");
            $stmt->execute([$test_user_id, $test_role['id']]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assignment) {
                throw new Exception("SELECT test failed - assignment not found");
            }
            
            // Test UPDATE
            $stmt = $db->prepare("UPDATE dms_user_roles SET notes = ? WHERE user_id = ? AND role_id = ?");
            $stmt->execute(['Updated test assignment', $test_user_id, $test_role['id']]);
            
            // Test DELETE (cleanup)
            $stmt = $db->prepare("DELETE FROM dms_user_roles WHERE user_id = ? AND role_id = ?");
            $stmt->execute([$test_user_id, $test_role['id']]);
            
            echo '<p class="success">✅ CHECK: CRUD operations successful</p>';
            echo '<pre>INSERT → SELECT → UPDATE → DELETE: User-role assignment operations completed</pre>';
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
            <h2><?php echo $all_passed ? '🎉 MICRO-STEP 4 COMPLETE: All checks passed!' : '❌ MICRO-STEP 4 FAILED: Issues found'; ?></h2>
            
            <h3>📊 SUMMARY:</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($all_passed): ?>
                <p class="success"><strong>🔧 ACT: RBAC Foundation Complete!</strong></p>
                <p class="info"><strong>🚀 Ready for MICRO-STEP 5: Document Management Core</strong></p>
                <div class="warning">
                    <strong>🎯 Milestone Achieved:</strong> Complete RBAC system functional across all 4 micro-steps!<br>
                    <strong>Next Phase:</strong> Document management implementation can begin.
                </div>
            <?php else: ?>
                <p class="error"><strong>🔧 ACT: Fix issues before proceeding</strong></p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>MICRO-STEP 4: User-Role Assignment | PDCA Development Methodology</p>
        </div>
    </div>
</body>
</html>

<?php
// Flush output buffer
ob_end_flush();
?>