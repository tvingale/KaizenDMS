<?php
/**
 * Debug Query Results
 * Show exactly what the main user roles query returns
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>🐛 Debug Main Query Results</h2>";

    // Replicate the exact query from module_users.php
    $selectColumns = "ur.user_id, ur.role_id, ur.status";
    $selectColumns .= ", ur.granted_by, ur.granted_at, ur.notes";
    $selectColumns .= ", ur.last_access, ur.role_name, ur.department";

    $query = "
        SELECT " . $selectColumns . ", r.name as role_table_name, r.display_name, r.hierarchy_level
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        ORDER BY ur.user_id, ur.status DESC, ur.granted_at DESC
    ";

    echo "<h3>📋 Query Being Executed</h3>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>" . htmlspecialchars($query) . "</pre>";

    $stmt = $db->query($query);
    $results = $stmt->fetchAll();

    echo "<h3>📊 Query Results</h3>";
    echo "<p><strong>Total Records Returned:</strong> " . count($results) . "</p>";

    if (empty($results)) {
        echo "<p style='color: red;'>❌ No records returned by query</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr style='background: #f5f5f5;'>";

        // Headers
        foreach ($results[0] as $key => $value) {
            if (!is_numeric($key)) {
                echo "<th style='padding: 8px;'>" . htmlspecialchars($key) . "</th>";
            }
        }
        echo "</tr>";

        // Data rows
        foreach ($results as $i => $row) {
            $bgColor = $i % 2 == 0 ? '#fff' : '#f9f9f9';
            echo "<tr style='background: $bgColor;'>";

            foreach ($row as $key => $value) {
                if (!is_numeric($key)) {
                    if ($key === 'status') {
                        $color = $value === 'active' ? 'green' : 'orange';
                        echo "<td style='padding: 8px; color: $color; font-weight: bold;'>" . strtoupper(htmlspecialchars($value)) . "</td>";
                    } else {
                        echo "<td style='padding: 8px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                }
            }
            echo "</tr>";
        }
        echo "</table>";

        // Focus on User ID 1
        $user1Records = array_filter($results, function($r) { return $r['user_id'] == 1; });

        echo "<h3>🎯 Records for User ID 1 Only</h3>";
        echo "<p><strong>Count:</strong> " . count($user1Records) . "</p>";

        if (count($user1Records) > 1) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4 style='color: #d32f2f;'>⚠️ FOUND THE BUG!</h4>";
            echo "<p>The query is returning <strong>" . count($user1Records) . " records</strong> for User ID 1, but the database inspection showed only 1 record exists.</p>";
            echo "<p><strong>This suggests:</strong></p>";
            echo "<ul>";
            echo "<li>The JOIN is duplicating records</li>";
            echo "<li>There might be multiple matching roles in dms_roles table</li>";
            echo "<li>The query logic needs to be fixed</li>";
            echo "</ul>";

            echo "<h4>🔍 Detailed Records:</h4>";
            foreach ($user1Records as $i => $record) {
                echo "<h5>Record #" . ($i + 1) . "</h5>";
                echo "<ul>";
                foreach ($record as $key => $value) {
                    if (!is_numeric($key)) {
                        echo "<li><strong>$key:</strong> " . htmlspecialchars($value ?? 'NULL') . "</li>";
                    }
                }
                echo "</ul><br>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
            echo "<p style='color: green;'>✅ Query returns correct number of records for User ID 1</p>";
            echo "</div>";
        }
    }

    // Check roles table for potential duplicates
    echo "<h3>🔍 Check dms_roles Table</h3>";
    $stmt = $db->prepare("SELECT * FROM dms_roles WHERE id = 1");
    $stmt->execute();
    $roleRecords = $stmt->fetchAll();

    echo "<p><strong>Role ID 1 Records:</strong> " . count($roleRecords) . "</p>";
    if (count($roleRecords) > 1) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: red;'>⚠️ <strong>Multiple role records found!</strong> This could cause JOIN duplicates.</p>";
        echo "</div>";
    }

    foreach ($roleRecords as $role) {
        echo "<p><strong>Role:</strong> " . htmlspecialchars($role['name']) . " | <strong>Display:</strong> " . htmlspecialchars($role['display_name'] ?? 'NULL') . "</p>";
    }

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>