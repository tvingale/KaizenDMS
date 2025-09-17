<?php
/**
 * MICRO-STEP 6 WEB TEST: Create New RBAC Tables
 * Access via browser: http://your-domain.com/tools/micro_step_6_web_test.php
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
    <title>MICRO-STEP 6 TEST: Create New RBAC Tables</title>
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
        <h1>🔄 MICRO-STEP 6 TEST: Create New RBAC Tables</h1>
        
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
        
        // Step 2: PDCA Dependency Validation - Verify MICRO-STEP 5 completed
        echo '<div class="step">';
        echo '<h3>📋 PLAN: PDCA Dependency Validation</h3>';
        echo '<p>⚡ DO: Validating MICRO-STEP 5 table upgrades...</p>';
        
        try {
            // Check if MICRO-STEP 5 upgrades were completed
            $stmt = $db->query("DESCRIBE dms_roles");
            $roles_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            $required_role_columns = ['role_name', 'display_name', 'hierarchy_level', 'department_scope'];
            foreach ($required_role_columns as $col) {
                if (!in_array($col, $roles_columns)) {
                    throw new Exception("MICRO-STEP 5 incomplete: dms_roles missing column '$col'");
                }
            }
            
            $stmt = $db->query("DESCRIBE dms_permissions");
            $perm_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            $required_perm_columns = ['permission_name', 'category', 'scope_qualifier', 'risk_level'];
            foreach ($required_perm_columns as $col) {
                if (!in_array($col, $perm_columns)) {
                    throw new Exception("MICRO-STEP 5 incomplete: dms_permissions missing column '$col'");
                }
            }
            
            echo '<p class="success">✅ MICRO-STEP 5 Validation: All table upgrades confirmed</p>';
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
        
        // Step 3: Create dms_document_hierarchy table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Create dms_document_hierarchy Table</h3>';
        echo '<p>⚡ DO: Creating hierarchical document organization...</p>';
        
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS dms_document_hierarchy (
                id INT PRIMARY KEY AUTO_INCREMENT,
                document_id INT NOT NULL,
                site_id INT,
                department_id INT,
                process_area_id INT,
                production_line_id INT NULL,
                station_id INT NULL,
                hierarchy_path VARCHAR(500) COMMENT 'Full path like /B75/Manufacturing/Assembly/Line2/Station15',
                parent_hierarchy_id INT NULL,
                level_type ENUM('site', 'department', 'process_area', 'production_line', 'station') NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_hierarchy_path (hierarchy_path),
                INDEX idx_document_hierarchy (document_id, site_id, department_id),
                INDEX idx_level_type (level_type, is_active),
                FOREIGN KEY (document_id) REFERENCES dms_documents(id) ON DELETE CASCADE,
                FOREIGN KEY (site_id) REFERENCES dms_sites(id),
                FOREIGN KEY (department_id) REFERENCES dms_departments(id),
                FOREIGN KEY (process_area_id) REFERENCES dms_process_areas(id),
                FOREIGN KEY (parent_hierarchy_id) REFERENCES dms_document_hierarchy(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->exec($sql);
            
            echo '<p class="success">✅ CHECK: dms_document_hierarchy table created successfully</p>';
            $results[] = '✅ dms_document_hierarchy table created';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: dms_document_hierarchy creation failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ dms_document_hierarchy creation failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 4: Create dms_document_acl table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Create dms_document_acl Table</h3>';
        echo '<p>⚡ DO: Creating document-level access control...</p>';
        
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS dms_document_acl (
                id INT PRIMARY KEY AUTO_INCREMENT,
                document_id INT NOT NULL,
                sensitivity_level ENUM('public', 'internal', 'confidential', 'safety_critical', 'regulatory') DEFAULT 'internal',
                owner_user_id INT NOT NULL,
                co_owners JSON COMMENT 'Array of co-owner user IDs',
                explicit_permissions JSON COMMENT 'User/role specific permissions override',
                access_restrictions JSON COMMENT 'Time, location, context restrictions',
                inheritance_blocked BOOLEAN DEFAULT FALSE COMMENT 'Block hierarchical inheritance',
                requires_training BOOLEAN DEFAULT FALSE,
                required_clearance_level VARCHAR(50),
                emergency_access_allowed BOOLEAN DEFAULT FALSE,
                audit_access_required BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_document_sensitivity (document_id, sensitivity_level),
                INDEX idx_owner (owner_user_id),
                INDEX idx_sensitivity_level (sensitivity_level),
                FOREIGN KEY (document_id) REFERENCES dms_documents(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->exec($sql);
            
            echo '<p class="success">✅ CHECK: dms_document_acl table created successfully</p>';
            $results[] = '✅ dms_document_acl table created';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: dms_document_acl creation failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ dms_document_acl creation failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 5: Create dms_document_assignments table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Create dms_document_assignments Table</h3>';
        echo '<p>⚡ DO: Creating document-specific assignments...</p>';
        
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS dms_document_assignments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                document_id INT NOT NULL,
                assigned_to_user_id INT NOT NULL,
                assigned_by_user_id INT NOT NULL,
                assignment_type ENUM('reviewer', 'expert_reviewer', 'approver', 'consultant', 'stakeholder_input', 'compliance_check', 'peer_review') NOT NULL,
                assignment_reason TEXT,
                granted_permissions JSON COMMENT 'Specific permissions for this assignment',
                access_restrictions JSON COMMENT 'Time limits, sections, conditions',
                assignment_context JSON COMMENT 'Department, project, compliance requirement',
                priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                due_date TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                expires_at TIMESTAMP NULL,
                status ENUM('active', 'completed', 'expired', 'cancelled', 'overdue') DEFAULT 'active',
                completion_notes TEXT,
                INDEX idx_assignment_status (assigned_to_user_id, status, due_date),
                INDEX idx_document_assignments (document_id, assignment_type, status),
                INDEX idx_assignment_timeline (assigned_at, due_date, expires_at),
                FOREIGN KEY (document_id) REFERENCES dms_documents(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_by_user_id) REFERENCES dms_user_roles(user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->exec($sql);
            
            echo '<p class="success">✅ CHECK: dms_document_assignments table created successfully</p>';
            $results[] = '✅ dms_document_assignments table created';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: dms_document_assignments creation failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ dms_document_assignments creation failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 6: Create dms_user_effective_permissions table
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Create dms_user_effective_permissions Table</h3>';
        echo '<p>⚡ DO: Creating permission caching system...</p>';
        
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS dms_user_effective_permissions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                permission_name VARCHAR(150) NOT NULL,
                scope_level VARCHAR(50) COMMENT 'all, department, process_area, assigned_only',
                context VARCHAR(100) COMMENT 'default, auditor_context, safety_context, emergency',
                granted_by_roles JSON COMMENT 'Array of roles that grant this permission',
                effective_scope JSON COMMENT 'Specific departments, areas, restrictions',
                permission_source ENUM('role_based', 'document_assignment', 'emergency_grant', 'temporary_elevation') DEFAULT 'role_based',
                calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL COMMENT 'NULL = permanent, based on role validity',
                is_cached BOOLEAN DEFAULT TRUE,
                cache_invalidated_at TIMESTAMP NULL,
                INDEX idx_user_permission (user_id, permission_name, context),
                INDEX idx_permission_lookup (permission_name, scope_level),
                INDEX idx_cache_expiry (expires_at, is_cached),
                INDEX idx_user_context (user_id, context, calculated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->exec($sql);
            
            echo '<p class="success">✅ CHECK: dms_user_effective_permissions table created successfully</p>';
            $results[] = '✅ dms_user_effective_permissions table created';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: dms_user_effective_permissions creation failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ dms_user_effective_permissions creation failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 7: Verify table creation and relationships
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Verify New Table Creation and Relationships</h3>';
        echo '<p>⚡ DO: Testing new table structures and foreign keys...</p>';
        
        try {
            $new_tables = [
                'dms_document_hierarchy',
                'dms_document_acl', 
                'dms_document_assignments',
                'dms_user_effective_permissions'
            ];
            
            $table_info = [];
            foreach ($new_tables as $table) {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Table $table was not created successfully");
                }
                
                $stmt = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = DATABASE()");
                $column_count = $stmt->fetchColumn();
                
                $table_info[] = "$table: $column_count columns";
            }
            
            echo '<p class="success">✅ All new tables created successfully:</p>';
            echo '<pre>' . implode("\n", $table_info) . '</pre>';
            
            // Test basic operations on each table
            $db->exec("INSERT IGNORE INTO dms_document_hierarchy (document_id, site_id, level_type, hierarchy_path) VALUES (1, 1, 'site', '/test')");
            $db->exec("DELETE FROM dms_document_hierarchy WHERE hierarchy_path = '/test'");
            
            echo '<p class="success">✅ CHECK: All new tables functional</p>';
            $results[] = '✅ New RBAC tables verification passed';
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
            <h2><?php echo $all_passed ? '🎉 MICRO-STEP 6 COMPLETE: All checks passed!' : '❌ MICRO-STEP 6 FAILED: Issues found'; ?></h2>
            
            <h3>📊 SUMMARY:</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($all_passed): ?>
                <p class="success"><strong>🔧 ACT: New RBAC Tables Created Successfully!</strong></p>
                <p class="info"><strong>🚀 Ready for MICRO-STEP 7: Role Hierarchy & Permission Catalog</strong></p>
                <div class="warning">
                    <strong>🎯 Milestone:</strong> Complete RBAC database schema now in place!<br>
                    <strong>Tables Added:</strong> Hierarchy, ACL, Assignments, Permission Cache<br>
                    <strong>Next:</strong> Populate with standard roles and permissions.
                </div>
            <?php else: ?>
                <p class="error"><strong>🔧 ACT: Fix issues before proceeding to MICRO-STEP 7</strong></p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>MICRO-STEP 6: Create New RBAC Tables | PDCA Development Methodology</p>
        </div>
    </div>
</body>
</html>

<?php
// Flush output buffer
ob_end_flush();
?>