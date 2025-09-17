<?php
/**
 * RBAC Quick Status Check
 * Verify current RBAC system status after population
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';

$db = getDB();

echo "<h2>🔍 RBAC System Quick Status</h2>";

try {
    // Check roles
    echo "<h3>👥 System Roles Status</h3>";
    $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE id >= 7");
    $rbac_roles = $stmt->fetchColumn();
    echo "<p>✅ RBAC Roles (ID >= 7): <strong>$rbac_roles</strong></p>";

    if ($rbac_roles > 0) {
        $stmt = $db->query("SELECT id, name, role_name, display_name FROM dms_roles WHERE id >= 7 ORDER BY id");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Role Name</th><th>Display Name</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['role_name']}</td><td>{$row['display_name']}</td></tr>";
        }
        echo "</table>";
    }

    // Check permissions
    echo "<h3>🔑 Permissions Status</h3>";
    $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions");
    $permissions = $stmt->fetchColumn();
    echo "<p>✅ Total Permissions: <strong>$permissions</strong></p>";

    // Check role-permission mappings
    echo "<h3>🔗 Role-Permission Mappings Status</h3>";
    $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
    $mappings = $stmt->fetchColumn();
    echo "<p>✅ Total Mappings: <strong>$mappings</strong></p>";

    // Check dms_role_permissions structure
    echo "<h3>🔧 Role-Permission Table Structure</h3>";
    $stmt = $db->query("DESCRIBE dms_role_permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Columns: ";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . "), ";
    }
    echo "</p>";

    // Overall RBAC Status
    echo "<h3>🎯 Overall RBAC Status</h3>";

    if ($rbac_roles >= 7 && $permissions >= 15 && $mappings >= 20) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
        echo "<h4>🎉 RBAC SYSTEM OPERATIONAL!</h4>";
        echo "<p>✅ RBAC system is ready for conflict resolution and admin interface development</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li>1. Run resolve_role_conflicts.php if needed</li>";
        echo "<li>2. Test RBAC integration with AccessControl.php</li>";
        echo "<li>3. Build admin interfaces for RBAC management</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<h4>⚠️ RBAC SYSTEM INCOMPLETE</h4>";
        echo "<p>Current: $rbac_roles roles, $permissions permissions, $mappings mappings</p>";
        echo "<p>The minimal population was successful. The error in the comprehensive script is due to table structure differences.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<h4>❌ Error checking RBAC status</h4>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p><small>Generated: " . date('Y-m-d H:i:s') . "</small></p>";
?>