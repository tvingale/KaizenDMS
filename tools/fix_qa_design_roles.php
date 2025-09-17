<?php
/**
 * Fix QA Manager and Design Manager roles
 * Make them system roles so they appear in the form
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();

    echo "<h2>Fixing QA Manager and Design Manager Roles</h2>";

    // Update QA Manager to be a system role
    echo "<h3>Updating QA Manager...</h3>";
    $stmt = $db->prepare("
        UPDATE dms_roles
        SET is_system_role = 1,
            hierarchy_level = 'manager',
            role_name = COALESCE(role_name, 'qa_manager')
        WHERE id = 18
    ");
    $stmt->execute();
    echo "<p style='color: green;'>✅ QA Manager updated (affected rows: " . $stmt->rowCount() . ")</p>";

    // Update Design Manager to be a system role
    echo "<h3>Updating Design Manager...</h3>";
    $stmt = $db->prepare("
        UPDATE dms_roles
        SET is_system_role = 1,
            hierarchy_level = 'manager',
            role_name = COALESCE(role_name, 'design_manager')
        WHERE id = 19
    ");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Design Manager updated (affected rows: " . $stmt->rowCount() . ")</p>";

    // Verify the changes
    echo "<h3>Verification - Updated Roles:</h3>";
    $stmt = $db->query("SELECT id, name, role_name, display_name, is_system_role, hierarchy_level FROM dms_roles WHERE id IN (18, 19)");
    $roles = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Role Name</th><th>Display Name</th><th>System Role</th><th>Hierarchy</th></tr>";
    foreach ($roles as $role) {
        echo "<tr>";
        echo "<td>" . $role['id'] . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['role_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($role['display_name']) . "</td>";
        echo "<td style='color: " . ($role['is_system_role'] ? 'green' : 'red') . ";'>" . ($role['is_system_role'] ? 'YES' : 'NO') . "</td>";
        echo "<td>" . htmlspecialchars($role['hierarchy_level'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>✅ Fixed! Now QA Manager and Design Manager should appear in role assignment forms.</h3>";
    echo "<p><a href='/module_users.php'>Test Role Assignment Form</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>