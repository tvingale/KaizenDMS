<?php
/**
 * Hide Legacy Roles from UI while preserving functionality
 * Keep admin, manager, user for code compatibility but hide from forms
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();

    echo "<h2>Hiding Legacy Roles from UI</h2>";

    // Add a 'hidden_from_ui' flag to legacy roles
    echo "<h3>Step 1: Add hidden_from_ui column if it doesn't exist...</h3>";
    try {
        $db->exec("ALTER TABLE dms_roles ADD COLUMN hidden_from_ui BOOLEAN DEFAULT FALSE");
        echo "<p style='color: green;'>✅ Added hidden_from_ui column</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: blue;'>ℹ️ Column already exists</p>";
        } else {
            throw $e;
        }
    }

    // Hide legacy roles from UI but keep them functional
    echo "<h3>Step 2: Hide legacy roles from UI...</h3>";
    $stmt = $db->prepare("
        UPDATE dms_roles
        SET hidden_from_ui = TRUE,
            description = CONCAT(COALESCE(description, ''), ' [LEGACY - Hidden from UI]')
        WHERE name IN ('manager', 'user')
    ");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Hidden manager and user roles from UI (affected rows: " . $stmt->rowCount() . ")</p>";

    // Protect admin role and keep it visible
    echo "<h3>Step 3: Protect admin role...</h3>";
    $stmt = $db->prepare("
        UPDATE dms_roles
        SET hidden_from_ui = FALSE,
            is_system_role = TRUE,
            description = 'System Administrator - PERMANENT ROLE for Kaizen Admin'
        WHERE name = 'admin'
    ");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Protected admin role (affected rows: " . $stmt->rowCount() . ")</p>";

    // Protect Kaizen Admin's assignment
    echo "<h3>Step 4: Protect Kaizen Admin assignment...</h3>";
    $stmt = $db->prepare("
        UPDATE dms_user_roles
        SET notes = 'PERMANENT - Kaizen System Admin - DO NOT REMOVE',
            assignment_reason = 'system_required'
        WHERE user_id = 1 AND role_id = 1
    ");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Protected Kaizen Admin assignment (affected rows: " . $stmt->rowCount() . ")</p>";

    // Show current status
    echo "<h3>Current Role Status:</h3>";
    $stmt = $db->query("
        SELECT id, name, display_name, is_system_role, hidden_from_ui,
               CASE WHEN hidden_from_ui THEN 'HIDDEN' ELSE 'VISIBLE' END as ui_status
        FROM dms_roles
        WHERE name IN ('admin', 'manager', 'user')
        ORDER BY id
    ");
    $roles = $stmt->fetchAll();

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Display Name</th><th>System Role</th><th>UI Status</th></tr>";
    foreach ($roles as $role) {
        $color = $role['ui_status'] == 'HIDDEN' ? '#ffcccc' : '#ccffcc';
        echo "<tr style='background-color: $color;'>";
        echo "<td>" . $role['id'] . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['display_name']) . "</td>";
        echo "<td>" . ($role['is_system_role'] ? 'YES' : 'NO') . "</td>";
        echo "<td><strong>" . $role['ui_status'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>✅ Complete! Now update the role selection query...</h3>";
    echo "<p>Legacy roles are hidden from UI but remain functional for code compatibility.</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>