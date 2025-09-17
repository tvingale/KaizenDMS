<?php
/**
 * Check User Role Assignments
 * Examine current and historical role assignments for any user
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

try {
    $db = getDB();
    echo "<h2>👤 Check User Role Assignments</h2>";

    // Get user ID from form or default to user2
    $checkUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 2;

    echo "<form method='get' style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<label><strong>Check User ID:</strong> <input type='number' name='user_id' value='{$checkUserId}' style='width: 80px; padding: 5px;'></label> ";
    echo "<button type='submit' style='background: #2196f3; color: white; padding: 8px 15px; border: none; border-radius: 3px;'>🔍 Check</button>";
    echo "</form>";

    // Get all role assignments for this user
    $stmt = $db->prepare("
        SELECT ur.user_id, ur.role_id, ur.status, ur.granted_by, ur.granted_at,
               ur.notes, ur.department, ur.assignment_reason,
               r.name as role_name, r.display_name as role_display_name
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
        ORDER BY ur.role_id, ur.granted_at DESC
    ");
    $stmt->execute([$checkUserId]);
    $userRoles = $stmt->fetchAll();

    echo "<h3>📋 Role Assignments for User ID {$checkUserId}</h3>";

    if (empty($userRoles)) {
        echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px;'>";
        echo "<p>ℹ️ <strong>No role assignments found</strong> for User ID {$checkUserId}</p>";
        echo "<p>This user can be assigned any role.</p>";
        echo "</div>";
    } else {
        $activeCount = 0;
        $inactiveCount = 0;
        $departmentOwnerActive = false;
        $departmentOwnerInactive = false;

        // Analyze roles
        foreach ($userRoles as $role) {
            if ($role['status'] === 'active') {
                $activeCount++;
                if (strtolower($role['role_name']) === 'department_owner' || strtolower($role['role_display_name']) === 'department owner') {
                    $departmentOwnerActive = true;
                }
            } else {
                $inactiveCount++;
                if (strtolower($role['role_name']) === 'department_owner' || strtolower($role['role_display_name']) === 'department owner') {
                    $departmentOwnerInactive = true;
                }
            }
        }

        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4>📊 Summary</h4>";
        echo "<ul>";
        echo "<li><strong>Total Assignments:</strong> " . count($userRoles) . "</li>";
        echo "<li><strong>Active Assignments:</strong> {$activeCount}</li>";
        echo "<li><strong>Inactive Assignments:</strong> {$inactiveCount}</li>";
        echo "<li><strong>Department Owner (Active):</strong> " . ($departmentOwnerActive ? 'YES' : 'NO') . "</li>";
        echo "<li><strong>Department Owner (Inactive):</strong> " . ($departmentOwnerInactive ? 'YES' : 'NO') . "</li>";
        echo "</ul>";
        echo "</div>";

        if ($departmentOwnerActive) {
            echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4 style='color: #d32f2f;'>⚠️ Department Owner Role Found</h4>";
            echo "<p><strong>This explains the error!</strong> User {$checkUserId} already has an active Department Owner role.</p>";
            echo "<p><strong>Solutions:</strong></p>";
            echo "<ul>";
            echo "<li>If you want to change the department scope, revoke the existing role first</li>";
            echo "<li>If this is the correct assignment, no action needed</li>";
            echo "<li>If this is a mistake, revoke the incorrect assignment</li>";
            echo "</ul>";
            echo "</div>";
        } elseif ($departmentOwnerInactive) {
            echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4 style='color: #f57c00;'>📝 Inactive Department Owner Role Found</h4>";
            echo "<p>User {$checkUserId} has an inactive Department Owner role. You might want to restore it instead of creating a new one.</p>";
            echo "</div>";
        }

        // Show detailed table
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th>Role ID</th><th>Role Name</th><th>Status</th><th>Department</th><th>Granted By</th><th>Date</th><th>Notes</th>";
        echo "</tr>";

        foreach ($userRoles as $role) {
            $statusColor = $role['status'] === 'active' ? 'green' : 'orange';
            $bgColor = $role['status'] === 'active' ? '#e8f5e8' : '#fff3e0';

            echo "<tr style='background: {$bgColor};'>";
            echo "<td>" . $role['role_id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($role['role_display_name'] ?? $role['role_name'] ?? 'Unknown') . "</strong></td>";
            echo "<td style='color: {$statusColor}; font-weight: bold;'>" . strtoupper($role['status']) . "</td>";
            echo "<td>" . htmlspecialchars($role['department'] ?? 'NULL') . "</td>";
            echo "<td>" . ($role['granted_by'] ?? 'NULL') . "</td>";
            echo "<td>" . ($role['granted_at'] ? date('M j, Y g:i A', strtotime($role['granted_at'])) : 'NULL') . "</td>";
            echo "<td style='font-size: 11px;'>" . htmlspecialchars(substr($role['notes'] ?? '', 0, 40)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Quick actions
    echo "<h3>🔗 Quick Actions</h3>";
    echo "<p>";
    echo "<a href='/module_users.php' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📋 User Management</a>";
    echo "<a href='?user_id=1' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>👑 Check Kaizen Admin</a>";
    echo "<a href='?user_id=2' style='background: #ff9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👤 Check User 2</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #d32f2f;'>❌ Error</h3>";
    echo "<p style='color: #d32f2f;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>