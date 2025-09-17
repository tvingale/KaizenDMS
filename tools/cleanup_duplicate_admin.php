<?php
/**
 * Cleanup Duplicate Kaizen Admin Records
 * Remove duplicate admin role assignments for User ID 1
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

$KAIZEN_ADMIN_USER_ID = 1;
$ADMIN_ROLE_ID = 1;

try {
    $db = getDB();
    echo "<h2>🧹 Cleanup Duplicate Kaizen Admin Records</h2>";

    // Check current records for Kaizen Admin
    $stmt = $db->prepare("
        SELECT user_id, role_id, status, granted_by, granted_at, notes, department
        FROM dms_user_roles
        WHERE user_id = ? AND role_id = ?
        ORDER BY granted_at ASC
    ");
    $stmt->execute([$KAIZEN_ADMIN_USER_ID, $ADMIN_ROLE_ID]);
    $records = $stmt->fetchAll();

    echo "<h3>📊 Current Records for Kaizen Admin (User ID 1, Role ID 1)</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th>Status</th><th>Granted By</th><th>Granted At</th><th>Department</th><th>Notes</th>";
    echo "</tr>";

    foreach ($records as $record) {
        $statusColor = $record['status'] === 'active' ? '#4caf50' : '#ff9800';
        echo "<tr>";
        echo "<td style='color: $statusColor; font-weight: bold;'>" . strtoupper($record['status']) . "</td>";
        echo "<td>" . ($record['granted_by'] ?? 'NULL') . "</td>";
        echo "<td>" . ($record['granted_at'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($record['department'] ?? 'NULL') . "</td>";
        echo "<td style='font-size: 11px;'>" . htmlspecialchars(substr($record['notes'] ?? '', 0, 60)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    $totalRecords = count($records);
    $activeRecords = 0;
    $inactiveRecords = 0;

    foreach ($records as $record) {
        if ($record['status'] === 'active') {
            $activeRecords++;
        } else {
            $inactiveRecords++;
        }
    }

    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>📈 Summary</h4>";
    echo "<ul>";
    echo "<li><strong>Total Records:</strong> {$totalRecords}</li>";
    echo "<li><strong>Active Records:</strong> {$activeRecords}</li>";
    echo "<li><strong>Inactive Records:</strong> {$inactiveRecords}</li>";
    echo "</ul>";
    echo "</div>";

    // Only show cleanup option if there are duplicates
    if ($totalRecords > 1) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4 style='color: #d32f2f;'>⚠️ Duplicate Records Detected</h4>";
        echo "<p>Kaizen Admin should have only ONE admin role record. Multiple records can cause display issues.</p>";

        if (isset($_POST['cleanup_action'])) {
            $action = $_POST['cleanup_action'];

            if ($action === 'keep_oldest_active') {
                // Keep the oldest active record, remove all others
                $stmt = $db->prepare("
                    DELETE FROM dms_user_roles
                    WHERE user_id = ? AND role_id = ?
                    AND granted_at != (
                        SELECT min_granted_at FROM (
                            SELECT MIN(granted_at) as min_granted_at
                            FROM dms_user_roles
                            WHERE user_id = ? AND role_id = ? AND status = 'active'
                        ) as subquery
                    )
                ");
                $stmt->execute([$KAIZEN_ADMIN_USER_ID, $ADMIN_ROLE_ID, $KAIZEN_ADMIN_USER_ID, $ADMIN_ROLE_ID]);
                $deleted = $stmt->rowCount();

                echo "<p style='color: green;'>✅ <strong>Cleanup Complete:</strong> Removed {$deleted} duplicate records, kept oldest active record</p>";

            } elseif ($action === 'keep_newest_active') {
                // Keep the newest active record, remove all others
                $stmt = $db->prepare("
                    DELETE FROM dms_user_roles
                    WHERE user_id = ? AND role_id = ?
                    AND granted_at != (
                        SELECT max_granted_at FROM (
                            SELECT MAX(granted_at) as max_granted_at
                            FROM dms_user_roles
                            WHERE user_id = ? AND role_id = ? AND status = 'active'
                        ) as subquery
                    )
                ");
                $stmt->execute([$KAIZEN_ADMIN_USER_ID, $ADMIN_ROLE_ID, $KAIZEN_ADMIN_USER_ID, $ADMIN_ROLE_ID]);
                $deleted = $stmt->rowCount();

                echo "<p style='color: green;'>✅ <strong>Cleanup Complete:</strong> Removed {$deleted} duplicate records, kept newest active record</p>";

            } elseif ($action === 'remove_all_inactive') {
                // Remove all inactive records, keep all active ones
                $stmt = $db->prepare("
                    DELETE FROM dms_user_roles
                    WHERE user_id = ? AND role_id = ? AND status != 'active'
                ");
                $stmt->execute([$KAIZEN_ADMIN_USER_ID, $ADMIN_ROLE_ID]);
                $deleted = $stmt->rowCount();

                echo "<p style='color: green;'>✅ <strong>Cleanup Complete:</strong> Removed {$deleted} inactive records</p>";
            }

            echo "<p><a href='" . $_SERVER['PHP_SELF'] . "' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Refresh to See Results</a></p>";

        } else {
            // Show cleanup options
            echo "<h4>🛠️ Cleanup Options</h4>";
            echo "<form method='post' style='margin: 10px 0;'>";

            if ($activeRecords > 1) {
                echo "<button type='submit' name='cleanup_action' value='keep_oldest_active' class='cleanup-btn' onclick='return confirm(\"Keep only the OLDEST active record and delete all others?\");'>";
                echo "📅 Keep Oldest Active Record";
                echo "</button>";
                echo "<br><br>";

                echo "<button type='submit' name='cleanup_action' value='keep_newest_active' class='cleanup-btn' onclick='return confirm(\"Keep only the NEWEST active record and delete all others?\");'>";
                echo "🆕 Keep Newest Active Record";
                echo "</button>";
                echo "<br><br>";
            }

            if ($inactiveRecords > 0) {
                echo "<button type='submit' name='cleanup_action' value='remove_all_inactive' class='cleanup-btn' onclick='return confirm(\"Remove all inactive records? Active records will be preserved.\");'>";
                echo "🗑️ Remove All Inactive Records ({$inactiveRecords} records)";
                echo "</button>";
            }

            echo "</form>";
        }

        echo "</div>";

        echo "<style>";
        echo ".cleanup-btn { background: #ff9800; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px 0; display: inline-block; text-decoration: none; }";
        echo ".cleanup-btn:hover { background: #f57c00; }";
        echo "</style>";

    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: green;'>✅ <strong>No Duplicates Found:</strong> Kaizen Admin has the correct number of admin role records.</p>";
        echo "</div>";
    }

    // Quick links
    echo "<h3>🔗 Quick Links</h3>";
    echo "<p>";
    echo "<a href='/module_users.php' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📋 Back to User Management</a>";
    echo "<a href='kaizen_admin_access_manager.php' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔒 Admin Access Manager</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>