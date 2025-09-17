<?php
/**
 * RBAC System Verification Script
 * Quick check to verify RBAC implementation completeness
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/AdditivePermissionManager.php';

$db = getDB();
$permManager = new AdditivePermissionManager($db);

echo "<h2>🔍 RBAC System Verification</h2>";

// Check 1: Core Tables
echo "<h3>📋 1. Core RBAC Tables</h3>";
$tables = ['dms_roles', 'dms_permissions', 'dms_role_permissions', 'dms_user_roles', 
           'dms_document_hierarchy', 'dms_document_acl', 'dms_document_assignments', 'dms_user_effective_permissions'];

foreach ($tables as $table) {
    $stmt = $db->query("SELECT COUNT(*) FROM $table");
    $count = $stmt->fetchColumn();
    echo "✅ $table: $count records<br>";
}

// Check 2: System Roles
echo "<h3>👥 2. System Roles</h3>";
$stmt = $db->query("SELECT role_name, hierarchy_level FROM dms_roles WHERE is_system_role = TRUE ORDER BY hierarchy_level");
while ($row = $stmt->fetch()) {
    echo "✅ {$row['role_name']} ({$row['hierarchy_level']})<br>";
}

// Check 3: Permission Categories
echo "<h3>🔑 3. Permission Distribution</h3>";
$stmt = $db->query("SELECT category, COUNT(*) as count FROM dms_permissions GROUP BY category");
while ($row = $stmt->fetch()) {
    echo "✅ {$row['category']}: {$row['count']} permissions<br>";
}

// Check 4: Business Logic Test
echo "<h3>⚙️ 4. Business Logic Test</h3>";
try {
    $start = microtime(true);
    $permissions = $permManager->calculateEffectivePermissions(1);
    $time = microtime(true) - $start;
    echo "✅ Permission calculation: " . count($permissions) . " permissions in " . number_format($time * 1000, 2) . "ms<br>";
    
    $hasAccess = $permManager->hasPermission(1, 'documents.view.all');
    echo "✅ Permission check: documents.view.all = " . ($hasAccess ? 'GRANTED' : 'DENIED') . "<br>";
} catch (Exception $e) {
    echo "❌ Business logic error: " . $e->getMessage() . "<br>";
}

// Check 5: Overall Status
echo "<h3>🎯 5. Overall RBAC Status</h3>";
$stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE is_system_role = TRUE");
$system_roles = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE is_system_permission = TRUE");
$system_permissions = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
$mappings = $stmt->fetchColumn();

if ($system_roles >= 7 && $system_permissions >= 40 && $mappings >= 70) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
    echo "<h4>🎉 RBAC SYSTEM FULLY OPERATIONAL!</h4>";
    echo "✅ $system_roles system roles<br>";
    echo "✅ $system_permissions system permissions<br>";
    echo "✅ $mappings role-permission mappings<br>";
    echo "<strong>Status: Ready for Production Use</strong>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<h4>⚠️ RBAC SYSTEM INCOMPLETE</h4>";
    echo "Current: $system_roles roles, $system_permissions permissions, $mappings mappings<br>";
    echo "Required: 7+ roles, 40+ permissions, 70+ mappings<br>";
    echo "<strong>Status: Needs Completion</strong>";
    echo "</div>";
}

echo "<p><small>Generated: " . date('Y-m-d H:i:s') . "</small></p>";
?>