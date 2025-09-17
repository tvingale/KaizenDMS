<?php
/**
 * Debug Grouping Logic
 * Find exactly where the single record becomes two in the grouping process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🐛 Debug Grouping Logic Step by Step</h2>";

    // Step 1: Raw query results
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
    echo "<p><strong>Total records:</strong> " . count($userRoleAssignments) . "</p>";

    foreach ($userRoleAssignments as $i => $assignment) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: #f9f9f9;'>";
        echo "<h4>Query Record #{$i}</h4>";
        echo "<p><strong>user_id:</strong> {$assignment['user_id']}</p>";
        echo "<p><strong>role_id:</strong> {$assignment['role_id']}</p>";
        echo "<p><strong>status:</strong> {$assignment['status']}</p>";
        echo "<p><strong>granted_at:</strong> {$assignment['granted_at']}</p>";
        echo "<p><strong>display_name:</strong> " . htmlspecialchars($assignment['display_name'] ?? 'NULL') . "</p>";
        echo "</div>";
    }

    // Step 2: Add composite IDs
    echo "<h3>2️⃣ After Adding Composite IDs</h3>";
    foreach ($userRoleAssignments as &$assignment) {
        $assignment['id'] = $assignment['user_id'] . '-' . $assignment['role_id'];
        $assignment['scope'] = $assignment['scope'] ?? 'all';
        $assignment['scope_value'] = $assignment['scope_value'] ?? '';
    }
    unset($assignment);

    echo "<p><strong>Records after ID processing:</strong> " . count($userRoleAssignments) . "</p>";
    foreach ($userRoleAssignments as $i => $assignment) {
        echo "<p><strong>Record #{$i}:</strong> ID={$assignment['id']}, User={$assignment['user_id']}, Role={$assignment['role_id']}</p>";
    }

    // Step 3: Grouping process (replicate exact logic)
    echo "<h3>3️⃣ Grouping Process - Step by Step</h3>";
    $userGroups = [];

    foreach ($userRoleAssignments as $i => $assignment) {
        $userId = $assignment['user_id'];

        echo "<div style='background: #e3f2fd; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
        echo "<h4>Processing Assignment #{$i}</h4>";
        echo "<p><strong>Assignment ID:</strong> {$assignment['id']}</p>";
        echo "<p><strong>User ID:</strong> {$userId}</p>";

        if (!isset($userGroups[$userId])) {
            $userGroups[$userId] = [];
            echo "<p style='color: green;'>✅ Created new group for User {$userId}</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ User {$userId} group already exists with " . count($userGroups[$userId]) . " assignment(s)</p>";
        }

        $userGroups[$userId][] = $assignment;
        echo "<p style='color: blue;'>➕ Added assignment to User {$userId} group. Group now has " . count($userGroups[$userId]) . " assignment(s)</p>";
        echo "</div>";
    }

    // Step 4: Final groups
    echo "<h3>4️⃣ Final User Groups</h3>";
    foreach ($userGroups as $userId => $assignments) {
        echo "<div style='border: 2px solid #2196f3; padding: 15px; margin: 10px 0; background: #f5f5f5;'>";
        echo "<h4>👤 User ID {$userId} - " . count($assignments) . " assignment(s)</h4>";

        foreach ($assignments as $i => $assignment) {
            echo "<div style='margin-left: 20px; padding: 5px; background: white; margin: 5px 0; border-radius: 3px;'>";
            echo "<p><strong>Assignment #{$i}:</strong></p>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> {$assignment['id']}</li>";
            echo "<li><strong>Status:</strong> {$assignment['status']}</li>";
            echo "<li><strong>Role:</strong> " . htmlspecialchars($assignment['display_name'] ?? $assignment['role_name']) . "</li>";
            echo "<li><strong>Granted At:</strong> {$assignment['granted_at']}</li>";
            echo "</ul>";
            echo "</div>";
        }
        echo "</div>";
    }

    // Step 5: Focus on User 1
    if (isset($userGroups[1])) {
        echo "<h3>🎯 User ID 1 Analysis</h3>";
        $user1Assignments = $userGroups[1];
        echo "<p><strong>User 1 has " . count($user1Assignments) . " assignment(s)</strong></p>";

        if (count($user1Assignments) > 1) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4 style='color: #d32f2f;'>🐛 DUPLICATION FOUND!</h4>";
            echo "<p>User 1 has multiple assignments in the final groups array.</p>";

            echo "<h5>📊 Comparison of Duplicates:</h5>";
            for ($i = 0; $i < count($user1Assignments); $i++) {
                for ($j = $i + 1; $j < count($user1Assignments); $j++) {
                    $assign1 = $user1Assignments[$i];
                    $assign2 = $user1Assignments[$j];

                    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: white;'>";
                    echo "<h6>Assignment #{$i} vs Assignment #{$j}</h6>";

                    $fields = ['id', 'user_id', 'role_id', 'status', 'granted_at', 'display_name'];
                    foreach ($fields as $field) {
                        $val1 = $assign1[$field] ?? 'NULL';
                        $val2 = $assign2[$field] ?? 'NULL';
                        $match = $val1 === $val2 ? '✅' : '❌';
                        echo "<p>{$match} <strong>{$field}:</strong> '{$val1}' vs '{$val2}'</p>";
                    }
                    echo "</div>";
                }
            }
            echo "</div>";
        }
    }

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>