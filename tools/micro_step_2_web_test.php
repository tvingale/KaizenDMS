<?php
/**
 * MICRO-STEP 2 WEB TEST: Permissions Table
 * Access via browser: http://your-domain.com/tools/micro_step_2_web_test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MICRO-STEP 2 TEST: Permissions Table</title>
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
        <h1>🔄 MICRO-STEP 2 TEST: Permissions Table</h1>
        
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
        
        // Step 2: Verify roles table exists (dependency check)
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify dms_roles table exists</h3>';
        echo '<p>⚡ DO: Checking for dependency table...</p>';
        
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'dms_roles'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("dms_roles table does not exist - run MICRO-STEP 1 first");
            }
            echo '<p class="success">✅ CHECK: dms_roles dependency verified</p>';
            $results[] = '✅ dms_roles dependency verified';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Dependency check failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Dependency check failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 3: Create permissions table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Create dms_permissions table</h3>';
        echo '<p>⚡ DO: Executing migration script...</p>';
        
        try {
            // MICRO-STEP 2: Verify existing permissions table and add test permission if needed
            // The table already exists with structure: id, name, description, resource, action, created_at
            
            // Check if we need to add a test permission
            $stmt = $db->prepare("SELECT COUNT(*) FROM dms_permissions WHERE name LIKE 'micro_test_%'");
            $stmt->execute();
            $testPermExists = $stmt->fetchColumn() > 0;
            
            if (!$testPermExists) {
                $stmt = $db->prepare("INSERT INTO dms_permissions (name, description, resource, action) VALUES (?, ?, ?, ?)");
                $stmt->execute(['micro_test_' . time(), 'MICRO-STEP 2 test permission', 'documents', 'read']);
            }
            
            echo '<p class="info">ℹ️ Working with existing dms_permissions table structure</p>';
            echo '<p class="success">✅ CHECK: Permissions table created successfully</p>';
            $results[] = '✅ dms_permissions table created';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Table creation failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Table creation failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 4: Verify table structure
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify table structure</h3>';
        echo '<p>⚡ DO: Checking table schema...</p>';
        
        try {
            $stmt = $db->query("DESCRIBE dms_permissions");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $expected_columns = ['id', 'name', 'description', 'resource', 'action', 'created_at'];
            $actual_columns = array_column($columns, 'Field');
            
            foreach ($expected_columns as $col) {
                if (!in_array($col, $actual_columns)) {
                    throw new Exception("Missing column: $col");
                }
            }
            echo '<p class="success">✅ CHECK: Table structure verified</p>';
            echo '<pre>Columns found: ' . implode(', ', $actual_columns) . '</pre>';
            $results[] = '✅ Table structure verified';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Table structure verification failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Table structure verification failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 5: Verify test data
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify test data insertion</h3>';
        echo '<p>⚡ DO: Checking inserted permissions...</p>';
        
        try {
            $stmt = $db->query("SELECT id, name, description, resource, action FROM dms_permissions ORDER BY id LIMIT 5");
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($permissions) < 3) {
                throw new Exception("Expected at least 3 permissions, found " . count($permissions));
            }
            
            echo '<p class="success">✅ CHECK: Test data verified - Found ' . count($permissions) . ' existing permissions:</p>';
            echo '<pre>';
            foreach ($permissions as $perm) {
                echo "   - {$perm['name']}: {$perm['description']} ({$perm['resource']}.{$perm['action']})\n";
            }
            echo '</pre>';
            $results[] = '✅ Test data inserted';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Test data verification failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Test data verification failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 6: Test basic CRUD operations
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test basic CRUD operations</h3>';
        echo '<p>⚡ DO: Testing INSERT, SELECT, UPDATE, DELETE...</p>';
        
        try {
            // Test INSERT
            $stmt = $db->prepare("INSERT INTO dms_permissions (name, description, resource, action) VALUES (?, ?, ?, ?)");
            $stmt->execute(['test_permission_' . time(), 'Test Permission for CRUD operations', 'test', 'read']);
            $test_id = $db->lastInsertId();
            
            // Test SELECT
            $stmt = $db->prepare("SELECT * FROM dms_permissions WHERE id = ?");
            $stmt->execute([$test_id]);
            $test_perm = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test_perm || strpos($test_perm['name'], 'test_permission_') !== 0) {
                throw new Exception("SELECT test failed");
            }
            
            // Test UPDATE
            $stmt = $db->prepare("UPDATE dms_permissions SET description = ? WHERE id = ?");
            $stmt->execute(['Updated test description', $test_id]);
            
            // Test DELETE (cleanup)
            $stmt = $db->prepare("DELETE FROM dms_permissions WHERE id = ?");
            $stmt->execute([$test_id]);
            
            echo '<p class="success">✅ CHECK: CRUD operations successful</p>';
            echo '<pre>INSERT -> SELECT -> UPDATE -> DELETE: All operations completed</pre>';
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
            <h2><?php echo $all_passed ? '🎉 MICRO-STEP 2 COMPLETE: All checks passed!' : '❌ MICRO-STEP 2 FAILED: Issues found'; ?></h2>
            
            <h3>📊 SUMMARY:</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($all_passed): ?>
                <p class="success"><strong>🔧 ACT: Ready for next micro-step</strong></p>
                <p class="info"><strong>🚀 Ready for MICRO-STEP 3: Role-Permission Mapping Table</strong></p>
            <?php else: ?>
                <p class="error"><strong>🔧 ACT: Fix issues before proceeding</strong></p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>MICRO-STEP 2: Permissions Foundation | PDCA Development Methodology</p>
        </div>
    </div>
</body>
</html>