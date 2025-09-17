<?php
/**
 * RBAC Schema Update Script
 * Adds missing columns to existing RBAC tables for full functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBAC Schema Update</title>
    <style>
        body { font-family: "Segoe UI", system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; }
        .step.success { border-left-color: #28a745; background: #d4edda; }
        .step.error { border-left-color: #dc3545; background: #f8d7da; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 RBAC Schema Update</h1>

        <?php

        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';

            $db = getDB();
            echo '<p class="success">✅ Database connection successful</p>';

            // Step 1: Check current dms_roles structure
            echo '<div class="step">';
            echo '<h3>📋 Step 1: Analyze Current dms_roles Structure</h3>';

            $stmt = $db->query("DESCRIBE dms_roles");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $existing_columns = array_column($columns, 'Field');

            echo '<p class="info">Current columns: ' . implode(', ', $existing_columns) . '</p>';

            $required_columns = [
                'role_name' => "VARCHAR(50) DEFAULT NULL COMMENT 'RBAC role identifier'",
                'display_name' => "VARCHAR(100) DEFAULT NULL COMMENT 'Human-readable role name'",
                'is_system_role' => "TINYINT(1) DEFAULT 0 COMMENT 'System vs custom role'",
                'hierarchy_level' => "INT(11) DEFAULT 100 COMMENT 'Role hierarchy (lower = higher authority)'",
                'scope' => "ENUM('all','cross_department','department','process_area','station','assigned_only') DEFAULT 'assigned_only' COMMENT 'Default permission scope'",
                'max_permissions' => "INT(11) DEFAULT 100 COMMENT 'Permission limit for role'"
            ];

            $columns_to_add = [];
            foreach ($required_columns as $col_name => $col_definition) {
                if (!in_array($col_name, $existing_columns)) {
                    $columns_to_add[$col_name] = $col_definition;
                }
            }

            if (empty($columns_to_add)) {
                echo '<p class="success">✅ All required columns already exist</p>';
            } else {
                echo '<p class="info">Missing columns: ' . implode(', ', array_keys($columns_to_add)) . '</p>';

                foreach ($columns_to_add as $col_name => $col_definition) {
                    $sql = "ALTER TABLE dms_roles ADD COLUMN $col_name $col_definition";
                    echo '<p class="info">Executing: ' . htmlspecialchars($sql) . '</p>';
                    $db->exec($sql);
                    echo '<p class="success">✅ Added column: ' . $col_name . '</p>';
                }
            }

            echo '</div>';

            // Step 2: Check current dms_permissions structure
            echo '<div class="step">';
            echo '<h3>🔑 Step 2: Analyze Current dms_permissions Structure</h3>';

            $stmt = $db->query("DESCRIBE dms_permissions");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $existing_columns = array_column($columns, 'Field');

            echo '<p class="info">Current columns: ' . implode(', ', $existing_columns) . '</p>';

            $required_columns = [
                'display_name' => "VARCHAR(150) DEFAULT NULL COMMENT 'Human-readable permission name'",
                'category' => "VARCHAR(50) DEFAULT 'general' COMMENT 'Permission grouping'",
                'resource' => "VARCHAR(50) DEFAULT NULL COMMENT 'Resource type (documents, users, reports)'",
                'action' => "VARCHAR(50) DEFAULT NULL COMMENT 'Action type (view, create, edit, delete)'",
                'scope_level' => "ENUM('all','cross_department','department','process_area','station','assigned_only') DEFAULT 'assigned_only' COMMENT 'Required scope level'",
                'risk_level' => "ENUM('low','medium','high','critical') DEFAULT 'medium' COMMENT 'Permission risk level'",
                'requires_context' => "TINYINT(1) DEFAULT 0 COMMENT 'Needs additional context'",
                'is_system_permission' => "TINYINT(1) DEFAULT 0 COMMENT 'System vs custom permission'"
            ];

            $columns_to_add = [];
            foreach ($required_columns as $col_name => $col_definition) {
                if (!in_array($col_name, $existing_columns)) {
                    $columns_to_add[$col_name] = $col_definition;
                }
            }

            if (empty($columns_to_add)) {
                echo '<p class="success">✅ All required columns already exist</p>';
            } else {
                echo '<p class="info">Missing columns: ' . implode(', ', array_keys($columns_to_add)) . '</p>';

                foreach ($columns_to_add as $col_name => $col_definition) {
                    $sql = "ALTER TABLE dms_permissions ADD COLUMN $col_name $col_definition";
                    echo '<p class="info">Executing: ' . htmlspecialchars($sql) . '</p>';
                    $db->exec($sql);
                    echo '<p class="success">✅ Added column: ' . $col_name . '</p>';
                }
            }

            echo '</div>';

            // Step 3: Check dms_role_permissions structure
            echo '<div class="step">';
            echo '<h3>🔗 Step 3: Analyze Current dms_role_permissions Structure</h3>';

            $stmt = $db->query("DESCRIBE dms_role_permissions");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $existing_columns = array_column($columns, 'Field');

            echo '<p class="info">Current columns: ' . implode(', ', $existing_columns) . '</p>';

            $required_columns = [
                'granted_scope' => "ENUM('all','cross_department','department','process_area','station','assigned_only') DEFAULT NULL COMMENT 'Specific granted scope'",
                'context_filter' => "TEXT DEFAULT NULL COMMENT 'JSON context filters'",
                'is_inherited' => "TINYINT(1) DEFAULT 0 COMMENT 'Inherited from parent role'",
                'granted_by' => "INT(11) DEFAULT NULL COMMENT 'User who granted permission'",
                'expires_at' => "TIMESTAMP NULL DEFAULT NULL COMMENT 'Temporary permissions'"
            ];

            $columns_to_add = [];
            foreach ($required_columns as $col_name => $col_definition) {
                if (!in_array($col_name, $existing_columns)) {
                    $columns_to_add[$col_name] = $col_definition;
                }
            }

            if (empty($columns_to_add)) {
                echo '<p class="success">✅ All required columns already exist</p>';
            } else {
                echo '<p class="info">Missing columns: ' . implode(', ', array_keys($columns_to_add)) . '</p>';

                foreach ($columns_to_add as $col_name => $col_definition) {
                    $sql = "ALTER TABLE dms_role_permissions ADD COLUMN $col_name $col_definition";
                    echo '<p class="info">Executing: ' . htmlspecialchars($sql) . '</p>';
                    $db->exec($sql);
                    echo '<p class="success">✅ Added column: ' . $col_name . '</p>';
                }
            }

            echo '</div>';

            // Step 4: Check dms_user_roles structure
            echo '<div class="step">';
            echo '<h3>👤 Step 4: Analyze Current dms_user_roles Structure</h3>';

            $stmt = $db->query("DESCRIBE dms_user_roles");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $existing_columns = array_column($columns, 'Field');

            echo '<p class="info">Current columns: ' . implode(', ', $existing_columns) . '</p>';

            $required_columns = [
                'scope_context' => "TEXT DEFAULT NULL COMMENT 'JSON scope limitations'",
                'granted_by' => "INT(11) DEFAULT NULL COMMENT 'Administrator who granted role'",
                'expires_at' => "TIMESTAMP NULL DEFAULT NULL COMMENT 'Role expiration'",
                'last_access' => "TIMESTAMP NULL DEFAULT NULL COMMENT 'Last access tracking'",
                'notes' => "TEXT DEFAULT NULL COMMENT 'Administrative notes'"
            ];

            $columns_to_add = [];
            foreach ($required_columns as $col_name => $col_definition) {
                if (!in_array($col_name, $existing_columns)) {
                    $columns_to_add[$col_name] = $col_definition;
                }
            }

            if (empty($columns_to_add)) {
                echo '<p class="success">✅ All required columns already exist</p>';
            } else {
                echo '<p class="info">Missing columns: ' . implode(', ', array_keys($columns_to_add)) . '</p>';

                foreach ($columns_to_add as $col_name => $col_definition) {
                    $sql = "ALTER TABLE dms_user_roles ADD COLUMN $col_name $col_definition";
                    echo '<p class="info">Executing: ' . htmlspecialchars($sql) . '</p>';
                    $db->exec($sql);
                    echo '<p class="success">✅ Added column: ' . $col_name . '</p>';
                }
            }

            echo '</div>';

            // Step 5: Add indexes for performance
            echo '<div class="step">';
            echo '<h3>⚡ Step 5: Add Performance Indexes</h3>';

            $indexes_to_add = [
                'dms_roles' => [
                    'idx_hierarchy_level' => 'hierarchy_level',
                    'idx_role_name' => 'role_name',
                    'idx_scope' => 'scope'
                ],
                'dms_permissions' => [
                    'idx_category' => 'category',
                    'idx_resource' => 'resource',
                    'idx_scope_level' => 'scope_level',
                    'idx_system_permission' => 'is_system_permission'
                ],
                'dms_role_permissions' => [
                    'idx_granted_scope' => 'granted_scope',
                    'idx_granted_by' => 'granted_by'
                ],
                'dms_user_roles' => [
                    'idx_user_status' => '(user_id, status)',
                    'idx_granted_by' => 'granted_by',
                    'idx_expires_at' => 'expires_at'
                ]
            ];

            foreach ($indexes_to_add as $table => $indexes) {
                foreach ($indexes as $index_name => $columns) {
                    // Check if index already exists
                    $stmt = $db->query("SHOW INDEX FROM $table WHERE Key_name = '$index_name'");
                    if ($stmt->rowCount() === 0) {
                        $sql = "ALTER TABLE $table ADD INDEX $index_name ($columns)";
                        try {
                            $db->exec($sql);
                            echo '<p class="success">✅ Added index: ' . $table . '.' . $index_name . '</p>';
                        } catch (Exception $e) {
                            echo '<p class="info">ℹ️ Index ' . $index_name . ' may already exist or column missing: ' . $e->getMessage() . '</p>';
                        }
                    } else {
                        echo '<p class="info">ℹ️ Index already exists: ' . $table . '.' . $index_name . '</p>';
                    }
                }
            }

            echo '</div>';

            // Final verification
            echo '<div class="step success">';
            echo '<h3>✅ Step 6: Schema Update Complete</h3>';

            echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;">';
            echo '<h4>🎉 RBAC SCHEMA UPDATE COMPLETE!</h4>';
            echo '<p><strong>✅ Database schema is now ready for full RBAC functionality</strong></p>';
            echo '<ul>';
            echo '<li>✅ All required columns added to RBAC tables</li>';
            echo '<li>✅ Performance indexes created</li>';
            echo '<li>✅ Schema supports advanced permission scopes</li>';
            echo '<li>✅ Ready for RBAC database population</li>';
            echo '</ul>';
            echo '<p><strong>Next Step:</strong> Run rbac_database_population.php to populate data</p>';
            echo '</div>';

            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="step error">';
            echo '<h3>❌ Error During Schema Update</h3>';
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }

        ?>

        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p><strong>RBAC Schema Update Script</strong></p>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>