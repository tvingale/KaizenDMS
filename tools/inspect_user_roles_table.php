<?php
/**
 * Inspect User Roles Table
 * Show all raw data from dms_user_roles table to identify duplicates
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>📋 Complete dms_user_roles Table Data</h2>";

    // Get table structure first
    echo "<h3>🏗️ Table Structure</h3>";
    $stmt = $db->query("DESCRIBE dms_user_roles");
    $columns = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #f5f5f5;'><th>Column</th><th>Type</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Get all records
    echo "<h3>📊 All Records in dms_user_roles</h3>";
    $stmt = $db->query("SELECT * FROM dms_user_roles ORDER BY user_id, role_id, granted_at");
    $records = $stmt->fetchAll();

    echo "<p><strong>Total Records:</strong> " . count($records) . "</p>";

    if (empty($records)) {
        echo "<p style='color: red;'>❌ No records found in dms_user_roles table</p>";
    } else {
        // Create a responsive table with all columns
        echo "<div style='overflow-x: auto; margin: 20px 0;'>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";

        // Header
        echo "<tr style='background: #e3f2fd;'>";
        foreach ($records[0] as $key => $value) {
            if (!is_numeric($key)) {
                echo "<th style='padding: 8px; white-space: nowrap;'>" . htmlspecialchars($key) . "</th>";
            }
        }
        echo "</tr>";

        // Data rows
        foreach ($records as $i => $record) {
            $bgColor = $i % 2 == 0 ? '#fff' : '#f9f9f9';

            // Highlight potential duplicates
            if ($record['user_id'] == 1 && $record['role_id'] == 1) {
                $bgColor = '#ffebee'; // Light red for User 1, Role 1 records
            }

            echo "<tr style='background: $bgColor;'>";
            foreach ($record as $key => $value) {
                if (!is_numeric($key)) {
                    if ($key === 'status') {
                        $color = $value === 'active' ? 'green' : 'orange';
                        echo "<td style='padding: 6px; color: $color; font-weight: bold;'>" . strtoupper(htmlspecialchars($value)) . "</td>";
                    } else {
                        echo "<td style='padding: 6px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                }
            }
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";

        // Focus on potential duplicates
        echo "<h3>🔍 Analysis: Potential Duplicate Records</h3>";

        // Group by user_id and role_id to find duplicates
        $grouped = [];
        foreach ($records as $record) {
            $key = $record['user_id'] . '-' . $record['role_id'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $record;
        }

        $hasDuplicates = false;
        foreach ($grouped as $key => $group) {
            if (count($group) > 1) {
                $hasDuplicates = true;
                list($userId, $roleId) = explode('-', $key);

                echo "<div style='background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #f44336;'>";
                echo "<h4 style='color: #d32f2f;'>⚠️ Duplicate Found: User ID {$userId}, Role ID {$roleId}</h4>";
                echo "<p><strong>Number of records:</strong> " . count($group) . "</p>";

                echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 11px;'>";
                echo "<tr style='background: #f5f5f5;'>";
                echo "<th>Status</th><th>Granted By</th><th>Granted At</th><th>Notes</th><th>Department</th>";
                echo "</tr>";

                foreach ($group as $record) {
                    echo "<tr>";
                    echo "<td style='color: " . ($record['status'] === 'active' ? 'green' : 'orange') . "; font-weight: bold;'>" . strtoupper($record['status']) . "</td>";
                    echo "<td>" . htmlspecialchars($record['granted_by'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($record['granted_at'] ?? 'NULL') . "</td>";
                    echo "<td style='max-width: 200px; word-wrap: break-word;'>" . htmlspecialchars(substr($record['notes'] ?? '', 0, 50)) . "...</td>";
                    echo "<td>" . htmlspecialchars($record['department'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            }
        }

        if (!$hasDuplicates) {
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
            echo "<p style='color: green;'>✅ <strong>No duplicates found</strong> - Each user+role combination appears only once</p>";
            echo "</div>";
        }

        // Summary statistics
        echo "<h3>📈 Summary Statistics</h3>";
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";

        $totalActive = 0;
        $totalInactive = 0;
        $uniqueUsers = [];

        foreach ($records as $record) {
            if ($record['status'] === 'active') {
                $totalActive++;
            } else {
                $totalInactive++;
            }
            $uniqueUsers[$record['user_id']] = true;
        }

        echo "<ul>";
        echo "<li><strong>Total Records:</strong> " . count($records) . "</li>";
        echo "<li><strong>Active Records:</strong> {$totalActive}</li>";
        echo "<li><strong>Inactive Records:</strong> {$totalInactive}</li>";
        echo "<li><strong>Unique Users:</strong> " . count($uniqueUsers) . "</li>";
        echo "<li><strong>Duplicate Groups:</strong> " . count(array_filter($grouped, function($group) { return count($group) > 1; })) . "</li>";
        echo "</ul>";
        echo "</div>";

        // Show records for specific users
        echo "<h3>🎯 Focus: User ID 1 Records</h3>";
        $user1Records = array_filter($records, function($r) { return $r['user_id'] == 1; });

        if (empty($user1Records)) {
            echo "<p style='color: red;'>❌ No records found for User ID 1</p>";
        } else {
            echo "<p><strong>User ID 1 has " . count($user1Records) . " record(s)</strong></p>";
            foreach ($user1Records as $i => $record) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: white;'>";
                echo "<h5>Record #" . ($i + 1) . "</h5>";
                echo "<ul>";
                echo "<li><strong>Role ID:</strong> {$record['role_id']}</li>";
                echo "<li><strong>Status:</strong> <span style='color: " . ($record['status'] === 'active' ? 'green' : 'orange') . ";'>" . strtoupper($record['status']) . "</span></li>";
                echo "<li><strong>Granted At:</strong> {$record['granted_at']}</li>";
                echo "<li><strong>Granted By:</strong> {$record['granted_by']}</li>";
                echo "<li><strong>Notes:</strong> " . htmlspecialchars(substr($record['notes'] ?? '', 0, 100)) . "</li>";
                echo "</ul>";
                echo "</div>";
            }
        }
    }

    echo "<h3>🔗 Quick Actions</h3>";
    echo "<p>";
    echo "<a href='/module_users.php' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📋 Back to User Management</a>";
    echo "<a href='debug_grouping_logic.php' style='background: #ff9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🐛 Debug Grouping Logic</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 3px; font-size: 12px;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div>";
}
?>