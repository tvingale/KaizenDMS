<?php
/**
 * Delete User 2 Records
 * Safely remove User ID 2 from dms_user_roles so they can be reassigned properly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🗑️ Delete User 2 Records</h2>";

    $USER_ID_TO_DELETE = 2;

    // First, show what records exist for User 2
    echo "<h3>📋 Current Records for User ID {$USER_ID_TO_DELETE}</h3>";
    $stmt = $db->prepare("SELECT * FROM dms_user_roles WHERE user_id = ?");
    $stmt->execute([$USER_ID_TO_DELETE]);
    $records = $stmt->fetchAll();

    if (empty($records)) {
        echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: #f57c00;'>ℹ️ <strong>No records found</strong> for User ID {$USER_ID_TO_DELETE}</p>";
        echo "<p>User {$USER_ID_TO_DELETE} has no role assignments to delete.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<p><strong>Found " . count($records) . " record(s) for User ID {$USER_ID_TO_DELETE}</strong></p>";
        echo "</div>";

        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th>Role ID</th><th>Status</th><th>Granted By</th><th>Granted At</th><th>Notes</th><th>Department</th><th>Assignment Reason</th>";
        echo "</tr>";

        foreach ($records as $record) {
            $statusColor = $record['status'] === 'active' ? 'green' : 'orange';
            echo "<tr>";
            echo "<td><strong>" . $record['role_id'] . "</strong></td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>" . strtoupper($record['status']) . "</td>";
            echo "<td>" . ($record['granted_by'] ?? 'NULL') . "</td>";
            echo "<td>" . ($record['granted_at'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars(substr($record['notes'] ?? 'NULL', 0, 30)) . "</td>";
            echo "<td>" . htmlspecialchars($record['department'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($record['assignment_reason'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Check if deletion was requested
        if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3 style='color: #d32f2f;'>🗑️ Deleting Records...</h3>";

            // Perform the deletion
            $stmt = $db->prepare("DELETE FROM dms_user_roles WHERE user_id = ?");
            $success = $stmt->execute([$USER_ID_TO_DELETE]);
            $deletedCount = $stmt->rowCount();

            if ($success && $deletedCount > 0) {
                echo "<p style='color: green;'>✅ <strong>Success!</strong> Deleted {$deletedCount} record(s) for User ID {$USER_ID_TO_DELETE}</p>";
                echo "<p style='color: blue;'>ℹ️ User {$USER_ID_TO_DELETE} can now be reassigned with proper role data through the UI</p>";

                echo "<div style='margin: 20px 0;'>";
                echo "<a href='/module_users.php' style='background: #4caf50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>✅ Go to User Management</a>";
                echo "<a href='inspect_user_roles_table.php' style='background: #2196f3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>🔍 Verify Deletion</a>";
                echo "</div>";
            } else {
                echo "<p style='color: red;'>❌ <strong>Failed!</strong> Could not delete records or no records found</p>";
            }
            echo "</div>";

        } else {
            // Show deletion confirmation form
            echo "<div style='background: #fff3e0; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3 style='color: #f57c00;'>⚠️ Confirm Deletion</h3>";
            echo "<p><strong>This will permanently delete all role assignments for User ID {$USER_ID_TO_DELETE}</strong></p>";
            echo "<p>After deletion, you can reassign the user through the UI with proper department, scope, and audit data.</p>";

            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4 style='color: #2e7d32;'>✅ Benefits of Deletion & Reassignment:</h4>";
            echo "<ul>";
            echo "<li>Proper <strong>department assignment</strong> (currently missing)</li>";
            echo "<li>Complete <strong>audit trail</strong> (assignment reason, notes)</li>";
            echo "<li>Correct <strong>role naming</strong> (role_name field)</li>";
            echo "<li>Proper <strong>scope configuration</strong> (department-specific permissions)</li>";
            echo "<li>Clean <strong>data consistency</strong></li>";
            echo "</ul>";
            echo "</div>";

            echo "<form method='post' style='margin: 20px 0;'>";
            echo "<input type='hidden' name='confirm_delete' value='yes'>";
            echo "<button type='submit' style='background: #f44336; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-right: 10px;' onclick='return confirm(\"Are you sure you want to delete all role assignments for User ID " . $USER_ID_TO_DELETE . "? This action cannot be undone.\");'>";
            echo "🗑️ Delete User {$USER_ID_TO_DELETE} Records";
            echo "</button>";
            echo "</form>";

            echo "<div style='margin: 20px 0;'>";
            echo "<a href='/module_users.php' style='background: #9e9e9e; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>↩️ Cancel - Back to User Management</a>";
            echo "</div>";
            echo "</div>";
        }
    }

    // Show impact assessment
    if (!empty($records) && !isset($_POST['confirm_delete'])) {
        echo "<h3>📊 Impact Assessment</h3>";
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Current State:</h4>";
        echo "<ul>";
        foreach ($records as $record) {
            echo "<li>Role ID {$record['role_id']} ({$record['status']}) - Missing: department, proper audit data</li>";
        }
        echo "</ul>";

        echo "<h4>After Deletion & Reassignment:</h4>";
        echo "<ul>";
        echo "<li>✅ Complete role data with department assignment</li>";
        echo "<li>✅ Proper audit trail (who, when, why)</li>";
        echo "<li>✅ Department-specific permissions working correctly</li>";
        echo "<li>✅ Consistent data structure matching User 1</li>";
        echo "</ul>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>