<?php
/**
 * Direct Fix for Duplicate Kaizen Admin Records
 * Remove exact duplicate admin records for User ID 1
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🔧 Direct Fix: Duplicate Kaizen Admin Records</h2>";

    // First, show current records
    $stmt = $db->prepare("
        SELECT user_id, role_id, status, granted_by, granted_at, notes, department
        FROM dms_user_roles
        WHERE user_id = 1 AND role_id = 1
        ORDER BY granted_at ASC
    ");
    $stmt->execute();
    $records = $stmt->fetchAll();

    echo "<h3>📊 Current Records</h3>";
    foreach ($records as $i => $record) {
        echo "<p><strong>Record " . ($i + 1) . ":</strong> Status: {$record['status']}, Granted: {$record['granted_at']}, By: {$record['granted_by']}</p>";
    }

    if (count($records) > 1) {
        if (isset($_POST['fix_now'])) {
            // Keep only one record - remove duplicates
            // Since dms_user_roles uses composite primary key (user_id, role_id),
            // we can't have true duplicates unless there's a bug

            // Let's check if there are multiple records with same user_id and role_id
            $stmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM dms_user_roles
                WHERE user_id = 1 AND role_id = 1 AND status = 'active'
            ");
            $stmt->execute();
            $activeCount = $stmt->fetchColumn();

            echo "<div style='background: #e3f2fd; padding: 15px; margin: 20px 0;'>";
            echo "<h3>🔄 Fixing Duplicates...</h3>";

            if ($activeCount > 1) {
                // This shouldn't be possible with composite primary key, but let's handle it
                // Keep the oldest record, delete newer ones
                $stmt = $db->prepare("
                    DELETE FROM dms_user_roles
                    WHERE user_id = 1 AND role_id = 1
                    AND granted_at > (
                        SELECT min_granted_at FROM (
                            SELECT MIN(granted_at) as min_granted_at
                            FROM dms_user_roles
                            WHERE user_id = 1 AND role_id = 1 AND status = 'active'
                        ) t
                    )
                ");
                $result = $stmt->execute();
                $deletedRows = $stmt->rowCount();

                echo "<p style='color: green;'>✅ Removed {$deletedRows} duplicate active records</p>";
            }

            // Also clean up any inactive duplicates
            $stmt = $db->prepare("
                DELETE FROM dms_user_roles
                WHERE user_id = 1 AND role_id = 1 AND status != 'active'
            ");
            $stmt->execute();
            $inactiveDeleted = $stmt->rowCount();

            if ($inactiveDeleted > 0) {
                echo "<p style='color: blue;'>ℹ️ Removed {$inactiveDeleted} inactive records</p>";
            }

            echo "<p><a href='/module_users.php' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>✅ Check User Management</a></p>";
            echo "</div>";

        } else {
            echo "<div style='background: #fff3e0; padding: 15px; margin: 20px 0;'>";
            echo "<p><strong>Found {count($records)} records for User ID 1, Role ID 1</strong></p>";
            echo "<p>This should only be 1 record. Click below to fix:</p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='fix_now' style='background: #ff9800; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer;' onclick='return confirm(\"Remove duplicate admin records? This will keep only the oldest active record.\");'>";
            echo "🔧 Fix Duplicates Now";
            echo "</button>";
            echo "</form>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px;'>";
        echo "<p style='color: green;'>✅ No duplicates found - only 1 record exists as expected</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>