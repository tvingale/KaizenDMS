<?php
/**
 * RBAC Minimal Population Script
 * Populates RBAC data using only existing table columns
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBAC Minimal Population</title>
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
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 RBAC Minimal Population</h1>

        <?php

        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';

            $db = getDB();
            echo '<p class="success">✅ Database connection successful</p>';

            // Step 1: Check table structures
            echo '<div class="step">';
            echo '<h3>🔍 Step 1: Analyze Table Structures</h3>';

            // Check dms_roles structure
            $stmt = $db->query("DESCRIBE dms_roles");
            $roles_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            echo '<p class="info">dms_roles columns: ' . implode(', ', $roles_columns) . '</p>';

            // Check dms_permissions structure
            $stmt = $db->query("DESCRIBE dms_permissions");
            $permissions_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            echo '<p class="info">dms_permissions columns: ' . implode(', ', $permissions_columns) . '</p>';

            // Check dms_role_permissions structure
            $stmt = $db->query("DESCRIBE dms_role_permissions");
            $role_permissions_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            echo '<p class="info">dms_role_permissions columns: ' . implode(', ', $role_permissions_columns) . '</p>';

            echo '</div>';

            // Step 2: Insert System Roles (using only existing columns)
            echo '<div class="step">';
            echo '<h3>👥 Step 2: Insert System Roles</h3>';

            // Build dynamic INSERT based on available columns
            $roles_insert_columns = [];
            $roles_placeholders = [];
            $roles_base_columns = ['id', 'name'];

            // Check for optional columns
            $optional_roles_columns = ['role_name', 'display_name', 'hierarchy_level', 'scope', 'is_system_role', 'description'];

            foreach ($roles_base_columns as $col) {
                if (in_array($col, $roles_columns)) {
                    $roles_insert_columns[] = $col;
                    $roles_placeholders[] = '?';
                }
            }

            foreach ($optional_roles_columns as $col) {
                if (in_array($col, $roles_columns)) {
                    $roles_insert_columns[] = $col;
                    $roles_placeholders[] = '?';
                }
            }

            if (in_array('created_at', $roles_columns)) {
                $roles_insert_columns[] = 'created_at';
                $roles_placeholders[] = 'NOW()';
            }

            $roles_sql = "INSERT INTO dms_roles (" . implode(', ', $roles_insert_columns) . ") VALUES (" . implode(', ', $roles_placeholders) . ")";
            echo '<p class="info">Roles SQL: ' . htmlspecialchars($roles_sql) . '</p>';

            $system_roles = [
                [7, 'operator', 'operator', 'Production Operator', 70, 'assigned_only', 1, 'Basic production operator with minimal permissions'],
                [8, 'line_lead', 'line_lead', 'Line Lead', 60, 'station', 1, 'Production line leader with team oversight'],
                [9, 'supervisor', 'supervisor', 'Production Supervisor', 50, 'process_area', 1, 'Process area supervisor with approval authority'],
                [10, 'engineer', 'engineer', 'Design Engineer', 40, 'department', 1, 'Technical specialist for document creation and editing'],
                [11, 'department_owner', 'department_owner', 'Department Owner', 30, 'cross_department', 1, 'Complete authority within assigned department'],
                [12, 'pso', 'pso', 'Product Safety Officer', 20, 'all', 1, 'Safety authority with cross-department access'],
                [13, 'system_admin', 'system_admin', 'System Administrator', 10, 'all', 1, 'Complete system administration authority']
            ];

            $roles_inserted = 0;
            $stmt = $db->prepare($roles_sql);

            foreach ($system_roles as $role) {
                // Build values array based on available columns
                $values = [];
                $col_index = 0;

                foreach ($roles_insert_columns as $col) {
                    if ($col === 'created_at') continue; // Skip NOW() columns

                    switch ($col) {
                        case 'id': $values[] = $role[0]; break;
                        case 'name': $values[] = $role[1]; break;
                        case 'role_name': $values[] = $role[2]; break;
                        case 'display_name': $values[] = $role[3]; break;
                        case 'hierarchy_level': $values[] = $role[4]; break;
                        case 'scope': $values[] = $role[5]; break;
                        case 'is_system_role': $values[] = $role[6]; break;
                        case 'description': $values[] = $role[7]; break;
                    }
                }

                $stmt->execute($values);
                $roles_inserted++;
                echo '<p class="success">✅ Created role: ' . $role[3] . ' (ID: ' . $role[0] . ')</p>';
            }

            echo '<p class="success">✅ System roles inserted: ' . $roles_inserted . '</p>';
            echo '</div>';

            // Step 3: Insert System Permissions (using only existing columns)
            echo '<div class="step">';
            echo '<h3>🔑 Step 3: Insert System Permissions</h3>';

            // Build dynamic INSERT for permissions
            $perms_insert_columns = [];
            $perms_placeholders = [];
            $perms_base_columns = ['name'];

            $optional_perms_columns = ['display_name', 'description', 'category', 'resource', 'action', 'scope_level', 'risk_level', 'requires_context', 'is_system_permission'];

            foreach ($perms_base_columns as $col) {
                if (in_array($col, $permissions_columns)) {
                    $perms_insert_columns[] = $col;
                    $perms_placeholders[] = '?';
                }
            }

            foreach ($optional_perms_columns as $col) {
                if (in_array($col, $permissions_columns)) {
                    $perms_insert_columns[] = $col;
                    $perms_placeholders[] = '?';
                }
            }

            if (in_array('created_at', $permissions_columns)) {
                $perms_insert_columns[] = 'created_at';
                $perms_placeholders[] = 'NOW()';
            }

            $perms_sql = "INSERT INTO dms_permissions (" . implode(', ', $perms_insert_columns) . ") VALUES (" . implode(', ', $perms_placeholders) . ")";
            echo '<p class="info">Permissions SQL: ' . htmlspecialchars($perms_sql) . '</p>';

            // Core permissions set
            $system_permissions = [
                ['documents.view.all', 'View All Documents', 'View all documents system-wide', 'documents', 'documents', 'view', 'all', 'medium', 0, 1],
                ['documents.view.department', 'View Department Documents', 'View documents within own department', 'documents', 'documents', 'view', 'department', 'low', 1, 1],
                ['documents.view.assigned', 'View Assigned Documents', 'View only assigned documents', 'documents', 'documents', 'view', 'assigned_only', 'low', 0, 1],
                ['documents.create', 'Create Documents', 'Create new documents', 'documents', 'documents', 'create', 'department', 'medium', 1, 1],
                ['documents.edit.all', 'Edit All Documents', 'Edit any document system-wide', 'documents', 'documents', 'edit', 'all', 'high', 0, 1],
                ['documents.edit.department', 'Edit Department Documents', 'Edit documents in own department', 'documents', 'documents', 'edit', 'department', 'medium', 1, 1],
                ['documents.edit.assigned', 'Edit Assigned Documents', 'Edit only assigned documents', 'documents', 'documents', 'edit', 'assigned_only', 'low', 0, 1],
                ['documents.approve.all', 'Approve All Documents', 'Approve any document system-wide', 'approvals', 'approvals', 'approve', 'all', 'high', 0, 1],
                ['documents.approve.department', 'Department Approvals', 'Approve documents within department', 'approvals', 'approvals', 'approve', 'department', 'medium', 1, 1],
                ['users.manage.all', 'Manage All Users', 'Complete user management authority', 'admin', 'users', 'manage', 'all', 'critical', 0, 1],
                ['users.view.all', 'View All Users', 'View all system users', 'admin', 'users', 'view', 'all', 'medium', 0, 1],
                ['roles.manage', 'Manage Roles', 'Role and permission management', 'admin', 'roles', 'manage', 'all', 'critical', 0, 1],
                ['reports.view.all', 'View All Reports', 'Access all system reports', 'reports', 'reports', 'view', 'all', 'medium', 0, 1],
                ['reports.view.department', 'View Department Reports', 'Access department-specific reports', 'reports', 'reports', 'view', 'department', 'low', 1, 1],
                ['safety.override', 'Safety Override Authority', 'Override document restrictions for safety reasons', 'safety', 'documents', 'override', 'all', 'critical', 0, 1],
                ['audit.view.all', 'View All Audit Logs', 'Access complete audit trail', 'audit', 'audit', 'view', 'all', 'high', 0, 1],
                ['system.settings', 'System Settings', 'Configure system settings', 'admin', 'system', 'configure', 'all', 'critical', 0, 1],
            ];

            $permissions_inserted = 0;
            $stmt = $db->prepare($perms_sql);

            foreach ($system_permissions as $perm) {
                $values = [];

                foreach ($perms_insert_columns as $col) {
                    if ($col === 'created_at') continue;

                    switch ($col) {
                        case 'name': $values[] = $perm[0]; break;
                        case 'display_name': $values[] = $perm[1]; break;
                        case 'description': $values[] = $perm[2]; break;
                        case 'category': $values[] = $perm[3]; break;
                        case 'resource': $values[] = $perm[4]; break;
                        case 'action': $values[] = $perm[5]; break;
                        case 'scope_level': $values[] = $perm[6]; break;
                        case 'risk_level': $values[] = $perm[7]; break;
                        case 'requires_context': $values[] = $perm[8]; break;
                        case 'is_system_permission': $values[] = $perm[9]; break;
                    }
                }

                $stmt->execute($values);
                $permissions_inserted++;
            }

            echo '<p class="success">✅ System permissions inserted: ' . $permissions_inserted . '</p>';
            echo '</div>';

            // Step 4: Create Role-Permission Mappings
            echo '<div class="step">';
            echo '<h3>🔗 Step 4: Create Role-Permission Mappings</h3>';

            // Get role and permission IDs
            $role_ids = [];
            $stmt = $db->query("SELECT id, name FROM dms_roles WHERE id >= 7");
            while ($row = $stmt->fetch()) {
                $role_ids[$row['name']] = $row['id'];
            }

            $permission_ids = [];
            $stmt = $db->query("SELECT id, name FROM dms_permissions");
            while ($row = $stmt->fetch()) {
                $permission_ids[$row['name']] = $row['id'];
            }

            // Simple role mappings
            $role_mappings = [
                'operator' => ['documents.view.assigned', 'documents.edit.assigned'],
                'line_lead' => ['documents.view.assigned', 'documents.edit.assigned', 'reports.view.department'],
                'supervisor' => ['documents.view.department', 'documents.edit.assigned', 'documents.approve.department', 'reports.view.department'],
                'engineer' => ['documents.view.department', 'documents.create', 'documents.edit.department', 'reports.view.department'],
                'department_owner' => ['documents.view.all', 'documents.create', 'documents.edit.department', 'documents.approve.department', 'users.view.all', 'reports.view.all'],
                'pso' => ['documents.view.all', 'documents.edit.all', 'documents.approve.all', 'safety.override', 'users.view.all', 'reports.view.all', 'audit.view.all'],
                'system_admin' => ['documents.view.all', 'documents.edit.all', 'documents.approve.all', 'users.manage.all', 'roles.manage', 'reports.view.all', 'audit.view.all', 'system.settings', 'safety.override']
            ];

            // Build role_permissions INSERT
            $rp_insert_columns = ['role_id', 'permission_id'];
            $rp_placeholders = ['?', '?'];

            if (in_array('granted_at', $role_permissions_columns)) {
                $rp_insert_columns[] = 'granted_at';
                $rp_placeholders[] = 'NOW()';
            }
            if (in_array('is_active', $role_permissions_columns)) {
                $rp_insert_columns[] = 'is_active';
                $rp_placeholders[] = '1';
            }

            $rp_sql = "INSERT INTO dms_role_permissions (" . implode(', ', $rp_insert_columns) . ") VALUES (" . implode(', ', $rp_placeholders) . ")";

            $mappings_inserted = 0;
            $stmt = $db->prepare($rp_sql);

            foreach ($role_mappings as $role_name => $permissions) {
                if (!isset($role_ids[$role_name])) continue;

                $role_id = $role_ids[$role_name];
                $role_permission_count = 0;

                foreach ($permissions as $permission_name) {
                    if (!isset($permission_ids[$permission_name])) continue;

                    $permission_id = $permission_ids[$permission_name];
                    $stmt->execute([$role_id, $permission_id]);
                    $mappings_inserted++;
                    $role_permission_count++;
                }

                echo '<p class="success">✅ ' . $role_name . ': ' . $role_permission_count . ' permissions assigned</p>';
            }

            echo '<p class="success">✅ Total role-permission mappings: ' . $mappings_inserted . '</p>';
            echo '</div>';

            // Final Verification
            echo '<div class="step success">';
            echo '<h3>✅ Final Verification</h3>';

            $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE id >= 7");
            $system_roles_count = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions");
            $system_permissions_count = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
            $mappings_count = $stmt->fetchColumn();

            echo '<table>';
            echo '<tr><th>Component</th><th>Count</th><th>Status</th></tr>';
            echo '<tr><td>System Roles</td><td>' . $system_roles_count . '</td><td>' . ($system_roles_count >= 7 ? '✅ Complete' : '❌ Incomplete') . '</td></tr>';
            echo '<tr><td>System Permissions</td><td>' . $system_permissions_count . '</td><td>' . ($system_permissions_count >= 15 ? '✅ Complete' : '❌ Incomplete') . '</td></tr>';
            echo '<tr><td>Role-Permission Mappings</td><td>' . $mappings_count . '</td><td>' . ($mappings_count >= 30 ? '✅ Complete' : '❌ Incomplete') . '</td></tr>';
            echo '</table>';

            echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;">';
            echo '<h4>🎉 RBAC MINIMAL POPULATION COMPLETE!</h4>';
            echo '<p><strong>✅ Database population successful with existing schema</strong></p>';
            echo '<ul>';
            echo '<li>✅ ' . $system_roles_count . ' system roles created</li>';
            echo '<li>✅ ' . $system_permissions_count . ' system permissions created</li>';
            echo '<li>✅ ' . $mappings_count . ' role-permission mappings created</li>';
            echo '<li>✅ RBAC system operational with current database structure</li>';
            echo '</ul>';
            echo '<p><strong>Status:</strong> Ready for admin interface development</p>';
            echo '</div>';

            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="step error">';
            echo '<h3>❌ Error During Minimal Population</h3>';
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }

        ?>

        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p><strong>RBAC Minimal Population Script</strong></p>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>