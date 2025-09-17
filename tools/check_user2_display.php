<?php
/**
 * Check User 2 Display Issue
 * Debug why User 2 isn't showing in the main table after reassignment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🔍 Debug User 2 Display Issue</h2>";

    // Step 1: Check raw database
    echo "<h3>1️⃣ Raw Database Check</h3>";
    $stmt = $db->query("SELECT * FROM dms_user_roles ORDER BY user_id, role_id");
    $allRecords = $stmt->fetchAll();

    echo "<p><strong>Total records in database:</strong> " . count($allRecords) . "</p>";
    foreach ($allRecords as $record) {
        $statusColor = $record['status'] === 'active' ? 'green' : 'orange';
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: " . ($record['user_id'] == 2 ? '#e3f2fd' : '#f9f9f9') . ";'>";
        echo "<h4>User {$record['user_id']}, Role {$record['role_id']}</h4>";
        echo "<p><strong>Status:</strong> <span style='color: $statusColor;'>{$record['status']}</span></p>";
        echo "<p><strong>Granted:</strong> {$record['granted_at']}</p>";
        echo "<p><strong>Role Name:</strong> " . htmlspecialchars($record['role_name'] ?? 'NULL') . "</p>";
        echo "<p><strong>Department:</strong> " . htmlspecialchars($record['department'] ?? 'NULL') . "</p>";
        echo "</div>";
    }

    // Step 2: Replicate the exact query from module_users.php
    echo "<h3>2️⃣ Main Query Replication</h3>";
    $selectColumns = "ur.user_id, ur.role_id, ur.status";
    $selectColumns .= ", ur.granted_by, ur.granted_at, ur.notes";
    $selectColumns .= ", ur.last_access, ur.role_name, ur.department";

    $query = "
        SELECT " . $selectColumns . ", r.name as role_table_name, r.display_name, r.hierarchy_level
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        ORDER BY ur.user_id, ur.status DESC, ur.granted_at DESC
    ";

    echo "<p><strong>Query:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px;'>" . htmlspecialchars($query) . "</pre>";

    $stmt = $db->query($query);
    $queryResults = $stmt->fetchAll();

    echo "<p><strong>Query results:</strong> " . count($queryResults) . " records</p>";
    foreach ($queryResults as $i => $result) {
        $bg = $result['user_id'] == 2 ? '#ffebee' : '#f5f5f5';
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: $bg;'>";
        echo "<h4>Query Result #{$i}: User {$result['user_id']}</h4>";
        echo "<ul>";
        echo "<li><strong>Role ID:</strong> {$result['role_id']}</li>";
        echo "<li><strong>Status:</strong> {$result['status']}</li>";
        echo "<li><strong>Role Table Name:</strong> " . htmlspecialchars($result['role_table_name'] ?? 'NULL') . "</li>";
        echo "<li><strong>Display Name:</strong> " . htmlspecialchars($result['display_name'] ?? 'NULL') . "</li>";
        echo "<li><strong>User Role Name:</strong> " . htmlspecialchars($result['role_name'] ?? 'NULL') . "</li>";
        echo "<li><strong>Department:</strong> " . htmlspecialchars($result['department'] ?? 'NULL') . "</li>";
        echo "</ul>";
        echo "</div>";
    }

    // Step 3: Simulate the grouping process
    echo "<h3>3️⃣ Grouping Process Simulation</h3>";

    // Add composite IDs
    foreach ($queryResults as &$assignment) {
        $assignment['id'] = $assignment['user_id'] . '-' . $assignment['role_id'];
        $assignment['scope'] = $assignment['scope'] ?? 'all';
        $assignment['scope_value'] = $assignment['scope_value'] ?? '';
    }
    unset($assignment);

    // Group by user with duplicate detection
    $userGroups = [];
    foreach ($queryResults as $assignment) {
        $userId = $assignment['user_id'];
        if (!isset($userGroups[$userId])) {
            $userGroups[$userId] = [];
        }

        // Check for duplicates
        $isDuplicate = false;
        foreach ($userGroups[$userId] as $existingAssignment) {
            if ($existingAssignment['user_id'] == $assignment['user_id'] &&
                $existingAssignment['role_id'] == $assignment['role_id'] &&
                $existingAssignment['status'] == $assignment['status'] &&
                $existingAssignment['granted_at'] == $assignment['granted_at']) {
                $isDuplicate = true;
                break;
            }
        }

        if (!$isDuplicate) {
            $userGroups[$userId][] = $assignment;
        }
    }

    echo "<p><strong>User groups created:</strong> " . count($userGroups) . "</p>";
    foreach ($userGroups as $userId => $assignments) {
        $bg = $userId == 2 ? '#fff3e0' : '#e8f5e8';
        echo "<div style='border: 2px solid #2196f3; padding: 15px; margin: 10px 0; background: $bg;'>";
        echo "<h4>👤 User ID {$userId} - " . count($assignments) . " assignment(s)</h4>";

        foreach ($assignments as $i => $assignment) {
            echo "<div style='margin-left: 20px; padding: 10px; background: white; margin: 5px 0; border-radius: 3px;'>";
            echo "<p><strong>Assignment #{$i}:</strong></p>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> {$assignment['id']}</li>";
            echo "<li><strong>Status:</strong> {$assignment['status']}</li>";
            echo "<li><strong>Display Name:</strong> " . htmlspecialchars($assignment['display_name'] ?? $assignment['role_name'] ?? 'Unknown') . "</li>";
            echo "<li><strong>Department:</strong> " . htmlspecialchars($assignment['department'] ?? 'NULL') . "</li>";
            echo "</ul>";
            echo "</div>";
        }
        echo "</div>";
    }

    // Step 4: Check for any filtering issues
    echo "<h3>4️⃣ Display Filter Check</h3>";
    if (isset($userGroups[2])) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: green;'>✅ <strong>User 2 found in user groups!</strong></p>";
        echo "<p>User 2 should appear in the display table. If it's not showing, there might be a frontend rendering issue or caching problem.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: red;'>❌ <strong>User 2 missing from user groups!</strong></p>";
        echo "<p>This indicates an issue in the query or grouping logic.</p>";
        echo "</div>";
    }

    // Step 5: Role lookup check
    echo "<h3>5️⃣ Role Lookup Verification</h3>";
    $user2Records = array_filter($queryResults, function($r) { return $r['user_id'] == 2; });
    if (!empty($user2Records)) {
        $user2Record = array_values($user2Records)[0];
        $roleId = $user2Record['role_id'];

        echo "<p><strong>User 2's Role ID:</strong> {$roleId}</p>";

        $stmt = $db->prepare("SELECT * FROM dms_roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $roleInfo = $stmt->fetch();

        if ($roleInfo) {
            echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
            echo "<p style='color: green;'>✅ <strong>Role found in dms_roles table:</strong></p>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> {$roleInfo['id']}</li>";
            echo "<li><strong>Name:</strong> " . htmlspecialchars($roleInfo['name']) . "</li>";
            echo "<li><strong>Display Name:</strong> " . htmlspecialchars($roleInfo['display_name'] ?? 'NULL') . "</li>";
            echo "<li><strong>Status:</strong> " . htmlspecialchars($roleInfo['status'] ?? 'NULL') . "</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div style='background: #ffebee; padding: 10px; border-radius: 5px;'>";
            echo "<p style='color: red;'>❌ <strong>Role ID {$roleId} not found in dms_roles table!</strong></p>";
            echo "<p>This could cause the JOIN to fail and User 2 to be filtered out.</p>";
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