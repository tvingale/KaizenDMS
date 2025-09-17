<?php
/**
 * Debug Role Assignment Issue
 * Check what's in the database vs what we're trying to assign
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();

    echo "<h2>Role Assignment Debug</h2>";

    // Check what roles exist in dms_roles
    echo "<h3>Available Roles in dms_roles:</h3>";
    $stmt = $db->query("SELECT id, name, role_name, display_name, is_system_role FROM dms_roles ORDER BY id");
    $roles = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Role Name</th><th>Display Name</th><th>System Role</th></tr>";
    foreach ($roles as $role) {
        echo "<tr>";
        echo "<td>" . $role['id'] . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['role_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($role['display_name'] ?? 'NULL') . "</td>";
        echo "<td>" . ($role['is_system_role'] ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check current role assignments
    echo "<h3>Current Role Assignments in dms_user_roles:</h3>";
    $stmt = $db->query("SELECT user_id, role_id, status, granted_by, granted_at, department FROM dms_user_roles ORDER BY user_id, role_id");
    $assignments = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>Role ID</th><th>Status</th><th>Granted By</th><th>Granted At</th><th>Department</th></tr>";
    foreach ($assignments as $assignment) {
        echo "<tr>";
        echo "<td>" . $assignment['user_id'] . "</td>";
        echo "<td>" . $assignment['role_id'] . "</td>";
        echo "<td>" . $assignment['status'] . "</td>";
        echo "<td>" . ($assignment['granted_by'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['granted_at'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($assignment['department'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test specific queries
    echo "<h3>Test Queries:</h3>";

    // Test 1: Check if user2 has any roles
    echo "<h4>Test 1: Does user2 have any active roles?</h4>";
    $stmt = $db->prepare("SELECT * FROM dms_user_roles WHERE user_id = ? AND status = 'active'");
    $stmt->execute([2]); // Assuming user2 has ID 2
    $user2Roles = $stmt->fetchAll();
    if (empty($user2Roles)) {
        echo "<p style='color: green;'>✅ User2 has NO active roles</p>";
    } else {
        echo "<p style='color: red;'>❌ User2 already has these active roles:</p>";
        echo "<pre>";
        print_r($user2Roles);
        echo "</pre>";
    }

    // Test 2: Check specific role existence
    echo "<h4>Test 2: Check if user2 has role ID 4 (qa_manager):</h4>";
    $stmt = $db->prepare("SELECT COUNT(*) FROM dms_user_roles WHERE user_id = ? AND role_id = ? AND status = 'active'");
    $stmt->execute([2, 4]);
    $hasRole = $stmt->fetchColumn() > 0;
    echo "<p style='color: " . ($hasRole ? 'red' : 'green') . ";'>" . ($hasRole ? '❌ Already has role' : '✅ Does not have role') . "</p>";

    // Test 3: Show what the form query would return
    echo "<h4>Test 3: Available Roles Query (as used in form):</h4>";
    $stmt = $db->query("SELECT * FROM dms_roles WHERE is_system_role = 1 ORDER BY hierarchy_level ASC, name");
    $availableRoles = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Display Name</th><th>Would appear in form?</th></tr>";
    foreach ($availableRoles as $role) {
        echo "<tr>";
        echo "<td>" . $role['id'] . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['display_name'] ?? $role['name']) . "</td>";
        echo "<td style='color: green;'>YES</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>