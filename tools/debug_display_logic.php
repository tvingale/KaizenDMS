<?php
/**
 * Debug Display Logic
 * Trace the exact processing of query results into display arrays
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🔍 Debug Display Logic Processing</h2>";

    // Replicate the exact logic from module_users.php
    $selectColumns = "ur.user_id, ur.role_id, ur.status";
    $selectColumns .= ", ur.granted_by, ur.granted_at, ur.notes";
    $selectColumns .= ", ur.last_access, ur.role_name, ur.department";

    $query = "
        SELECT " . $selectColumns . ", r.name as role_table_name, r.display_name, r.hierarchy_level
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        ORDER BY ur.user_id, ur.status DESC, ur.granted_at DESC
    ";

    $stmt = $db->query($query);
    $userRoleAssignments = $stmt->fetchAll();

    echo "<h3>1️⃣ Raw Query Results</h3>";
    echo "<p><strong>Total records from query:</strong> " . count($userRoleAssignments) . "</p>";
    foreach ($userRoleAssignments as $i => $assignment) {
        echo "<p><strong>Record #{$i}:</strong> User ID {$assignment['user_id']}, Role ID {$assignment['role_id']}, Status: {$assignment['status']}</p>";
    }

    // Step 2: Add composite IDs (from module_users.php line 326-328)
    echo "<h3>2️⃣ After Adding Composite IDs</h3>";
    foreach ($userRoleAssignments as &$assignment) {
        // Create composite ID for form operations: user_id-role_id
        $assignment['id'] = $assignment['user_id'] . '-' . $assignment['role_id'];

        // Add missing fields to prevent errors
        $assignment['scope'] = $assignment['scope'] ?? 'all';
        $assignment['scope_value'] = $assignment['scope_value'] ?? '';
    }
    unset($assignment); // Break reference

    echo "<p><strong>After composite ID processing:</strong></p>";
    foreach ($userRoleAssignments as $i => $assignment) {
        echo "<p><strong>Record #{$i}:</strong> ID: {$assignment['id']}, User ID {$assignment['user_id']}, Role ID {$assignment['role_id']}</p>";
    }

    // Step 3: Group by user (from module_users.php line 346-354)
    echo "<h3>3️⃣ After Grouping by User</h3>";
    $userGroups = [];
    foreach ($userRoleAssignments as $assignment) {
        $userId = $assignment['user_id'];
        if (!isset($userGroups[$userId])) {
            $userGroups[$userId] = [];
        }
        $userGroups[$userId][] = $assignment;
        echo "<p>➕ Added assignment ID {$assignment['id']} to User Group {$userId}</p>";
    }

    echo "<p><strong>Final user groups:</strong></p>";
    foreach ($userGroups as $userId => $assignments) {
        echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>👤 User ID {$userId} - " . count($assignments) . " assignment(s)</h4>";
        foreach ($assignments as $i => $assignment) {
            echo "<p>&nbsp;&nbsp;&nbsp;📋 Assignment #{$i}: ID={$assignment['id']}, Role={$assignment['display_name']}, Status={$assignment['status']}</p>";
        }
        echo "</div>";
    }

    // Focus on User ID 1
    if (isset($userGroups[1])) {
        echo "<h3>🎯 Focus: User ID 1 Processing</h3>";
        $user1Assignments = $userGroups[1];
        echo "<p><strong>User ID 1 has " . count($user1Assignments) . " assignment(s)</strong></p>";

        if (count($user1Assignments) > 1) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4 style='color: #d32f2f;'>🐛 BUG FOUND!</h4>";
            echo "<p>User ID 1 has multiple assignments in the user groups array, but should only have 1.</p>";

            echo "<h5>🔍 Details of each assignment:</h5>";
            foreach ($user1Assignments as $i => $assignment) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: white;'>";
                echo "<h6>Assignment #" . ($i + 1) . "</h6>";
                echo "<ul>";
                foreach (['id', 'user_id', 'role_id', 'status', 'granted_at', 'notes'] as $key) {
                    echo "<li><strong>$key:</strong> " . htmlspecialchars($assignment[$key] ?? 'NULL') . "</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
            echo "<p style='color: green;'>✅ User ID 1 has exactly 1 assignment as expected</p>";
            echo "</div>";
        }

        // Simulate the display processing (from line 952-954)
        echo "<h4>🖥️ Display Processing Simulation</h4>";
        $assignments = $user1Assignments;
        $activeAssignments = array_filter($assignments, function($a) { return $a['status'] === 'active'; });
        $inactiveAssignments = array_filter($assignments, function($a) { return $a['status'] === 'inactive'; });
        $allAssignments = array_merge($activeAssignments, $inactiveAssignments);

        echo "<p><strong>Active assignments:</strong> " . count($activeAssignments) . "</p>";
        echo "<p><strong>Inactive assignments:</strong> " . count($inactiveAssignments) . "</p>";
        echo "<p><strong>All assignments for display:</strong> " . count($allAssignments) . "</p>";

        foreach ($allAssignments as $i => $assignment) {
            echo "<p>&nbsp;&nbsp;&nbsp;🖥️ Display item #{$i}: Assignment #{$assignment['id']}</p>";
        }
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: red;'>❌ User ID 1 not found in user groups!</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>