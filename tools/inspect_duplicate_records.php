<?php
/**
 * Inspect Duplicate Kaizen Admin Records
 * Show exact database records causing the duplicate display
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🔍 Inspect Duplicate Records for User ID 1</h2>";

    // First, check what columns exist in dms_user_roles
    echo "<h3>📋 Table Structure: dms_user_roles</h3>";
    $stmt = $db->query("DESCRIBE dms_user_roles");
    $columns = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f5f5f5;'><th>Column</th><th>Type</th><th>Key</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Show ALL records for User ID 1
    echo "<h3>🔎 ALL Records for User ID 1</h3>";
    $stmt = $db->prepare("
        SELECT * FROM dms_user_roles
        WHERE user_id = 1
        ORDER BY role_id, granted_at
    ");
    $stmt->execute();
    $allRecords = $stmt->fetchAll();

    if (empty($allRecords)) {
        echo "<p style='color: red;'>❌ No records found for User ID 1</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f5f5f5;'>";

        // Dynamic headers based on actual columns
        foreach ($allRecords[0] as $key => $value) {
            if (!is_numeric($key)) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
        }
        echo "</tr>";

        foreach ($allRecords as $i => $record) {
            $bgColor = $record['status'] === 'active' ? '#e8f5e8' : '#fff3e0';
            echo "<tr style='background: $bgColor;'>";

            foreach ($record as $key => $value) {
                if (!is_numeric($key)) {
                    if ($key === 'status') {
                        $color = $value === 'active' ? 'green' : 'orange';
                        echo "<td style='color: $color; font-weight: bold;'>" . strtoupper(htmlspecialchars($value)) . "</td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                }
            }
            echo "</tr>";
        }
        echo "</table>";

        echo "<p><strong>Total Records Found:</strong> " . count($allRecords) . "</p>";
    }

    // Specifically check for role_id = 1 records
    echo "<h3>🎯 Admin Role Records (role_id = 1) for User ID 1</h3>";
    $stmt = $db->prepare("
        SELECT *
        FROM dms_user_roles
        WHERE user_id = 1 AND role_id = 1
        ORDER BY granted_at
    ");
    $stmt->execute();
    $adminRecords = $stmt->fetchAll();

    if (empty($adminRecords)) {
        echo "<p style='color: red;'>❌ No admin role records found for User ID 1</p>";
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4 style='color: #d32f2f;'>⚠️ Found " . count($adminRecords) . " admin role record(s)</h4>";
        echo "<p>There should only be ONE admin role record for User ID 1.</p>";
        echo "</div>";

        foreach ($adminRecords as $i => $record) {
            echo "<div style='border: 2px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>Record #" . ($i + 1) . "</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";

            foreach ($record as $key => $value) {
                if (!is_numeric($key) && $key !== 'record_number') {
                    echo "<tr>";
                    echo "<td style='background: #f5f5f5; font-weight: bold; padding: 8px;'>" . htmlspecialchars($key) . "</td>";
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
            echo "</div>";
        }

        if (count($adminRecords) > 1) {
            echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>🛠️ Fix Options</h4>";
            echo "<p>Choose how to fix the duplicate records:</p>";

            if (isset($_POST['fix_action'])) {
                $action = $_POST['fix_action'];

                if ($action === 'delete_newest') {
                    // Delete all but the oldest record
                    $stmt = $db->prepare("
                        DELETE FROM dms_user_roles
                        WHERE user_id = 1 AND role_id = 1
                        AND granted_at != (
                            SELECT min_granted FROM (
                                SELECT MIN(granted_at) as min_granted
                                FROM dms_user_roles
                                WHERE user_id = 1 AND role_id = 1
                            ) t
                        )
                    ");
                    $stmt->execute();
                    $deleted = $stmt->rowCount();
                    echo "<p style='color: green;'>✅ Deleted {$deleted} newer duplicate record(s). Kept the oldest.</p>";

                } elseif ($action === 'delete_oldest') {
                    // Delete all but the newest record
                    $stmt = $db->prepare("
                        DELETE FROM dms_user_roles
                        WHERE user_id = 1 AND role_id = 1
                        AND granted_at != (
                            SELECT max_granted FROM (
                                SELECT MAX(granted_at) as max_granted
                                FROM dms_user_roles
                                WHERE user_id = 1 AND role_id = 1
                            ) t
                        )
                    ");
                    $stmt->execute();
                    $deleted = $stmt->rowCount();
                    echo "<p style='color: green;'>✅ Deleted {$deleted} older duplicate record(s). Kept the newest.</p>";
                }

                echo "<p><a href='/module_users.php' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>✅ Check Results</a></p>";

            } else {
                echo "<form method='post' style='margin: 15px 0;'>";
                echo "<button type='submit' name='fix_action' value='delete_newest' style='background: #2196f3; color: white; padding: 12px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;' onclick='return confirm(\"Delete newer records and keep the OLDEST one?\");'>";
                echo "📅 Keep Oldest Record";
                echo "</button><br>";

                echo "<button type='submit' name='fix_action' value='delete_oldest' style='background: #ff9800; color: white; padding: 12px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer;' onclick='return confirm(\"Delete older records and keep the NEWEST one?\");'>";
                echo "🆕 Keep Newest Record";
                echo "</button>";
                echo "</form>";
            }
            echo "</div>";
        }
    }

    // Show index information
    echo "<h3>🔑 Table Indexes</h3>";
    $stmt = $db->query("SHOW INDEX FROM dms_user_roles");
    $indexes = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f5f5f5;'><th>Key Name</th><th>Column</th><th>Unique</th><th>Type</th></tr>";
    foreach ($indexes as $idx) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($idx['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($idx['Column_name']) . "</td>";
        echo "<td>" . ($idx['Non_unique'] == 0 ? 'YES' : 'NO') . "</td>";
        echo "<td>" . htmlspecialchars($idx['Index_type']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>