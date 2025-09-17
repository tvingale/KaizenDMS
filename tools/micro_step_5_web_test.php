<?php
/**
 * MICRO-STEP 5 WEB TEST: Upgrade Existing RBAC Tables
 * Access via browser: http://your-domain.com/tools/micro_step_5_web_test.php
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
    <title>MICRO-STEP 5 TEST: Upgrade Existing RBAC Tables</title>
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
        <h1>🔄 MICRO-STEP 5 TEST: Upgrade Existing RBAC Tables</h1>
        
        <?php
        
        // Step 1: Test database connection
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify database connection</h3>';
        echo '<p>⚡ DO: Loading database configuration...</p>';
        
        try {
            // Suppress session warnings temporarily
            $old_error_level = error_reporting(E_ALL & ~E_WARNING);
            
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';
            
            // Restore error reporting
            error_reporting($old_error_level);
            
            $db = getDB();
            echo '<p class="success">✅ CHECK: Database connection successful</p>';
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
        
        // Step 2: PDCA Dependency Validation - Verify MICRO-STEPS 1-4 completed
        echo '<div class="step">';
        echo '<h3>📋 PLAN: PDCA Dependency Validation</h3>';
        echo '<p>⚡ DO: Validating MICRO-STEPS 1-4 foundation...</p>';
        
        try {
            // Validate MICRO-STEP 1-4 foundation
            $required_tables = ['dms_roles', 'dms_permissions', 'dms_role_permissions', 'dms_user_roles'];
            
            foreach ($required_tables as $table) {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Required table $table does not exist - previous MICRO-STEPs incomplete");
                }
                
                $stmt = $db->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo '<p class="success">✅ ' . strtoupper(str_replace('dms_', '', $table)) . ': ' . $count . ' records</p>';
            }
            
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
        
        // Step 3: Upgrade dms_roles table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Upgrade dms_roles Table</h3>';
        echo '<p>⚡ DO: Adding RBAC requirement columns...</p>';
        
        try {
            // Check current structure
            $stmt = $db->query("DESCRIBE dms_roles");
            $current_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            echo '<p class="info">Current columns: ' . implode(', ', $current_columns) . '</p>';
            
            // Add required columns if they don't exist
            $required_additions = [
                'role_name' => "ADD COLUMN role_name VARCHAR(100)",
                'display_name' => "ADD COLUMN display_name VARCHAR(150)",
                'department_scope' => "ADD COLUMN department_scope JSON COMMENT 'Array of departments this role applies to'",
                'hierarchy_level' => "ADD COLUMN hierarchy_level ENUM('operator', 'lead', 'supervisor', 'manager', 'director', 'executive')",
                'can_be_combined_with' => "ADD COLUMN can_be_combined_with JSON COMMENT 'Compatible roles for multi-role users'",
                'is_system_role' => "ADD COLUMN is_system_role BOOLEAN DEFAULT FALSE COMMENT 'Cannot be deleted if true'",
                'created_by_user_id' => "ADD COLUMN created_by_user_id INT",
                'status' => "ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'"
            ];
            
            $added_columns = [];
            foreach ($required_additions as $column => $sql) {
                if (!in_array($column, $current_columns)) {
                    try {
                        $db->exec("ALTER TABLE dms_roles $sql");
                        $added_columns[] = $column;
                    } catch (Exception $e) {
                        // Column might already exist, check if it's just a different definition
                        if (strpos($e->getMessage(), 'Duplicate column') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            // Migrate existing data if needed
            if (in_array('role_name', $added_columns)) {
                $db->exec("UPDATE dms_roles SET role_name = name WHERE role_name IS NULL OR role_name = ''");
            }
            
            // Add unique constraint after data migration
            if (in_array('role_name', $added_columns)) {
                try {
                    $db->exec("ALTER TABLE dms_roles ADD CONSTRAINT unique_role UNIQUE (role_name)");
                } catch (Exception $e) {
                    // Constraint may already exist, continue
                    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                        throw $e;
                    }
                }
            }
            if (in_array('display_name', $added_columns)) {
                $db->exec("UPDATE dms_roles SET display_name = name WHERE display_name IS NULL");
            }
            
            echo '<p class="success">✅ CHECK: dms_roles table upgraded successfully</p>';
            if (!empty($added_columns)) {
                echo '<pre>Added columns: ' . implode(', ', $added_columns) . '</pre>';
            } else {
                echo '<pre>All required columns already present</pre>';
            }
            $results[] = '✅ dms_roles table upgraded';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: dms_roles upgrade failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ dms_roles upgrade failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 4: Upgrade dms_permissions table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Upgrade dms_permissions Table</h3>';
        echo '<p>⚡ DO: Adding RBAC requirement columns...</p>';
        
        try {
            // Check current structure
            $stmt = $db->query("DESCRIBE dms_permissions");
            $current_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            echo '<p class="info">Current columns: ' . implode(', ', $current_columns) . '</p>';
            
            // Add required columns if they don't exist
            $required_additions = [
                'permission_name' => "ADD COLUMN permission_name VARCHAR(150)",
                'display_name' => "ADD COLUMN display_name VARCHAR(150)",
                'category' => "ADD COLUMN category VARCHAR(50) COMMENT 'documents, users, reports, system, workflow'",
                'action' => "ADD COLUMN action VARCHAR(50) COMMENT 'create, edit, view, delete, approve'",
                'scope_qualifier' => "ADD COLUMN scope_qualifier VARCHAR(50) COMMENT 'all, department, process_area, assigned_only'",
                'risk_level' => "ADD COLUMN risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium'",
                'requires_approval' => "ADD COLUMN requires_approval BOOLEAN DEFAULT FALSE",
                'approved_by_role' => "ADD COLUMN approved_by_role VARCHAR(100)",
                'audit_logged' => "ADD COLUMN audit_logged BOOLEAN DEFAULT TRUE",
                'is_system_permission' => "ADD COLUMN is_system_permission BOOLEAN DEFAULT FALSE",
                'created_by_user_id' => "ADD COLUMN created_by_user_id INT",
                'status' => "ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'"
            ];
            
            $added_columns = [];
            foreach ($required_additions as $column => $sql) {
                if (!in_array($column, $current_columns)) {
                    try {
                        $db->exec("ALTER TABLE dms_permissions $sql");
                        $added_columns[] = $column;
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate column') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            // Migrate existing data if needed
            if (in_array('permission_name', $added_columns)) {
                $db->exec("UPDATE dms_permissions SET permission_name = name WHERE permission_name IS NULL OR permission_name = ''");
            }
            if (in_array('display_name', $added_columns)) {
                $db->exec("UPDATE dms_permissions SET display_name = name WHERE display_name IS NULL OR display_name = ''");
            }
            if (in_array('category', $added_columns)) {
                $db->exec("UPDATE dms_permissions SET category = resource WHERE category IS NULL OR category = ''");
            }
            
            // Add unique constraint after data migration
            if (in_array('permission_name', $added_columns)) {
                try {
                    $db->exec("ALTER TABLE dms_permissions ADD CONSTRAINT unique_permission UNIQUE (permission_name)");
                } catch (Exception $e) {
                    // Constraint may already exist, continue
                    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                        throw $e;
                    }
                }
            }
            
            echo '<p class="success">✅ CHECK: dms_permissions table upgraded successfully</p>';
            if (!empty($added_columns)) {
                echo '<pre>Added columns: ' . implode(', ', $added_columns) . '</pre>';
            } else {
                echo '<pre>All required columns already present</pre>';
            }
            $results[] = '✅ dms_permissions table upgraded';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: dms_permissions upgrade failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ dms_permissions upgrade failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 5: Upgrade dms_user_roles table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Upgrade dms_user_roles Table</h3>';
        echo '<p>⚡ DO: Adding RBAC requirement columns...</p>';
        
        try {
            // Check current structure
            $stmt = $db->query("DESCRIBE dms_user_roles");
            $current_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            echo '<p class="info">Current columns: ' . implode(', ', $current_columns) . '</p>';
            
            // Add required columns if they don't exist
            $required_additions = [
                'role_name' => "ADD COLUMN role_name VARCHAR(100)",
                'department' => "ADD COLUMN department VARCHAR(100)",
                'site_id' => "ADD COLUMN site_id INT",
                'process_areas' => "ADD COLUMN process_areas JSON COMMENT 'Array of process areas'",
                'assigned_by_user_id' => "ADD COLUMN assigned_by_user_id INT",
                'assignment_reason' => "ADD COLUMN assignment_reason TEXT",
                'effective_from' => "ADD COLUMN effective_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
                'effective_until' => "ADD COLUMN effective_until TIMESTAMP NULL COMMENT 'NULL = permanent'"
            ];
            
            $added_columns = [];
            foreach ($required_additions as $column => $sql) {
                if (!in_array($column, $current_columns)) {
                    try {
                        $db->exec("ALTER TABLE dms_user_roles $sql");
                        $added_columns[] = $column;
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate column') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            // Migrate existing data if needed
            if (in_array('role_name', $added_columns)) {
                $db->exec("
                    UPDATE dms_user_roles ur 
                    JOIN dms_roles r ON ur.role_id = r.id 
                    SET ur.role_name = r.name 
                    WHERE ur.role_name IS NULL
                ");
            }
            
            echo '<p class="success">✅ CHECK: dms_user_roles table upgraded successfully</p>';
            if (!empty($added_columns)) {
                echo '<pre>Added columns: ' . implode(', ', $added_columns) . '</pre>';
            } else {
                echo '<pre>All required columns already present</pre>';
            }
            $results[] = '✅ dms_user_roles table upgraded';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: dms_user_roles upgrade failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ dms_user_roles upgrade failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 6: Verify upgraded table structures
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify Upgraded Table Structures</h3>';
        echo '<p>⚡ DO: Testing upgraded table functionality...</p>';
        
        try {
            // Test upgraded dms_roles
            $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE role_name IS NOT NULL");
            $roles_with_names = $stmt->fetchColumn();
            
            // Test upgraded dms_permissions  
            $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE permission_name IS NOT NULL");
            $perms_with_names = $stmt->fetchColumn();
            
            // Test upgraded dms_user_roles
            $stmt = $db->query("SELECT COUNT(*) FROM dms_user_roles WHERE role_name IS NOT NULL");
            $user_roles_with_names = $stmt->fetchColumn();
            
            echo '<p class="success">✅ Roles with role_name: ' . $roles_with_names . '</p>';
            echo '<p class="success">✅ Permissions with permission_name: ' . $perms_with_names . '</p>';
            echo '<p class="success">✅ User-roles with role_name: ' . $user_roles_with_names . '</p>';
            
            echo '<p class="success">✅ CHECK: All table upgrades verified</p>';
            $results[] = '✅ Table upgrade verification passed';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Table verification failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Table verification failed';
            $all_passed = false;
            echo '</div>';
        }
        
        summary:
        ?>
        
        <div class="summary">
            <h2><?php echo $all_passed ? '🎉 MICRO-STEP 5 COMPLETE: All checks passed!' : '❌ MICRO-STEP 5 FAILED: Issues found'; ?></h2>
            
            <h3>📊 SUMMARY:</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($all_passed): ?>
                <p class="success"><strong>🔧 ACT: RBAC Tables Upgraded Successfully!</strong></p>
                <p class="info"><strong>🚀 Ready for MICRO-STEP 6: Create New RBAC Tables</strong></p>
                <div class="warning">
                    <strong>🎯 Milestone:</strong> Existing RBAC tables now match requirements schema!<br>
                    <strong>Next:</strong> Create missing RBAC tables for complete implementation.
                </div>
            <?php else: ?>
                <p class="error"><strong>🔧 ACT: Fix issues before proceeding to MICRO-STEP 6</strong></p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>MICRO-STEP 5: Upgrade Existing RBAC Tables | PDCA Development Methodology</p>
        </div>
    </div>
</body>
</html>

<?php
// Flush output buffer
ob_end_flush();
?>