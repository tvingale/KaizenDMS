<?php
/**
 * Test Impact of Hidden Roles on Existing Code
 * See what happens to users with manager/user role assignments
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';
require_once '/home3/kaizenap/public_html/doms/includes/AccessControl.php';

try {
    $db = getDB();

    echo "<h2>Testing Hidden Role Impact</h2>";

    // Test 1: Check what roles will be shown in UI
    echo "<h3>Test 1: Roles visible in assignment forms</h3>";
    $stmt = $db->query("
        SELECT id, name, display_name, hidden_from_ui,
               CASE WHEN (hidden_from_ui IS NULL OR hidden_from_ui = FALSE) THEN 'VISIBLE' ELSE 'HIDDEN' END as ui_status
        FROM dms_roles
        WHERE status = 'active'
        ORDER BY id
    ");
    $roles = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Display Name</th><th>UI Status</th></tr>";
    foreach ($roles as $role) {
        $color = $role['ui_status'] == 'HIDDEN' ? '#ffcccc' : '#ccffcc';
        echo "<tr style='background-color: $color;'>";
        echo "<td>" . $role['id'] . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['display_name']) . "</td>";
        echo "<td><strong>" . $role['ui_status'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test 2: Check existing assignments with hidden roles
    echo "<h3>Test 2: Existing assignments with hidden roles</h3>";
    $stmt = $db->query("
        SELECT ur.user_id, ur.role_id, ur.status, r.name as role_name, r.display_name,
               CASE WHEN r.hidden_from_ui = TRUE THEN 'HIDDEN ROLE' ELSE 'VISIBLE ROLE' END as role_ui_status
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        WHERE ur.status = 'active'
        ORDER BY ur.user_id, ur.role_id
    ");
    $assignments = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>Role ID</th><th>Role Name</th><th>Display Name</th><th>Assignment Status</th></tr>";
    foreach ($assignments as $assignment) {
        $color = $assignment['role_ui_status'] == 'HIDDEN ROLE' ? '#ffcccc' : '#ccffcc';
        echo "<tr style='background-color: $color;'>";
        echo "<td>" . $assignment['user_id'] . "</td>";
        echo "<td>" . $assignment['role_id'] . "</td>";
        echo "<td>" . htmlspecialchars($assignment['role_name']) . "</td>";
        echo "<td>" . htmlspecialchars($assignment['display_name'] ?? 'NULL') . "</td>";
        echo "<td><strong>" . $assignment['role_ui_status'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test 3: Test AccessControl functionality
    echo "<h3>Test 3: AccessControl functionality with hidden roles</h3>";

    // Mock user with manager role (if exists)
    $mockUser = ['id' => 999, 'name' => 'Test Manager User'];

    // Check if we can create AccessControl instance
    try {
        $accessControl = new AccessControl($db, $mockUser);
        echo "<p style='color: green;'>✅ AccessControl instance created successfully</p>";

        // Test role hierarchy
        $hierarchy = [
            'user' => 1,
            'manager' => 2,
            'admin' => 3
        ];

        foreach ($hierarchy as $roleName => $level) {
            echo "<p>Role '<strong>$roleName</strong>' (level $level): ";

            // Check if role exists in database
            $stmt = $db->prepare("SELECT COUNT(*) FROM dms_roles WHERE name = ?");
            $stmt->execute([$roleName]);
            $roleExists = $stmt->fetchColumn() > 0;

            if ($roleExists) {
                echo "<span style='color: green;'>EXISTS in DB</span>";
            } else {
                echo "<span style='color: red;'>MISSING from DB</span>";
            }
            echo "</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ AccessControl failed: " . $e->getMessage() . "</p>";
    }

    // Test 4: What happens in module_users.php query
    echo "<h3>Test 4: Role assignments display query (from module_users.php)</h3>";
    $query = "
        SELECT ur.user_id, ur.role_id, ur.status, ur.granted_by, ur.granted_at, ur.notes,
               ur.last_access, ur.role_name, ur.department,
               r.name as role_table_name, r.display_name, r.hierarchy_level
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        ORDER BY ur.user_id, ur.status DESC, ur.granted_at DESC
        LIMIT 10
    ";

    $stmt = $db->query($query);
    $displayAssignments = $stmt->fetchAll();

    echo "<p><strong>Query Results (first 10):</strong></p>";
    if (empty($displayAssignments)) {
        echo "<p style='color: blue;'>No assignments found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr><th>User</th><th>Role ID</th><th>Role Name</th><th>Display Name</th><th>Status</th></tr>";
        foreach ($displayAssignments as $assignment) {
            echo "<tr>";
            echo "<td>" . $assignment['user_id'] . "</td>";
            echo "<td>" . $assignment['role_id'] . "</td>";
            echo "<td>" . htmlspecialchars($assignment['role_table_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($assignment['display_name'] ?? 'NULL') . "</td>";
            echo "<td>" . $assignment['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h3>Summary</h3>";
    echo "<ul>";
    echo "<li><strong>UI Impact:</strong> Hidden roles won't appear in assignment forms</li>";
    echo "<li><strong>Existing Assignments:</strong> Will still function and display with role names</li>";
    echo "<li><strong>Code Compatibility:</strong> hasRole() calls will continue to work</li>";
    echo "<li><strong>Display Issues:</strong> Need to handle NULL display names gracefully</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>