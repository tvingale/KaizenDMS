<?php
/**
 * RBAC Database Population Script
 * Populates complete system permissions and role mappings for KaizenDMS
 * Run this ONCE after database structure is created
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBAC Database Population</title>
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
        <h1>🔄 RBAC Database Population</h1>
        
        <?php
        
        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';
            
            $db = getDB();
            echo '<p class="success">✅ Database connection successful</p>';
            
            // Step 1: Verify RBAC tables exist
            echo '<div class="step">';
            echo '<h3>📋 Step 1: Verify RBAC Table Structure</h3>';
            
            $required_tables = ['dms_roles', 'dms_permissions', 'dms_role_permissions', 'dms_user_roles'];
            $missing_tables = [];
            
            foreach ($required_tables as $table) {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    $missing_tables[] = $table;
                    echo '<p class="error">❌ Missing table: ' . $table . '</p>';
                } else {
                    echo '<p class="success">✅ Table exists: ' . $table . '</p>';
                }
            }
            
            if (!empty($missing_tables)) {
                echo '<p class="error">❌ Cannot proceed - missing required tables</p>';
                echo '</div>';
                exit;
            }
            
            echo '<p class="success">✅ All required tables present</p>';
            echo '</div>';
            
            // Step 2: System Roles Population
            echo '<div class="step">';
            echo '<h3>👥 Step 2: Populate System Roles</h3>';
            
            $system_roles = [
                [
                    'id' => 7, 'name' => 'operator', 'role_name' => 'operator', 
                    'display_name' => 'Production Operator', 'hierarchy_level' => 70, 
                    'scope' => 'assigned_only', 'is_system_role' => 1,
                    'description' => 'Basic production operator with minimal permissions'
                ],
                [
                    'id' => 8, 'name' => 'line_lead', 'role_name' => 'line_lead',
                    'display_name' => 'Line Lead', 'hierarchy_level' => 60,
                    'scope' => 'station', 'is_system_role' => 1,
                    'description' => 'Production line leader with team oversight'
                ],
                [
                    'id' => 9, 'name' => 'supervisor', 'role_name' => 'supervisor',
                    'display_name' => 'Production Supervisor', 'hierarchy_level' => 50,
                    'scope' => 'process_area', 'is_system_role' => 1,
                    'description' => 'Process area supervisor with approval authority'
                ],
                [
                    'id' => 10, 'name' => 'engineer', 'role_name' => 'engineer',
                    'display_name' => 'Design Engineer', 'hierarchy_level' => 40,
                    'scope' => 'department', 'is_system_role' => 1,
                    'description' => 'Technical specialist for document creation and editing'
                ],
                [
                    'id' => 11, 'name' => 'department_owner', 'role_name' => 'department_owner',
                    'display_name' => 'Department Owner', 'hierarchy_level' => 30,
                    'scope' => 'cross_department', 'is_system_role' => 1,
                    'description' => 'Complete authority within assigned department'
                ],
                [
                    'id' => 12, 'name' => 'pso', 'role_name' => 'pso',
                    'display_name' => 'Product Safety Officer', 'hierarchy_level' => 20,
                    'scope' => 'all', 'is_system_role' => 1,
                    'description' => 'Safety authority with cross-department access'
                ],
                [
                    'id' => 13, 'name' => 'system_admin', 'role_name' => 'system_admin',
                    'display_name' => 'System Administrator', 'hierarchy_level' => 10,
                    'scope' => 'all', 'is_system_role' => 1,
                    'description' => 'Complete system administration authority'
                ]
            ];
            
            $roles_created = 0;
            $roles_updated = 0;
            
            foreach ($system_roles as $role) {
                // Check if role exists
                $stmt = $db->prepare("SELECT id FROM dms_roles WHERE id = ?");
                $stmt->execute([$role['id']]);
                
                if ($stmt->fetch()) {
                    // Update existing role
                    $stmt = $db->prepare("
                        UPDATE dms_roles 
                        SET name = ?, role_name = ?, display_name = ?, hierarchy_level = ?, 
                            scope = ?, is_system_role = ?, description = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $role['name'], $role['role_name'], $role['display_name'], 
                        $role['hierarchy_level'], $role['scope'], $role['is_system_role'], 
                        $role['description'], $role['id']
                    ]);
                    $roles_updated++;
                    echo '<p class="info">🔄 Updated role: ' . $role['display_name'] . '</p>';
                } else {
                    // Insert new role
                    $stmt = $db->prepare("
                        INSERT INTO dms_roles (id, name, role_name, display_name, hierarchy_level, scope, is_system_role, description)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $role['id'], $role['name'], $role['role_name'], $role['display_name'],
                        $role['hierarchy_level'], $role['scope'], $role['is_system_role'], $role['description']
                    ]);
                    $roles_created++;
                    echo '<p class="success">✅ Created role: ' . $role['display_name'] . '</p>';
                }
            }
            
            echo '<p class="success">✅ Roles processed: ' . $roles_created . ' created, ' . $roles_updated . ' updated</p>';
            echo '</div>';
            
            // Step 3: System Permissions Population
            echo '<div class="step">';
            echo '<h3>🔑 Step 3: Populate System Permissions</h3>';
            
            $system_permissions = [
                // Document Management Permissions
                ['name' => 'documents.view.all', 'display_name' => 'View All Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'view', 'scope_level' => 'all', 'description' => 'View all documents system-wide'],
                ['name' => 'documents.view.cross_department', 'display_name' => 'View Cross-Department Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'view', 'scope_level' => 'cross_department', 'description' => 'View documents across multiple departments'],
                ['name' => 'documents.view.department', 'display_name' => 'View Department Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'view', 'scope_level' => 'department', 'description' => 'View documents within own department'],
                ['name' => 'documents.view.process_area', 'display_name' => 'View Process Area Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'view', 'scope_level' => 'process_area', 'description' => 'View documents within process area'],
                ['name' => 'documents.view.station', 'display_name' => 'View Station Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'view', 'scope_level' => 'station', 'description' => 'View documents for specific station'],
                ['name' => 'documents.view.assigned', 'display_name' => 'View Assigned Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'view', 'scope_level' => 'assigned_only', 'description' => 'View only assigned documents'],
                
                ['name' => 'documents.create', 'display_name' => 'Create Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'create', 'scope_level' => 'department', 'description' => 'Create new documents'],
                ['name' => 'documents.edit.all', 'display_name' => 'Edit All Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'edit', 'scope_level' => 'all', 'description' => 'Edit any document system-wide'],
                ['name' => 'documents.edit.department', 'display_name' => 'Edit Department Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'edit', 'scope_level' => 'department', 'description' => 'Edit documents in own department'],
                ['name' => 'documents.edit.assigned', 'display_name' => 'Edit Assigned Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'edit', 'scope_level' => 'assigned_only', 'description' => 'Edit only assigned documents'],
                ['name' => 'documents.delete.all', 'display_name' => 'Delete All Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'delete', 'scope_level' => 'all', 'description' => 'Delete any document'],
                ['name' => 'documents.delete.department', 'display_name' => 'Delete Department Documents', 'category' => 'documents', 'resource' => 'documents', 'action' => 'delete', 'scope_level' => 'department', 'description' => 'Delete documents in own department'],
                
                // Approval Permissions
                ['name' => 'documents.approve.all', 'display_name' => 'Approve All Documents', 'category' => 'approvals', 'resource' => 'approvals', 'action' => 'approve', 'scope_level' => 'all', 'description' => 'Approve any document system-wide'],
                ['name' => 'documents.approve.cross_department', 'display_name' => 'Cross-Department Approvals', 'category' => 'approvals', 'resource' => 'approvals', 'action' => 'approve', 'scope_level' => 'cross_department', 'description' => 'Approve documents across departments'],
                ['name' => 'documents.approve.department', 'display_name' => 'Department Approvals', 'category' => 'approvals', 'resource' => 'approvals', 'action' => 'approve', 'scope_level' => 'department', 'description' => 'Approve documents within department'],
                ['name' => 'documents.approve.process_area', 'display_name' => 'Process Area Approvals', 'category' => 'approvals', 'resource' => 'approvals', 'action' => 'approve', 'scope_level' => 'process_area', 'description' => 'Approve documents within process area'],
                
                // User Management Permissions
                ['name' => 'users.manage.all', 'display_name' => 'Manage All Users', 'category' => 'admin', 'resource' => 'users', 'action' => 'manage', 'scope_level' => 'all', 'description' => 'Complete user management authority'],
                ['name' => 'users.view.all', 'display_name' => 'View All Users', 'category' => 'admin', 'resource' => 'users', 'action' => 'view', 'scope_level' => 'all', 'description' => 'View all system users'],
                ['name' => 'users.view.department', 'display_name' => 'View Department Users', 'category' => 'admin', 'resource' => 'users', 'action' => 'view', 'scope_level' => 'department', 'description' => 'View users in same department'],
                
                // Role Management Permissions
                ['name' => 'roles.manage', 'display_name' => 'Manage Roles', 'category' => 'admin', 'resource' => 'roles', 'action' => 'manage', 'scope_level' => 'all', 'description' => 'Role and permission management'],
                ['name' => 'roles.assign.all', 'display_name' => 'Assign All Roles', 'category' => 'admin', 'resource' => 'roles', 'action' => 'assign', 'scope_level' => 'all', 'description' => 'Assign any role to users'],
                ['name' => 'roles.assign.department', 'display_name' => 'Assign Department Roles', 'category' => 'admin', 'resource' => 'roles', 'action' => 'assign', 'scope_level' => 'department', 'description' => 'Assign roles within department'],
                
                // Reporting Permissions
                ['name' => 'reports.view.all', 'display_name' => 'View All Reports', 'category' => 'reports', 'resource' => 'reports', 'action' => 'view', 'scope_level' => 'all', 'description' => 'Access all system reports'],
                ['name' => 'reports.view.cross_department', 'display_name' => 'View Cross-Department Reports', 'category' => 'reports', 'resource' => 'reports', 'action' => 'view', 'scope_level' => 'cross_department', 'description' => 'Access reports across departments'],
                ['name' => 'reports.view.department', 'display_name' => 'View Department Reports', 'category' => 'reports', 'resource' => 'reports', 'action' => 'view', 'scope_level' => 'department', 'description' => 'Access department-specific reports'],
                ['name' => 'reports.create', 'display_name' => 'Create Reports', 'category' => 'reports', 'resource' => 'reports', 'action' => 'create', 'scope_level' => 'department', 'description' => 'Create custom reports'],
                
                // Safety Permissions
                ['name' => 'safety.audit.all', 'display_name' => 'System-wide Safety Audits', 'category' => 'safety', 'resource' => 'audit', 'action' => 'audit', 'scope_level' => 'all', 'description' => 'Conduct safety audits across all departments'],
                ['name' => 'safety.override', 'display_name' => 'Safety Override Authority', 'category' => 'safety', 'resource' => 'documents', 'action' => 'override', 'scope_level' => 'all', 'description' => 'Override document restrictions for safety reasons'],
                ['name' => 'safety.emergency_access', 'display_name' => 'Emergency Access', 'category' => 'safety', 'resource' => 'emergency', 'action' => 'access', 'scope_level' => 'all', 'description' => 'Emergency access to critical documents'],
                
                // Audit Trail Permissions
                ['name' => 'audit.view.all', 'display_name' => 'View All Audit Logs', 'category' => 'audit', 'resource' => 'audit', 'action' => 'view', 'scope_level' => 'all', 'description' => 'Access complete audit trail'],
                ['name' => 'audit.view.department', 'display_name' => 'View Department Audit Logs', 'category' => 'audit', 'resource' => 'audit', 'action' => 'view', 'scope_level' => 'department', 'description' => 'Access department audit logs'],
                ['name' => 'audit.export', 'display_name' => 'Export Audit Data', 'category' => 'audit', 'resource' => 'audit', 'action' => 'export', 'scope_level' => 'all', 'description' => 'Export audit data for compliance'],
                
                // Training Permissions
                ['name' => 'training.assign', 'display_name' => 'Assign Training', 'category' => 'training', 'resource' => 'training', 'action' => 'assign', 'scope_level' => 'department', 'description' => 'Assign training to users'],
                ['name' => 'training.view.all', 'display_name' => 'View All Training Records', 'category' => 'training', 'resource' => 'training', 'action' => 'view', 'scope_level' => 'all', 'description' => 'Access all training records'],
                ['name' => 'training.view.department', 'display_name' => 'View Department Training', 'category' => 'training', 'resource' => 'training', 'action' => 'view', 'scope_level' => 'department', 'description' => 'View department training records'],
                
                // System Administration
                ['name' => 'system.settings', 'display_name' => 'System Settings', 'category' => 'admin', 'resource' => 'system', 'action' => 'configure', 'scope_level' => 'all', 'description' => 'Configure system settings'],
                ['name' => 'system.backup', 'display_name' => 'System Backup', 'category' => 'admin', 'resource' => 'system', 'action' => 'backup', 'scope_level' => 'all', 'description' => 'Create system backups'],
                ['name' => 'system.maintenance', 'display_name' => 'System Maintenance', 'category' => 'admin', 'resource' => 'system', 'action' => 'maintain', 'scope_level' => 'all', 'description' => 'Perform system maintenance'],
            ];
            
            $permissions_created = 0;
            $permissions_updated = 0;
            
            foreach ($system_permissions as $perm) {
                // Check if permission exists
                $stmt = $db->prepare("SELECT id FROM dms_permissions WHERE name = ?");
                $stmt->execute([$perm['name']]);
                
                if ($stmt->fetch()) {
                    // Update existing permission
                    $stmt = $db->prepare("
                        UPDATE dms_permissions 
                        SET display_name = ?, category = ?, resource = ?, action = ?, 
                            scope_level = ?, description = ?, is_system_permission = 1
                        WHERE name = ?
                    ");
                    $stmt->execute([
                        $perm['display_name'], $perm['category'], $perm['resource'], 
                        $perm['action'], $perm['scope_level'], $perm['description'], $perm['name']
                    ]);
                    $permissions_updated++;
                } else {
                    // Insert new permission
                    $stmt = $db->prepare("
                        INSERT INTO dms_permissions (name, display_name, category, resource, action, scope_level, description, is_system_permission)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([
                        $perm['name'], $perm['display_name'], $perm['category'], $perm['resource'], 
                        $perm['action'], $perm['scope_level'], $perm['description']
                    ]);
                    $permissions_created++;
                }
            }
            
            echo '<p class="success">✅ Permissions processed: ' . $permissions_created . ' created, ' . $permissions_updated . ' updated</p>';
            echo '</div>';
            
            // Step 4: Role-Permission Mappings
            echo '<div class="step">';
            echo '<h3>🔗 Step 4: Create Role-Permission Mappings</h3>';
            
            // Get role and permission IDs for mapping
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
            
            // Role-Permission mappings based on hierarchy
            $role_mappings = [
                'operator' => [
                    'documents.view.assigned',
                    'documents.edit.assigned',
                    'training.view.department'
                ],
                'line_lead' => [
                    'documents.view.station',
                    'documents.edit.assigned',
                    'training.view.department',
                    'reports.view.department'
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
            
            $mappings_created = 0;
            
            foreach ($role_mappings as $role_name => $permissions) {
                if (!isset($role_ids[$role_name])) {
                    echo '<p class="error">❌ Role not found: ' . $role_name . '</p>';
                    continue;
                }
                
                $role_id = $role_ids[$role_name];
                
                foreach ($permissions as $permission_name) {
                    if (!isset($permission_ids[$permission_name])) {
                        echo '<p class="error">❌ Permission not found: ' . $permission_name . '</p>';
                        continue;
                    }
                    
                    $permission_id = $permission_ids[$permission_name];
                    
                    // Check if mapping already exists
                    $stmt = $db->prepare("SELECT id FROM dms_role_permissions WHERE role_id = ? AND permission_id = ?");
                    $stmt->execute([$role_id, $permission_id]);
                    
                    if (!$stmt->fetch()) {
                        // Create new mapping
                        $stmt = $db->prepare("
                            INSERT INTO dms_role_permissions (role_id, permission_id, granted_scope, is_inherited, granted_by)
                            VALUES (?, ?, 'default', 0, 1)
                        ");
                        $stmt->execute([$role_id, $permission_id]);
                        $mappings_created++;
                    }
                }
                
                echo '<p class="success">✅ Processed role: ' . $role_name . ' (' . count($permissions) . ' permissions)</p>';
            }
            
            echo '<p class="success">✅ Role-permission mappings created: ' . $mappings_created . '</p>';
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
                echo '<h4>🎉 RBAC DATABASE POPULATION COMPLETE!</h4>';
                echo '<p><strong>✅ System is ready for RBAC operations</strong></p>';
                echo '<ul>';
                echo '<li>✅ All system roles created with proper hierarchy</li>';
                echo '<li>✅ Complete permission catalog populated</li>';
                echo '<li>✅ Role-permission mappings established</li>';
                echo '<li>✅ Ready for admin interface integration</li>';
                echo '</ul>';
                echo '<p><strong>Next Step:</strong> Run role conflict resolution if needed</p>';
                echo '</div>';
            } else {
                echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;">';
                echo '<h4>⚠️ RBAC POPULATION INCOMPLETE</h4>';
                echo '<p>Some components may not have been populated correctly. Review the logs above.</p>';
                echo '</div>';
            }
            
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="step error">';
            echo '<h3>❌ Error During Population</h3>';
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        
        ?>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p><strong>RBAC Database Population Script</strong></p>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>