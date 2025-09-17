<?php
/**
 * Fix Role Names - Update missing role_name values
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();

echo "<h2>🔧 Role Names Fix</h2>";

// Step 1: Check current role data
echo "<h3>Current Role Data:</h3>";
$stmt = $db->query("SELECT id, name, role_name, display_name, is_system_role FROM dms_roles ORDER BY id");
$roles = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>name</th><th>role_name</th><th>display_name</th><th>system_role</th></tr>";
foreach ($roles as $role) {
    echo "<tr>";
    echo "<td>" . $role['id'] . "</td>";
    echo "<td>" . ($role['name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['role_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['display_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['is_system_role'] ? 'YES' : 'NO') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Step 2: Fix missing role_name values
echo "<h3>Fixing Missing role_name Values:</h3>";

$updates = 0;
foreach ($roles as $role) {
    if (empty($role['role_name']) && !empty($role['name'])) {
        // Update role_name to match name
        $stmt = $db->prepare("UPDATE dms_roles SET role_name = ? WHERE id = ?");
        $stmt->execute([$role['name'], $role['id']]);
        echo "<p>✅ Updated role ID {$role['id']}: role_name = '{$role['name']}'</p>";
        $updates++;
    } elseif (empty($role['role_name']) && empty($role['name']) && !empty($role['display_name'])) {
        // Try to derive role_name from display_name
        $role_name = strtolower(str_replace([' ', '-'], '_', $role['display_name']));
        $stmt = $db->prepare("UPDATE dms_roles SET role_name = ?, name = ? WHERE id = ?");
        $stmt->execute([$role_name, $role_name, $role['id']]);
        echo "<p>✅ Updated role ID {$role['id']}: role_name = '{$role_name}' (derived from display_name)</p>";
        $updates++;
    }
}

if ($updates === 0) {
    echo "<p>ℹ️ No role_name updates needed.</p>";
}

// Step 3: Check if we need to map system roles
echo "<h3>System Role Mapping:</h3>";

$system_role_mappings = [
    'operator' => ['display_name' => 'Production Operator', 'description' => 'Basic production line operator with minimal permissions'],
    'line_lead' => ['display_name' => 'Line Lead', 'description' => 'Production line leader with team oversight'],
    'supervisor' => ['display_name' => 'Production Supervisor', 'description' => 'Process area supervisor with approval authority'],
    'engineer' => ['display_name' => 'Design Engineer', 'description' => 'Technical specialist for document creation and editing'],
    'department_owner' => ['display_name' => 'Department Owner', 'description' => 'Complete authority within assigned department'],
    'pso' => ['display_name' => 'Product Safety Officer', 'description' => 'Safety authority with cross-department access'],
    'system_admin' => ['display_name' => 'System Administrator', 'description' => 'Complete system administration authority']
];

foreach ($system_role_mappings as $role_name => $info) {
    $stmt = $db->prepare("SELECT id FROM dms_roles WHERE role_name = ?");
    $stmt->execute([$role_name]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        // Find role by description pattern
        $stmt = $db->prepare("SELECT id FROM dms_roles WHERE description LIKE ? AND is_system_role = TRUE AND (role_name IS NULL OR role_name = '')");
        $stmt->execute(['%' . $info['description'] . '%']);
        $match = $stmt->fetch();
        
        if ($match) {
            $stmt = $db->prepare("UPDATE dms_roles SET role_name = ?, name = ?, display_name = ? WHERE id = ?");
            $stmt->execute([$role_name, $role_name, $info['display_name'], $match['id']]);
            echo "<p>✅ Mapped role ID {$match['id']} to system role: {$role_name}</p>";
        }
    }
}

// Step 4: Show updated results
echo "<h3>Updated Role Data:</h3>";
$stmt = $db->query("SELECT id, name, role_name, display_name, is_system_role FROM dms_roles ORDER BY id");
$updated_roles = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>name</th><th>role_name</th><th>display_name</th><th>system_role</th></tr>";
foreach ($updated_roles as $role) {
    echo "<tr>";
    echo "<td>" . $role['id'] . "</td>";
    echo "<td>" . ($role['name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['role_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['display_name'] ?? 'NULL') . "</td>";
    echo "<td>" . ($role['is_system_role'] ? 'YES' : 'NO') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>✅ Role Names Fix Complete!</h3>";
echo "<p><a href='../admin/roles_permissions.php'>View Updated Roles in Admin Panel</a></p>";
?>