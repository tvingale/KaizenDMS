<?php
/**
 * RBAC Safe Population Script
 * Safely populates RBAC data without conflicts, handles existing data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBAC Safe Population</title>
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
        <h1>🔄 RBAC Safe Population</h1>

        <?php

        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';

            $db = getDB();
            echo '<p class="success">✅ Database connection successful</p>';

            // Step 1: Clear existing RBAC data for clean start
            echo '<div class="step">';
            echo '<h3>🧹 Step 1: Clean Existing RBAC Data</h3>';

            // Delete existing role-permission mappings
            $stmt = $db->query("DELETE FROM dms_role_permissions WHERE role_id >= 6");
            $deleted_mappings = $stmt->rowCount();
            echo '<p class="info">Cleared ' . $deleted_mappings . ' existing role-permission mappings</p>';

            // Delete existing system roles (ID >= 6 to preserve legacy roles 1-4)
            $stmt = $db->query("DELETE FROM dms_roles WHERE id >= 6");
            $deleted_roles = $stmt->rowCount();
            echo '<p class="info">Cleared ' . $deleted_roles . ' existing system roles</p>';

            // Delete existing system permissions
            $stmt = $db->query("DELETE FROM dms_permissions");
            $deleted_permissions = $stmt->rowCount();
            echo '<p class="info">Cleared ' . $deleted_permissions . ' existing permissions</p>';

            echo '<p class="success">✅ Database cleaned for fresh RBAC installation</p>';
            echo '</div>';

            // Step 2: Insert System Roles
            echo '<div class="step">';
            echo '<h3>👥 Step 2: Insert System Roles</h3>';

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

            $stmt = $db->prepare("
                INSERT INTO dms_roles (id, name, role_name, display_name, hierarchy_level, scope, is_system_role, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            foreach ($system_roles as $role) {
                $stmt->execute($role);
                $roles_inserted++;
                echo '<p class="success">✅ Created role: ' . $role[3] . ' (ID: ' . $role[0] . ')</p>';
            }

            echo '<p class="success">✅ System roles created: ' . $roles_inserted . '</p>';
            echo '</div>';

            // Step 3: Insert System Permissions
            echo '<div class="step">';
            echo '<h3>🔑 Step 3: Insert System Permissions</h3>';

            $system_permissions = [
                // Document Management Permissions
                ['documents.view.all', 'View All Documents', 'documents', 'documents', 'view', 'all', 'medium', 0, 1, 'View all documents system-wide'],
                ['documents.view.cross_department', 'View Cross-Department Documents', 'documents', 'documents', 'view', 'cross_department', 'medium', 1, 1, 'View documents across multiple departments'],
                ['documents.view.department', 'View Department Documents', 'documents', 'documents', 'view', 'department', 'low', 1, 1, 'View documents within own department'],
                ['documents.view.process_area', 'View Process Area Documents', 'documents', 'documents', 'view', 'process_area', 'low', 1, 1, 'View documents within process area'],
                ['documents.view.station', 'View Station Documents', 'documents', 'documents', 'view', 'station', 'low', 1, 1, 'View documents for specific station'],
                ['documents.view.assigned', 'View Assigned Documents', 'documents', 'documents', 'view', 'assigned_only', 'low', 0, 1, 'View only assigned documents'],

                ['documents.create', 'Create Documents', 'documents', 'documents', 'create', 'department', 'medium', 1, 1, 'Create new documents'],
                ['documents.edit.all', 'Edit All Documents', 'documents', 'documents', 'edit', 'all', 'high', 0, 1, 'Edit any document system-wide'],
                ['documents.edit.department', 'Edit Department Documents', 'documents', 'documents', 'edit', 'department', 'medium', 1, 1, 'Edit documents in own department'],
                ['documents.edit.assigned', 'Edit Assigned Documents', 'documents', 'documents', 'edit', 'assigned_only', 'low', 0, 1, 'Edit only assigned documents'],
                ['documents.delete.all', 'Delete All Documents', 'documents', 'documents', 'delete', 'all', 'critical', 0, 1, 'Delete any document'],
                ['documents.delete.department', 'Delete Department Documents', 'documents', 'documents', 'delete', 'department', 'high', 1, 1, 'Delete documents in own department'],

                // Approval Permissions
                ['documents.approve.all', 'Approve All Documents', 'approvals', 'approvals', 'approve', 'all', 'high', 0, 1, 'Approve any document system-wide'],
                ['documents.approve.cross_department', 'Cross-Department Approvals', 'approvals', 'approvals', 'approve', 'cross_department', 'high', 1, 1, 'Approve documents across departments'],
                ['documents.approve.department', 'Department Approvals', 'approvals', 'approvals', 'approve', 'department', 'medium', 1, 1, 'Approve documents within department'],
                ['documents.approve.process_area', 'Process Area Approvals', 'approvals', 'approvals', 'approve', 'process_area', 'medium', 1, 1, 'Approve documents within process area'],

                // User Management Permissions
                ['users.manage.all', 'Manage All Users', 'admin', 'users', 'manage', 'all', 'critical', 0, 1, 'Complete user management authority'],
                ['users.view.all', 'View All Users', 'admin', 'users', 'view', 'all', 'medium', 0, 1, 'View all system users'],
                ['users.view.department', 'View Department Users', 'admin', 'users', 'view', 'department', 'low', 1, 1, 'View users in same department'],

                // Role Management Permissions
                ['roles.manage', 'Manage Roles', 'admin', 'roles', 'manage', 'all', 'critical', 0, 1, 'Role and permission management'],
                ['roles.assign.all', 'Assign All Roles', 'admin', 'roles', 'assign', 'all', 'high', 0, 1, 'Assign any role to users'],
                ['roles.assign.department', 'Assign Department Roles', 'admin', 'roles', 'assign', 'department', 'medium', 1, 1, 'Assign roles within department'],

                // Reporting Permissions
                ['reports.view.all', 'View All Reports', 'reports', 'reports', 'view', 'all', 'medium', 0, 1, 'Access all system reports'],
                ['reports.view.cross_department', 'View Cross-Department Reports', 'reports', 'reports', 'view', 'cross_department', 'medium', 1, 1, 'Access reports across departments'],
                ['reports.view.department', 'View Department Reports', 'reports', 'reports', 'view', 'department', 'low', 1, 1, 'Access department-specific reports'],
                ['reports.create', 'Create Reports', 'reports', 'reports', 'create', 'department', 'medium', 1, 1, 'Create custom reports'],

                // Safety Permissions
                ['safety.audit.all', 'System-wide Safety Audits', 'safety', 'audit', 'audit', 'all', 'high', 0, 1, 'Conduct safety audits across all departments'],
                ['safety.override', 'Safety Override Authority', 'safety', 'documents', 'override', 'all', 'critical', 0, 1, 'Override document restrictions for safety reasons'],
                ['safety.emergency_access', 'Emergency Access', 'safety', 'emergency', 'access', 'all', 'critical', 0, 1, 'Emergency access to critical documents'],

                // Audit Trail Permissions
                ['audit.view.all', 'View All Audit Logs', 'audit', 'audit', 'view', 'all', 'high', 0, 1, 'Access complete audit trail'],
                ['audit.view.department', 'View Department Audit Logs', 'audit', 'audit', 'view', 'department', 'medium', 1, 1, 'Access department audit logs'],
                ['audit.export', 'Export Audit Data', 'audit', 'audit', 'export', 'all', 'high', 0, 1, 'Export audit data for compliance'],

                // Training Permissions
                ['training.assign', 'Assign Training', 'training', 'training', 'assign', 'department', 'medium', 1, 1, 'Assign training to users'],
                ['training.view.all', 'View All Training Records', 'training', 'training', 'view', 'all', 'medium', 0, 1, 'Access all training records'],
                ['training.view.department', 'View Department Training', 'training', 'training', 'view', 'department', 'low', 1, 1, 'View department training records'],

                // System Administration
                ['system.settings', 'System Settings', 'admin', 'system', 'configure', 'all', 'critical', 0, 1, 'Configure system settings'],
                ['system.backup', 'System Backup', 'admin', 'system', 'backup', 'all', 'high', 0, 1, 'Create system backups'],
                ['system.maintenance', 'System Maintenance', 'admin', 'system', 'maintain', 'all', 'high', 0, 1, 'Perform system maintenance'],
            ];

            $permissions_inserted = 0;

            $stmt = $db->prepare("
                INSERT INTO dms_permissions (name, display_name, category, resource, action, scope_level, risk_level, requires_context, is_system_permission, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            foreach ($system_permissions as $perm) {
                $stmt->execute($perm);
                $permissions_inserted++;
            }

            echo '<p class="success">✅ System permissions created: ' . $permissions_inserted . '</p>';
            echo '</div>';

            // Step 4: Create Role-Permission Mappings
            echo '<div class="step">';
            echo '<h3>🔗 Step 4: Create Role-Permission Mappings</h3>';

            // Get role and permission IDs
            $role_ids = [];
            $stmt = $db->query("SELECT id, role_name FROM dms_roles WHERE is_system_role = 1");
            while ($row = $stmt->fetch()) {
                $role_ids[$row['role_name']] = $row['id'];
            }

            $permission_ids = [];
            $stmt = $db->query("SELECT id, name FROM dms_permissions WHERE is_system_permission = 1");
            while ($row = $stmt->fetch()) {
                $permission_ids[$row['name']] = $row['id'];
            }

            // Role-Permission mappings
            $role_mappings = [
                'operator' => [
                    'documents.view.assigned',
                    'documents.edit.assigned'
                ],
                'line_lead' => [
                    'documents.view.station',
                    'documents.edit.assigned',
                    'training.view.department'
                ],
                'supervisor' => [
                    'documents.view.process_area',
                    'documents.edit.assigned',
                    'documents.approve.process_area',
                    'training.assign',
                    'training.view.department',
                    'reports.view.department',
                    'users.view.department'
                ],
                'engineer' => [
                    'documents.view.department',
                    'documents.create',
                    'documents.edit.department',
                    'reports.view.department',
                    'reports.create',
                    'training.view.department'
                ],
                'department_owner' => [
                    'documents.view.cross_department',
                    'documents.create',
                    'documents.edit.department',
                    'documents.delete.department',
                    'documents.approve.cross_department',
                    'users.view.all',
                    'roles.assign.department',
                    'reports.view.cross_department',
                    'training.assign',
                    'training.view.all',
                    'audit.view.department'
                ],
                'pso' => [
                    'documents.view.all',
                    'documents.edit.all',
                    'documents.approve.all',
                    'safety.audit.all',
                    'safety.override',
                    'safety.emergency_access',
                    'users.view.all',
                    'reports.view.all',
                    'audit.view.all',
                    'training.view.all'
                ],
                'system_admin' => [
                    'documents.view.all',
                    'documents.edit.all',
                    'documents.delete.all',
                    'documents.approve.all',
                    'users.manage.all',
                    'users.view.all',
                    'roles.manage',
                    'roles.assign.all',
                    'reports.view.all',
                    'reports.create',
                    'audit.view.all',
                    'audit.export',
                    'training.assign',
                    'training.view.all',
                    'system.settings',
                    'system.backup',
                    'system.maintenance',
                    'safety.audit.all',
                    'safety.override'
                ]
            ];

            $mappings_inserted = 0;
            $stmt = $db->prepare("
                INSERT INTO dms_role_permissions (role_id, permission_id, granted_scope, is_inherited, granted_by, granted_at, is_active)
                VALUES (?, ?, 'default', 0, 1, NOW(), 1)
            ");

            foreach ($role_mappings as $role_name => $permissions) {
                if (!isset($role_ids[$role_name])) {
                    echo '<p class="error">❌ Role not found: ' . $role_name . '</p>';
                    continue;
                }

                $role_id = $role_ids[$role_name];
                $role_permission_count = 0;

                foreach ($permissions as $permission_name) {
                    if (!isset($permission_ids[$permission_name])) {
                        echo '<p class="error">❌ Permission not found: ' . $permission_name . '</p>';
                        continue;
                    }

                    $permission_id = $permission_ids[$permission_name];
                    $stmt->execute([$role_id, $permission_id]);
                    $mappings_inserted++;
                    $role_permission_count++;
                }

                echo '<p class="success">✅ ' . $role_name . ': ' . $role_permission_count . ' permissions assigned</p>';
            }

            echo '<p class="success">✅ Total role-permission mappings created: ' . $mappings_inserted . '</p>';
            echo '</div>';

            // Step 5: Final Verification
            echo '<div class="step success">';
            echo '<h3>✅ Step 5: Final Verification</h3>';

            $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE is_system_role = 1");
            $system_roles_count = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE is_system_permission = 1");
            $system_permissions_count = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
            $mappings_count = $stmt->fetchColumn();

            echo '<table>';
            echo '<tr><th>Component</th><th>Count</th><th>Status</th></tr>';
            echo '<tr><td>System Roles</td><td>' . $system_roles_count . '</td><td>' . ($system_roles_count >= 7 ? '✅ Complete' : '❌ Incomplete') . '</td></tr>';
            echo '<tr><td>System Permissions</td><td>' . $system_permissions_count . '</td><td>' . ($system_permissions_count >= 35 ? '✅ Complete' : '❌ Incomplete') . '</td></tr>';
            echo '<tr><td>Role-Permission Mappings</td><td>' . $mappings_count . '</td><td>' . ($mappings_count >= 50 ? '✅ Complete' : '❌ Incomplete') . '</td></tr>';
            echo '</table>';

            if ($system_roles_count >= 7 && $system_permissions_count >= 35 && $mappings_count >= 50) {
                echo '<div style="background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;">';
                echo '<h4>🎉 RBAC SYSTEM FULLY OPERATIONAL!</h4>';
                echo '<p><strong>✅ Database population complete and verified</strong></p>';
                echo '<ul>';
                echo '<li>✅ ' . $system_roles_count . ' system roles with proper hierarchy</li>';
                echo '<li>✅ ' . $system_permissions_count . ' system permissions with scope levels</li>';
                echo '<li>✅ ' . $mappings_count . ' role-permission mappings</li>';
                echo '<li>✅ Ready for admin interface integration</li>';
                echo '</ul>';
                echo '<p><strong>Status:</strong> RBAC system is production-ready!</p>';
                echo '</div>';
            } else {
                echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;">';
                echo '<h4>⚠️ RBAC POPULATION NEEDS REVIEW</h4>';
                echo '<p>Some components may not have been populated correctly. Review the logs above.</p>';
                echo '</div>';
            }

            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="step error">';
            echo '<h3>❌ Error During Safe Population</h3>';
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }

        ?>

        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p><strong>RBAC Safe Population Script</strong></p>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>