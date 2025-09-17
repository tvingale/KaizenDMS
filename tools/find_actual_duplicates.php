<?php
/**
 * Find Actual Duplicate Records
 * Deep dive to find the real source of duplicate records
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🕵️ Find Actual Duplicate Records</h2>";

    // Check EXACTLY what's in the database for user_id = 1
    echo "<h3>1️⃣ Raw Database Query</h3>";
    $stmt = $db->prepare("SELECT * FROM dms_user_roles WHERE user_id = 1");
    $stmt->execute();
    $rawRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Raw records in dms_user_roles for user_id = 1:</strong> " . count($rawRecords) . "</p>";

    foreach ($rawRecords as $i => $record) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;'>";
        echo "<h4>Raw Record #" . ($i + 1) . "</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($record as $key => $value) {
            echo "<tr><td style='background: #f5f5f5; padding: 5px;'><strong>$key</strong></td><td style='padding: 5px;'>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    }

    // Check if it's a JOIN issue creating duplicates
    echo "<h3>2️⃣ JOIN Query Analysis</h3>";
    $stmt = $db->prepare("
        SELECT ur.*, r.name as role_table_name, r.display_name, r.hierarchy_level
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        WHERE ur.user_id = 1
    ");
    $stmt->execute();
    $joinRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Records after JOIN for user_id = 1:</strong> " . count($joinRecords) . "</p>";

    if (count($joinRecords) > count($rawRecords)) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4 style='color: #d32f2f;'>🐛 JOIN IS CREATING DUPLICATES!</h4>";
        echo "<p>Raw records: " . count($rawRecords) . ", JOIN records: " . count($joinRecords) . "</p>";
        echo "<p>This means the JOIN with dms_roles is duplicating records.</p>";
        echo "</div>";
    }

    foreach ($joinRecords as $i => $record) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #fff;'>";
        echo "<h4>JOIN Record #" . ($i + 1) . "</h4>";
        echo "<p><strong>Key fields:</strong> user_id={$record['user_id']}, role_id={$record['role_id']}, status={$record['status']}, granted_at={$record['granted_at']}</p>";
        echo "<p><strong>Role data:</strong> role_table_name={$record['role_table_name']}, display_name={$record['display_name']}</p>";
        echo "</div>";
    }

    // Check for duplicate role records
    echo "<h3>3️⃣ Check dms_roles for Duplicates</h3>";
    $stmt = $db->prepare("SELECT * FROM dms_roles WHERE id = 1");
    $stmt->execute();
    $roleRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Role records for role_id = 1:</strong> " . count($roleRecords) . "</p>";

    if (count($roleRecords) > 1) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4 style='color: #d32f2f;'>🐛 DUPLICATE ROLE RECORDS FOUND!</h4>";
        echo "<p>There are multiple role records with ID = 1, causing JOIN duplicates.</p>";
        echo "</div>";
    }

    foreach ($roleRecords as $i => $role) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #e8f5e8;'>";
        echo "<h4>Role Record #" . ($i + 1) . "</h4>";
        echo "<p><strong>ID:</strong> {$role['id']}, <strong>Name:</strong> {$role['name']}, <strong>Display:</strong> " . htmlspecialchars($role['display_name'] ?? 'NULL') . "</p>";
        echo "</div>";
    }

    // Exact replication of module_users.php logic
    echo "<h3>4️⃣ Replicate Exact module_users.php Logic</h3>";

    // Use the exact same query as module_users.php
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

    // Filter for user 1
    $user1Records = array_filter($userRoleAssignments, function($r) { return $r['user_id'] == 1; });

    echo "<p><strong>Final processed records for user 1:</strong> " . count($user1Records) . "</p>";

    foreach ($user1Records as $i => $assignment) {
        echo "<div style='border: 2px solid #2196f3; padding: 10px; margin: 10px 0; background: #e3f2fd;'>";
        echo "<h4>Final Record #" . ($i + 1) . " (This is what gets displayed)</h4>";
        echo "<p><strong>Composite ID:</strong> {$assignment['user_id']}-{$assignment['role_id']}</p>";
        echo "<p><strong>Display Name:</strong> " . htmlspecialchars($assignment['display_name'] ?? $assignment['role_name']) . "</p>";
        echo "<p><strong>Status:</strong> {$assignment['status']}</p>";
        echo "<p><strong>Granted At:</strong> {$assignment['granted_at']}</p>";
        echo "</div>";
    }

    if (count($user1Records) > 1) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4 style='color: #d32f2f;'>🎯 FOUND THE SOURCE!</h4>";
        echo "<p>The processing logic is creating <strong>" . count($user1Records) . " records</strong> for User ID 1.</p>";
        echo "<p><strong>Next step:</strong> Investigate which stage is creating the duplicates.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>