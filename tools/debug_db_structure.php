<?php
/**
 * Debug Database Structure
 * Quick script to check what columns exist in RBAC tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();

    echo "<h2>Database Structure Check</h2>";

    // Check dms_user_roles structure
    echo "<h3>dms_user_roles columns:</h3>";
    try {
        $stmt = $db->query("DESCRIBE dms_user_roles");
        $columns = $stmt->fetchAll();
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li><strong>" . $col['Field'] . "</strong> (" . $col['Type'] . ")" . ($col['Key'] ? " - " . $col['Key'] : "") . "</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p>Error checking dms_user_roles: " . $e->getMessage() . "</p>";
    }

    // Check dms_roles structure
    echo "<h3>dms_roles columns:</h3>";
    try {
        $stmt = $db->query("DESCRIBE dms_roles");
        $columns = $stmt->fetchAll();
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li><strong>" . $col['Field'] . "</strong> (" . $col['Type'] . ")" . ($col['Key'] ? " - " . $col['Key'] : "") . "</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p>Error checking dms_roles: " . $e->getMessage() . "</p>";
    }

    // Test specific column detection
    echo "<h3>Column Detection Tests:</h3>";

    // Test dms_user_roles.id
    try {
        $stmt = $db->query("SHOW COLUMNS FROM dms_user_roles LIKE 'id'");
        $hasId = $stmt->fetchColumn();
        echo "<p>dms_user_roles has 'id' column: " . ($hasId ? "YES" : "NO") . "</p>";
    } catch (Exception $e) {
        echo "<p>Error testing dms_user_roles.id: " . $e->getMessage() . "</p>";
    }

    // Test dms_roles.id
    try {
        $stmt = $db->query("SHOW COLUMNS FROM dms_roles LIKE 'id'");
        $hasId = $stmt->fetchColumn();
        echo "<p>dms_roles has 'id' column: " . ($hasId ? "YES" : "NO") . "</p>";
    } catch (Exception $e) {
        echo "<p>Error testing dms_roles.id: " . $e->getMessage() . "</p>";
    }

    // Sample data
    echo "<h3>Sample Data:</h3>";
    try {
        $stmt = $db->query("SELECT * FROM dms_roles LIMIT 3");
        $roles = $stmt->fetchAll();
        echo "<h4>Sample Roles:</h4><pre>";
        print_r($roles);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p>Error getting sample roles: " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<p>Database connection error: " . $e->getMessage() . "</p>";
}
?>