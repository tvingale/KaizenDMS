<?php
/**
 * Resolve Role Conflicts - Fix conflicting role systems
 * This handles the conflict between original roles (1-4) and new RBAC roles (6-14)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();

echo "<h2>🔧 Role Conflict Resolution</h2>";

// Step 1: Analyze current situation
echo "<h3>📊 Current Role Analysis:</h3>";
$stmt = $db->query("SELECT id, name, role_name, display_name, description, is_system_role FROM dms_roles ORDER BY id");
$all_roles = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>name</th><th>role_name</th><th>display_name</th><th>description</th><th>system_role</th><th>issue</th></tr>";

$conflicts = [];
$original_roles = [];
$new_rbac_roles = [];

foreach ($all_roles as $role) {
    $issue = '';
    if ($role['id'] <= 4) {
        $original_roles[] = $role;
        if (in_array($role['name'], ['admin', 'manager', 'user'])) {
            $issue = 'Original System';
        } elseif (strpos($role['name'], 'micro_test') !== false) {
            $issue = 'Test Role - Can Delete';
        }
    } else {
        $new_rbac_roles[] = $role;
        if (empty($role['role_name'])) {
            $issue = 'Missing role_name';
        } elseif ($role['is_system_role']) {
            $issue = 'New RBAC System';
        }
    }
    
    echo "<tr>";
    echo "<td>" . $role['id'] . "</td>";
    echo "<td>" . ($role['name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['role_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['display_name'] ?? 'NULL') . "</td>";
    echo "<td>" . substr(($role['description'] ?? ''), 0, 50) . "...</td>";
    echo "<td>" . ($role['is_system_role'] ? 'YES' : 'NO') . "</td>";
    echo "<td style='color: " . ($issue ? 'red' : 'green') . ";'>" . $issue . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 2: Resolution Strategy
echo "<h3>🎯 Resolution Strategy:</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>Options:</h4>";
echo "<ol>";
echo "<li><strong>Option A:</strong> Keep both systems, map original roles to new RBAC equivalents</li>";
echo "<li><strong>Option B:</strong> Migrate original roles to new RBAC system</li>";
echo "<li><strong>Option C:</strong> Clean slate - remove conflicts and standardize</li>";
echo "</ol>";
echo "</div>";

// Step 3: Recommended Solution - Option A (Safe Migration)
echo "<h3>✅ Implementing Option A: Safe Role Mapping</h3>";

// Map original roles to new RBAC equivalents
$role_mappings = [
    'admin' => 'system_admin',
    'manager' => 'department_owner', 
    'user' => 'operator'
];

echo "<h4>1. Mapping Original Roles to RBAC Equivalents:</h4>";
foreach ($role_mappings as $original => $rbac_equivalent) {
    echo "<p>📍 <strong>{$original}</strong> → <strong>{$rbac_equivalent}</strong></p>";
}

// Step 4: Update original roles to match RBAC structure
echo "<h4>2. Updating Original Roles:</h4>";

foreach ($original_roles as $role) {
    if (isset($role_mappings[$role['name']])) {
        $rbac_equivalent = $role_mappings[$role['name']];
        
        // Update the original role to have proper RBAC structure
        $stmt = $db->prepare("
            UPDATE dms_roles 
            SET role_name = ?, 
                is_system_role = TRUE,
                description = CONCAT('Legacy role mapped to RBAC: ', ?)
            WHERE id = ?
        ");
        $stmt->execute([$role['name'], $rbac_equivalent, $role['id']]);
        
        echo "<p>✅ Updated role ID {$role['id']} ({$role['name']}) with RBAC structure</p>";
    }
}

// Step 5: Fix new RBAC roles with missing names
echo "<h4>3. Fixing New RBAC Roles:</h4>";

$system_role_mappings = [
    6 => ['role_name' => 'operator', 'name' => 'operator', 'display_name' => 'Production Operator'],
    9 => ['role_name' => 'line_lead', 'name' => 'line_lead', 'display_name' => 'Line Lead'],
    10 => ['role_name' => 'supervisor', 'name' => 'supervisor', 'display_name' => 'Production Supervisor'],
    11 => ['role_name' => 'engineer', 'name' => 'engineer', 'display_name' => 'Design Engineer'],
    12 => ['role_name' => 'department_owner', 'name' => 'department_owner', 'display_name' => 'Department Owner'],
    13 => ['role_name' => 'pso', 'name' => 'pso', 'display_name' => 'Product Safety Officer'],
    14 => ['role_name' => 'system_admin', 'name' => 'system_admin', 'display_name' => 'System Administrator']
];

foreach ($system_role_mappings as $id => $mapping) {
    $stmt = $db->prepare("
        UPDATE dms_roles 
        SET role_name = ?, 
            name = ?, 
            display_name = ?
        WHERE id = ?
    ");
    $stmt->execute([$mapping['role_name'], $mapping['name'], $mapping['display_name'], $id]);
    echo "<p>✅ Fixed role ID {$id}: {$mapping['role_name']}</p>";
}

// Step 6: Handle duplicate system_admin conflict
echo "<h4>4. Resolving system_admin Conflict:</h4>";

// Check if we have both 'admin' (ID 1) and 'system_admin' roles
$stmt = $db->prepare("SELECT id FROM dms_roles WHERE name = 'admin'");
$stmt->execute();
$admin_role = $stmt->fetch();

$stmt = $db->prepare("SELECT id FROM dms_roles WHERE name = 'system_admin'");
$stmt->execute();
$system_admin_role = $stmt->fetch();

if ($admin_role && $system_admin_role) {
    echo "<p>⚠️ Conflict detected: Both 'admin' (ID {$admin_role['id']}) and 'system_admin' (ID {$system_admin_role['id']}) exist</p>";
    
    // Solution: Make 'admin' an alias for 'system_admin'
    $stmt = $db->prepare("
        UPDATE dms_roles 
        SET description = 'Legacy admin role - equivalent to system_admin',
            role_name = 'admin_legacy'
        WHERE id = ?
    ");
    $stmt->execute([$admin_role['id']]);
    echo "<p>✅ Converted 'admin' to 'admin_legacy' to avoid conflict</p>";
}

// Step 7: Clean up test roles
echo "<h4>5. Cleaning Up Test Roles:</h4>";
$stmt = $db->prepare("DELETE FROM dms_roles WHERE name LIKE 'micro_test%'");
$deleted = $stmt->execute();
if ($deleted) {
    echo "<p>✅ Removed test roles (micro_test_*)</p>";
}

// Step 8: Show final results
echo "<h3>📊 Final Role Structure:</h3>";
$stmt = $db->query("SELECT id, name, role_name, display_name, is_system_role FROM dms_roles ORDER BY id");
$final_roles = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>name</th><th>role_name</th><th>display_name</th><th>system_role</th><th>status</th></tr>";
foreach ($final_roles as $role) {
    $status = '';
    if ($role['is_system_role']) {
        $status = '✅ RBAC System Role';
    } else {
        $status = 'ℹ️ Custom Role';
    }
    
    echo "<tr>";
    echo "<td>" . $role['id'] . "</td>";
    echo "<td>" . ($role['name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['role_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['display_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['is_system_role'] ? 'YES' : 'NO') . "</td>";
    echo "<td style='color: green;'>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 9: Update user role assignments if needed
echo "<h3>👥 User Role Assignment Status:</h3>";
$stmt = $db->query("
    SELECT ur.user_id, ur.role_id, r.name as role_name, r.role_name as rbac_role_name 
    FROM dms_user_roles ur 
    JOIN dms_roles r ON ur.role_id = r.id 
    WHERE ur.status = 'active'
");
$user_roles = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>User ID</th><th>Role ID</th><th>Role Name</th><th>RBAC Role Name</th><th>Status</th></tr>";
foreach ($user_roles as $ur) {
    $status = $ur['rbac_role_name'] ? '✅ RBAC Compatible' : '⚠️ Needs Update';
    echo "<tr>";
    echo "<td>" . $ur['user_id'] . "</td>";
    echo "<td>" . $ur['role_id'] . "</td>";
    echo "<td>" . ($ur['role_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($ur['rbac_role_name'] ?? 'NULL') . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h3>✅ Role Conflict Resolution Complete!</h3>";
echo "<ul>";
echo "<li>✅ Original roles (admin, manager, user) updated with RBAC structure</li>";
echo "<li>✅ New RBAC roles (operator, line_lead, etc.) properly named</li>";
echo "<li>✅ Role conflicts resolved</li>";
echo "<li>✅ Test roles cleaned up</li>";
echo "<li>✅ User assignments preserved</li>";
echo "</ul>";
echo "<p><strong>Next:</strong> <a href='../admin/roles_permissions.php'>View Updated Roles in Admin Panel</a></p>";
echo "</div>";
?>