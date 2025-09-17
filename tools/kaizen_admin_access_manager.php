<?php
/**
 * Kaizen Admin Access Manager
 * Check and protect Kaizen Admin (User ID 1) access records
 * Ensure undeleteable admin access
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/home3/kaizenap/public_html/doms/config.php';
require_once '/home3/kaizenap/public_html/doms/includes/database.php';

$KAIZEN_ADMIN_USER_ID = 1;
$ADMIN_ROLE_ID = 1; // Legacy admin role

try {
    $db = getDB();
    echo "<h2>🔒 Kaizen Admin Access Manager</h2>";
    echo "<p><strong>Kaizen Admin User ID:</strong> {$KAIZEN_ADMIN_USER_ID}</p>";

    // Check if form was submitted
    if ($_POST['action'] ?? '' === 'create_admin_access') {
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🔧 Fixing Kaizen Admin Access...</h3>";

        // Check for existing records (both active and inactive)
        $stmt = $db->prepare("SELECT status, role_id FROM dms_user_roles WHERE user_id = ? AND role_id = ?");
        $stmt->execute([$KAIZEN_ADMIN_USER_ID, $ADMIN_ROLE_ID]);
        $existingRecord = $stmt->fetch();

        if ($existingRecord && $existingRecord['status'] === 'active') {
            echo "<p style='color: orange;'>ℹ️ <strong>Already Active:</strong> Kaizen Admin already has active admin access</p>";
        } elseif ($existingRecord && $existingRecord['status'] !== 'active') {
            // Reactivate existing inactive record
            echo "<p style='color: blue;'>🔄 <strong>Found inactive record, reactivating...</strong></p>";

            $stmt = $db->prepare("
                UPDATE dms_user_roles
                SET status = 'active',
                    granted_by = ?,
                    granted_at = NOW(),
                    notes = ?,
                    assignment_reason = 'system_required'
                WHERE user_id = ? AND role_id = ?
            ");

            $success = $stmt->execute([
                $KAIZEN_ADMIN_USER_ID, // Self-granted
                'PERMANENT - Kaizen System Admin - PROTECTED RECORD - DO NOT DELETE',
                $KAIZEN_ADMIN_USER_ID,
                $ADMIN_ROLE_ID
            ]);

            if ($success) {
                echo "<p style='color: green;'>✅ <strong>Success:</strong> Reactivated existing admin access record</p>";
            } else {
                echo "<p style='color: red;'>❌ <strong>Failed:</strong> Could not reactivate admin access record</p>";
            }
        } else {
            // Create new record if none exists
            echo "<p style='color: blue;'>➕ <strong>No existing record found, creating new one...</strong></p>";

            $stmt = $db->prepare("
                INSERT INTO dms_user_roles
                (user_id, role_id, status, granted_by, granted_at, notes, department, assignment_reason)
                VALUES (?, ?, 'active', ?, NOW(), ?, 'SYSTEM', 'system_required')
            ");

            $success = $stmt->execute([
                $KAIZEN_ADMIN_USER_ID,
                $ADMIN_ROLE_ID,
                $KAIZEN_ADMIN_USER_ID, // Self-granted
                'PERMANENT - Kaizen System Admin - PROTECTED RECORD - DO NOT DELETE',
            ]);

            if ($success) {
                echo "<p style='color: green;'>✅ <strong>Success:</strong> Created protected admin access record</p>";
            } else {
                echo "<p style='color: red;'>❌ <strong>Failed:</strong> Could not create admin access record</p>";
            }
        }
        echo "</div>";
    }

    // STEP 1: Check current access records
    echo "<h3>📊 Step 1: Current Access Records for User ID {$KAIZEN_ADMIN_USER_ID}</h3>";

    $stmt = $db->prepare("
        SELECT ur.user_id, ur.role_id, ur.status, ur.granted_by, ur.granted_at, ur.notes,
               ur.department, ur.assignment_reason, r.name as role_name, r.display_name
        FROM dms_user_roles ur
        LEFT JOIN dms_roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
        ORDER BY ur.status DESC, ur.granted_at DESC
    ");
    $stmt->execute([$KAIZEN_ADMIN_USER_ID]);
    $userRecords = $stmt->fetchAll();

    if (empty($userRecords)) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; border-left: 4px solid #f44336;'>";
        echo "<p style='color: #d32f2f;'><strong>⚠️ CRITICAL:</strong> No access records found for Kaizen Admin!</p>";
        echo "<p>This explains why you're getting 'user has no access' errors.</p>";
        echo "</div>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th>Role ID</th><th>Role Name</th><th>Status</th><th>Department</th><th>Granted By</th><th>Date</th><th>Notes</th><th>Reason</th>";
        echo "</tr>";

        foreach ($userRecords as $record) {
            $statusColor = $record['status'] === 'active' ? '#4caf50' : '#ff9800';
            echo "<tr>";
            echo "<td>" . $record['role_id'] . "</td>";
            echo "<td>" . htmlspecialchars($record['role_name'] ?? 'Unknown') . "</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>" . strtoupper($record['status']) . "</td>";
            echo "<td>" . htmlspecialchars($record['department'] ?? 'NULL') . "</td>";
            echo "<td>" . ($record['granted_by'] ?? 'NULL') . "</td>";
            echo "<td>" . ($record['granted_at'] ?? 'NULL') . "</td>";
            echo "<td style='font-size: 11px;'>" . htmlspecialchars(substr($record['notes'] ?? '', 0, 50)) . "</td>";
            echo "<td>" . htmlspecialchars($record['assignment_reason'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // STEP 2: Check if admin role exists
    echo "<h3>🛡️ Step 2: Admin Role Verification</h3>";

    $stmt = $db->prepare("SELECT id, name, display_name, is_system_role FROM dms_roles WHERE id = ?");
    $stmt->execute([$ADMIN_ROLE_ID]);
    $adminRole = $stmt->fetch();

    if ($adminRole) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: green;'>✅ <strong>Admin Role Found:</strong></p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $adminRole['id'] . "</li>";
        echo "<li><strong>Name:</strong> " . htmlspecialchars($adminRole['name']) . "</li>";
        echo "<li><strong>Display Name:</strong> " . htmlspecialchars($adminRole['display_name'] ?? 'NULL') . "</li>";
        echo "<li><strong>System Role:</strong> " . ($adminRole['is_system_role'] ? 'YES' : 'NO') . "</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: red;'>❌ <strong>Admin Role Missing:</strong> Role ID {$ADMIN_ROLE_ID} not found!</p>";
        echo "</div>";
    }

    // STEP 3: Fix Admin Access
    echo "<h3>🔧 Step 3: Fix Admin Access</h3>";

    $activeCount = 0;
    $hasInactiveAdminRole = false;
    foreach ($userRecords as $record) {
        if ($record['status'] === 'active') $activeCount++;
        if ($record['role_id'] == $ADMIN_ROLE_ID && $record['status'] !== 'active') {
            $hasInactiveAdminRole = true;
        }
    }

    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<p><strong>Current Status:</strong></p>";
    echo "<ul>";
    echo "<li>Active Records: <strong>{$activeCount}</strong></li>";
    echo "<li>Inactive Admin Role: <strong>" . ($hasInactiveAdminRole ? 'YES (needs reactivation)' : 'NO') . "</strong></li>";
    echo "<li>Admin Role Exists: <strong>" . ($adminRole ? 'YES' : 'NO') . "</strong></li>";
    echo "<li>Action Needed: <strong>" . (($activeCount === 0 && $adminRole) ? 'YES' : 'NO') . "</strong></li>";
    echo "</ul>";
    echo "</div>";

    if ($activeCount === 0 && $adminRole) {
        $buttonText = $hasInactiveAdminRole ? "🔄 Reactivate Admin Access" : "🔒 Create Protected Admin Access";
        $confirmText = $hasInactiveAdminRole ? "Reactivate existing admin access record?" : "Create protected admin access record for Kaizen Admin?";
        $description = $hasInactiveAdminRole ? "This will reactivate the existing inactive admin record for User ID 1." : "This will create an undeleteable admin access record for User ID 1.";

        echo "<form method='post' style='margin: 20px 0;'>";
        echo "<input type='hidden' name='action' value='create_admin_access'>";
        echo "<button type='submit' style='background: #4caf50; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;' onclick='return confirm(\"{$confirmText}\");'>";
        echo $buttonText;
        echo "</button>";
        echo "</form>";
        echo "<p style='color: #666; font-size: 14px;'>{$description}</p>";
    } elseif ($activeCount > 0) {
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: #1976d2;'>ℹ️ <strong>No Action Needed:</strong> User already has active access record(s).</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
        echo "<p style='color: #d32f2f;'>⚠️ <strong>Cannot Fix:</strong> Admin role is missing. Please create admin role first.</p>";
        echo "</div>";
    }

    // STEP 4: Recommendations
    echo "<h3>💡 Step 4: Recommendations</h3>";
    echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 5px;'>";
    echo "<h4>To prevent future issues:</h4>";
    echo "<ol>";
    echo "<li><strong>UI Protection:</strong> Hide delete button for Kaizen Admin in module_users.php</li>";
    echo "<li><strong>Database Protection:</strong> Add triggers to prevent deletion of User ID 1 records</li>";
    echo "<li><strong>Code Protection:</strong> Add checks in delete operations</li>";
    echo "<li><strong>Backup:</strong> Always backup before role management operations</li>";
    echo "</ol>";
    echo "</div>";

    // STEP 5: Quick Links
    echo "<h3>🔗 Quick Links</h3>";
    echo "<p>";
    echo "<a href='/module_users.php' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📋 User Management</a>";
    echo "<a href='/admin/roles_permissions.php' style='background: #ff9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛡️ Roles & Permissions</a>";
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