<?php
/**
 * RBAC User Management
 * Admin interface to manage user roles and scope-based permissions
 */

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/kaizen_sso.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/UserDisplayHelper.php';
require_once 'includes/KaizenAuthAPI.php';
require_once 'includes/AccessControl.php';

// Initialize RBAC system
if (file_exists(__DIR__ . '/includes/AdditivePermissionManager.php')) {
    require_once 'includes/AdditivePermissionManager.php';
}

// Initialize SSO
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);

if (!$sso->isAuthenticated()) {
    header('Location: sso.php');
    exit;
}

$user = $sso->getUserInfo();
$db = getDB();

// Use unified access control - require admin access
$accessControl = AccessControl::requireAccess('admin');

// Initialize RBAC manager if available
$permissionManager = null;
if (class_exists('AdditivePermissionManager')) {
    try {
        $permissionManager = new AdditivePermissionManager($db);
    } catch (Exception $e) {
        error_log("Failed to initialize AdditivePermissionManager: " . $e->getMessage());
    }
}

// Generate CSRF token for this page
if (!isset($_SESSION['module_users_csrf_token'])) {
    $_SESSION['module_users_csrf_token'] = bin2hex(random_bytes(32));
}

// Set page title
$pageTitle = 'RBAC User Management';

// Handle form submissions
$success = '';
$error = '';

if ($_POST) {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['module_users_csrf_token']) ||
        !hash_equals($_SESSION['module_users_csrf_token'], $_POST['csrf_token'])) {

        $_SESSION['flash_message'] = 'Security token invalid. Please refresh the page and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: module_users.php');
        exit;
    }

    try {
        if (isset($_POST['assign_roles'])) {
            // Multi-role assignment
            $kaizenUserId = intval($_POST['kaizen_user_id']);
            $kaizenUsername = trim($_POST['kaizen_username']);
            $kaizenEmail = trim($_POST['kaizen_email']);
            $kaizenName = trim($_POST['kaizen_name']);
            $selectedRoles = $_POST['selected_roles'] ?? [];
            $scope = trim($_POST['scope']) ?? 'all';
            $scopeValue = trim($_POST['scope_value']) ?? '';
            $notes = trim($_POST['notes']);

            if ($kaizenUserId && !empty($selectedRoles)) {
                $assignedCount = 0;
                $skippedRoles = [];

                foreach ($selectedRoles as $roleId) {
                    // Role IDs are always numeric since dms_roles has ID column
                    $roleId = intval($roleId);
                    if (!$roleId) continue;

                    // Verify role exists and is active, get role name for better messaging
                    $stmt = $db->prepare("SELECT id, display_name, name FROM dms_roles WHERE id = ? AND status = 'active'");
                    $stmt->execute([$roleId]);
                    $roleInfo = $stmt->fetch();
                    if (!$roleInfo) {
                        continue; // Role doesn't exist
                    }

                    // Check if user already has this role (no ID column in user_roles)
                    $stmt = $db->prepare("
                        SELECT COUNT(*) FROM dms_user_roles
                        WHERE user_id = ? AND role_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$kaizenUserId, $roleId]);
                    $roleExists = $stmt->fetchColumn() > 0;

                    if (!$roleExists) {
                        // Insert using actual database schema columns
                        $stmt = $db->prepare("
                            INSERT INTO dms_user_roles
                            (user_id, role_id, status, granted_by, granted_at, notes, department)
                            VALUES (?, ?, 'active', ?, NOW(), ?, ?)
                        ");
                        $stmt->execute([
                            $kaizenUserId,
                            $roleId,
                            $user['id'],
                            $notes,
                            $scopeValue // Use scopeValue as department
                        ]);
                        $assignedCount++;

                        // Log the action
                        $db->prepare("
                            INSERT INTO dms_activity_log
                            (entity_type, entity_id, action, user_id, new_values, created_at)
                            VALUES ('rbac_role_assignment', ?, 'role_assigned', ?, ?, NOW())
                        ")->execute([
                            $kaizenUserId,
                            $user['id'],
                            json_encode([
                                'username' => $kaizenUsername,
                                'name' => $kaizenName,
                                'email' => $kaizenEmail,
                                'role_id' => $roleId,
                                'scope' => $scope,
                                'scope_value' => $scopeValue,
                                'notes' => $notes
                            ])
                        ]);
                    } else {
                        // Track roles that were skipped because user already has them
                        $skippedRoles[] = $roleInfo['display_name'] ?? $roleInfo['name'];
                    }
                }

                if ($assignedCount > 0) {
                    $success = "Assigned $assignedCount role(s) to user: $kaizenUsername";
                    if (!empty($skippedRoles)) {
                        $success .= " (Skipped existing roles: " . implode(', ', $skippedRoles) . ")";
                    }

                    // Clear permission cache if RBAC is available
                    if ($permissionManager && method_exists($permissionManager, 'clearUserCache')) {
                        $permissionManager->clearUserCache($kaizenUserId);
                    }
                } else {
                    if (!empty($skippedRoles)) {
                        $error = "User already has all selected roles: " . implode(', ', $skippedRoles);
                    } else {
                        $error = "No valid roles selected or roles don't exist";
                    }
                }
            } else {
                $error = "User ID and at least one role are required";
            }

        } elseif (isset($_POST['revoke_role'])) {
            // Revoke specific role assignment - use composite key since no ID column
            $userId = intval($_POST['user_id']);
            $roleId = intval($_POST['role_id']);

            if ($userId && $roleId) {
                // Protect Kaizen Admin's admin role from being revoked
                if ($userId == 1 && $roleId == 1) {
                    $error = "Cannot revoke admin access for Kaizen Admin - this is a protected system record";
                } else {
                    // This schema has audit columns but no revoked_by/revoked_at
                    $stmt = $db->prepare("
                        UPDATE dms_user_roles
                        SET status = 'inactive'
                        WHERE user_id = ? AND role_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$userId, $roleId]);

                    if ($stmt->rowCount() > 0) {
                        $success = "Role assignment revoked successfully";

                        // Clear permission cache if RBAC is available
                        if ($permissionManager && method_exists($permissionManager, 'clearUserCache')) {
                            $permissionManager->clearUserCache($userId);
                        }
                    } else {
                        $error = "No active role assignment found to revoke";
                    }
                }
            }

        } elseif (isset($_POST['restore_role'])) {
            // Restore specific role assignment - use composite key since no ID column
            $userId = intval($_POST['user_id']);
            $roleId = intval($_POST['role_id']);

            if ($userId && $roleId) {
                $stmt = $db->prepare("
                    UPDATE dms_user_roles
                    SET status = 'active'
                    WHERE user_id = ? AND role_id = ? AND status = 'inactive'
                ");
                $stmt->execute([$userId, $roleId]);

                if ($stmt->rowCount() > 0) {
                    $success = "Role assignment restored successfully";

                    // Clear permission cache if RBAC is available
                    if ($permissionManager && method_exists($permissionManager, 'clearUserCache')) {
                        $permissionManager->clearUserCache($userId);
                    }
                }
            }

        } elseif (isset($_POST['update_scope'])) {
            // Update department (scope) for specific role assignment
            $userId = intval($_POST['user_id']);
            $roleId = intval($_POST['role_id']);
            $newDepartment = trim($_POST['new_scope']);

            if ($userId && $roleId && $newDepartment) {
                $stmt = $db->prepare("
                    UPDATE dms_user_roles
                    SET department = ?
                    WHERE user_id = ? AND role_id = ? AND status = 'active'
                ");
                $stmt->execute([$newDepartment, $userId, $roleId]);

                if ($stmt->rowCount() > 0) {
                    $success = "Role department updated successfully";

                    // Clear permission cache if RBAC is available
                    if ($permissionManager && method_exists($permissionManager, 'clearUserCache')) {
                        $permissionManager->clearUserCache($userId);
                    }
                }
            }
        }

    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Module Users Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        error_log("SQL Context: Processing role assignment for user " . ($kaizenUserId ?? 'unknown'));
    }
}

// Include header after all form processing
require_once 'includes/header.php';

// Check if RBAC columns exist in the database
$scopeColumns = '';
$auditColumns = '';
$hasAuditColumns = false;
$hasIdColumn = false;
$rolesHasIdColumn = false;
$userRolesColumns = [];
$rolesColumns = [];

try {
    // Get all columns from dms_user_roles
    $stmt = $db->query("DESCRIBE dms_user_roles");
    $userRolesCols = $stmt->fetchAll();
    foreach ($userRolesCols as $col) {
        $userRolesColumns[] = $col['Field'];
    }
    $hasIdColumn = in_array('id', $userRolesColumns);
    if (in_array('scope', $userRolesColumns)) {
        $scopeColumns = ', ur.scope, ur.scope_value';
    }
    if (in_array('revoked_by', $userRolesColumns)) {
        $hasAuditColumns = true;
    }

    // Get all columns from dms_roles
    $stmt = $db->query("DESCRIBE dms_roles");
    $rolesCols = $stmt->fetchAll();
    foreach ($rolesCols as $col) {
        $rolesColumns[] = $col['Field'];
    }
    $rolesHasIdColumn = in_array('id', $rolesColumns);

    error_log("DB Schema Detection - User Roles Columns: " . implode(', ', $userRolesColumns));
    error_log("DB Schema Detection - Roles Columns: " . implode(', ', $rolesColumns));
    error_log("DB Schema Detection - hasIdColumn: " . ($hasIdColumn ? 'true' : 'false'));
    error_log("DB Schema Detection - rolesHasIdColumn: " . ($rolesHasIdColumn ? 'true' : 'false'));

} catch (Exception $e) {
    // Fallback to minimal assumptions
    error_log("Database compatibility check failed: " . $e->getMessage());
    $userRolesColumns = ['user_id', 'role_id', 'status'];
    $rolesColumns = ['role_name', 'display_name'];
    $hasIdColumn = false;
    $rolesHasIdColumn = false;
}

// Build query based on available columns - user_roles has NO id column
$selectColumns = "ur.user_id, ur.role_id, ur.status";
// Always include audit columns since they exist in this schema
$selectColumns .= ", ur.granted_by, ur.granted_at, ur.notes";
// Add other available columns
$selectColumns .= ", ur.last_access, ur.role_name, ur.department";

// Get all user role assignments with role and user details
// dms_roles HAS id column, dms_user_roles does NOT
$query = "
    SELECT " . $selectColumns . ", r.name as role_table_name, r.display_name, r.hierarchy_level
    FROM dms_user_roles ur
    LEFT JOIN dms_roles r ON ur.role_id = r.id
    ORDER BY ur.user_id, ur.status DESC, ur.granted_at DESC
";

$stmt = $db->query($query);
$userRoleAssignments = $stmt->fetchAll();

// Add default values and create composite ID for forms
foreach ($userRoleAssignments as &$assignment) {
    // Create composite ID for form operations: user_id-role_id
    $assignment['id'] = $assignment['user_id'] . '-' . $assignment['role_id'];

    // Set scope defaults (this schema uses department instead)
    $assignment['scope'] = $assignment['department'] ?? 'all';
    $assignment['scope_value'] = $assignment['department'] ?? '';

    // Use role_name from user_roles table or fall back to role table data
    if (empty($assignment['role_name']) && !empty($assignment['role_table_name'])) {
        $assignment['role_name'] = $assignment['role_table_name'];
    }
    if (empty($assignment['display_name'])) {
        $assignment['display_name'] = $assignment['role_name'] ?? ('Role #' . $assignment['role_id']);
    }
    if (empty($assignment['hierarchy_level'])) {
        $assignment['hierarchy_level'] = 'operator'; // default from enum
    }
}
unset($assignment); // Important: Break the reference to avoid corruption in next loop

// Group assignments by user - add duplicate detection
$userGroups = [];
foreach ($userRoleAssignments as $assignment) {
    $userId = $assignment['user_id'];
    if (!isset($userGroups[$userId])) {
        $userGroups[$userId] = [];
    }

    // Check for duplicates before adding
    $isDuplicate = false;
    foreach ($userGroups[$userId] as $existingAssignment) {
        if ($existingAssignment['user_id'] == $assignment['user_id'] &&
            $existingAssignment['role_id'] == $assignment['role_id'] &&
            $existingAssignment['status'] == $assignment['status'] &&
            $existingAssignment['granted_at'] == $assignment['granted_at']) {
            $isDuplicate = true;
            break;
        }
    }

    if (!$isDuplicate) {
        $userGroups[$userId][] = $assignment;
    }
}

// Pre-populate UserDisplayHelper cache with current user info
$userDisplayHelper = UserDisplayHelper::getInstance();
$userDisplayHelper->cacheUserInfo($user['id'], [
    'name' => $user['name'] ?? $user['username'] ?? 'Unknown User',
    'email' => $user['email'] ?? '',
    'mobile' => $user['mobile'] ?? '',
    'username' => $user['username'] ?? ''
]);

// Get available roles for the form - temporarily show all active roles
$stmt = $db->query("
    SELECT * FROM dms_roles
    WHERE status = 'active'
    ORDER BY
        CASE
            WHEN hierarchy_level = 'director' THEN 1
            WHEN hierarchy_level = 'manager' THEN 2
            WHEN hierarchy_level = 'supervisor' THEN 3
            WHEN hierarchy_level = 'lead' THEN 4
            WHEN hierarchy_level = 'operator' THEN 5
            ELSE 6
        END,
        name
");
$availableRoles = $stmt->fetchAll();

// Get available departments for scope dropdown
$stmt = $db->query("
    SELECT DISTINCT dept_code, dept_name
    FROM dms_departments
    WHERE is_active = 1
    ORDER BY dept_name
");
$availableDepartments = $stmt->fetchAll();

// Get statistics - calculate from processed data to avoid duplicates
$stats = [];

// Count from processed userGroups to match display logic
$totalActiveAssignments = 0;
$totalInactiveAssignments = 0;
$totalUsers = count($userGroups);

foreach ($userGroups as $userId => $assignments) {
    foreach ($assignments as $assignment) {
        if ($assignment['status'] === 'active') {
            $totalActiveAssignments++;
        } else {
            $totalInactiveAssignments++;
        }
    }
}

$stats['total_users'] = $totalUsers;
$stats['total_assignments'] = $totalActiveAssignments;
$stats['inactive_assignments'] = $totalInactiveAssignments;

// Keep role count from database since it's not affected by duplicates
$stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE is_system_role = 1");
$stats['total_roles'] = $stmt->fetchColumn();
?>

<!-- Kaizen UI Design System Styles -->
<style>
:root {
    --brand-primary: #C53A3A;
    --brand-primary-dark: #A72E2E;
    --neutral-100: #F6F7F8;
    --neutral-300: #E6E9EC;
    --neutral-600: #6B7280;
    --text-default: #111827;
    --white: #FFFFFF;
    --success: #16A34A;
    --warning: #F59E0B;
    --error: #DC2626;
    --info: #2563EB;
    --pending: #9CA3AF;
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --shadow-soft: 0 6px 18px rgba(16, 24, 40, 0.06);
}

* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
    color: var(--text-default);
    background: var(--neutral-100);
    line-height: 1.5;
}

.wrap {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 20px;
    display: grid;
    gap: 20px;
}

section {
    background: #fff;
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
    padding: 20px;
}

h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: bold;
}

h2 {
    margin: 0 0 10px 0;
    font-size: 22px;
}

h3 {
    margin: 16px 0 8px;
    font-size: 18px;
}

.grid {
    display: grid;
    gap: 14px;
}

.grid.cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 900px) {
    .grid.cols-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .grid.cols-3 {
        grid-template-columns: 1fr;
    }
}

.card {
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
    padding: 16px;
    background: #fff;
}

.muted {
    color: var(--neutral-600);
    font-size: 13px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

th {
    background: var(--neutral-100);
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid var(--neutral-300);
    font-size: 14px;
}

td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--neutral-300);
    vertical-align: top;
}

tbody tr:hover {
    background: var(--neutral-100);
}

.btn {
    border: 0;
    cursor: pointer;
    border-radius: 12px;
    padding: 10px 16px;
    font-weight: 600;
    box-shadow: var(--shadow-soft);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn:focus {
    outline: 3px solid rgba(197, 58, 58, .25);
}

.btn-primary {
    background: var(--brand-primary);
    color: #fff;
}

.btn-primary:hover {
    background: var(--brand-primary-dark);
}

.btn-secondary {
    background: #fff;
    color: var(--text-default);
    border: 1px solid var(--neutral-300);
}

.btn-tertiary {
    background: transparent;
    color: var(--brand-primary);
    box-shadow: none;
    padding: 8px 10px;
}

.btn-danger {
    background: var(--error);
    color: #fff;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
}

.bg-success { background: var(--success); }
.bg-warning { background: var(--warning); color: #1f2937; }
.bg-error { background: var(--error); }
.bg-pending { background: var(--pending); }

.badge.primary { background: var(--brand-primary); color: #fff; }
.badge.secondary { background: var(--neutral-300); color: var(--text-default); }
.badge.success { background: var(--success); color: #fff; }
.badge.warning { background: var(--warning); color: #1f2937; }
.badge.error { background: var(--error); color: #fff; }
.badge.info { background: var(--info); color: #fff; }

.field {
    margin-bottom: 14px;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
}

.input, .select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid var(--neutral-300);
    background: #fff;
}

.input:focus, .select:focus {
    outline: 3px solid rgba(37, 99, 235, .25);
}

.alert {
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert.success {
    background: #F0FDF4;
    color: #15803D;
    border: 1px solid #BBF7D0;
}

.alert.error {
    background: #FEF2F2;
    color: #DC2626;
    border: 1px solid #FECACA;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

/* Modal styling */
.modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
    overflow: hidden;
}

.modal-header {
    background: var(--brand-primary);
    color: #fff;
    padding: 20px;
    border-bottom: none;
}

.modal-title {
    color: #fff;
    font-weight: 600;
    font-size: 18px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-body {
    padding: 20px;
    background: #fff;
}

.modal-footer {
    background: var(--neutral-100);
    padding: 16px 20px;
    border-top: 1px solid var(--neutral-300);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.close {
    color: #fff;
    opacity: 0.8;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close:hover {
    opacity: 1;
}

@media (max-width: 768px) {
    .wrap {
        padding: 0 16px;
        margin: 16px auto;
    }

    h1 {
        font-size: 24px;
    }
}
</style>

<div class="wrap">
    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1>RBAC User Management</h1>
                <p class="muted">Manage multi-role assignments and scope-based permissions</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#assignRolesModal">
                    <i class="fas fa-user-plus"></i> Assign User Roles
                </button>
            </div>
        </div>
    </section>

    <?php if ($success): ?>
    <div class="alert success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert error">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="grid cols-3">
        <div class="card">
            <div class="muted">Active Users</div>
            <div style="font-size: 28px; font-weight: 700;"><?= $stats['total_users'] ?></div>
        </div>
        <div class="card">
            <div class="muted">Role Assignments</div>
            <div style="font-size: 28px; font-weight: 700;"><?= $stats['total_assignments'] ?></div>
        </div>
        <div class="card">
            <div class="muted">System Roles</div>
            <div style="font-size: 28px; font-weight: 700;"><?= $stats['total_roles'] ?></div>
        </div>
    </div>

    <section>
        <h2><i class="fas fa-users-cog"></i> User Role Assignments</h2>
        <?php if (empty($userGroups)): ?>
        <div style="text-align: center; padding: 40px; color: var(--neutral-600);">
            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
            <p>No user role assignments found. Use the "Assign User Roles" button to get started.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Roles & Scope</th>
                        <th>Status</th>
                        <th>Assigned By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                                <?php
                                echo "<!-- DEBUG: userGroups has " . count($userGroups) . " users: " . implode(', ', array_keys($userGroups)) . " -->";
                                foreach ($userGroups as $userId => $assignments):
                                    echo "<!-- DEBUG: Processing User {$userId} with " . count($assignments) . " assignments -->";

                                    $activeAssignments = array_filter($assignments, function($a) { return $a['status'] === 'active'; });
                                    $inactiveAssignments = array_filter($assignments, function($a) { return $a['status'] === 'inactive'; });
                                    $allAssignments = array_merge($activeAssignments, $inactiveAssignments);

                                    echo "<!-- DEBUG: User {$userId} - Active: " . count($activeAssignments) . ", Inactive: " . count($inactiveAssignments) . ", Total: " . count($allAssignments) . " -->";
                                ?>

                    <tr style="background: var(--neutral-100); border-top: 2px solid var(--brand-primary);">
                        <td colspan="6">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-md);">
                                <div style="display: flex; align-items: center; gap: var(--space-md);">
                                    <div style="font-size: 24px; color: var(--brand-primary);">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div>
                                        <h6 style="margin: 0; font-weight: 600; color: var(--text-default);">
                                            <?php if ($userId == $user['id']): ?>
                                                <?= getUserDisplayName($userId, $user['name'] ?? $user['username'] ?? 'Current User') ?>
                                                <span class="badge info" style="margin-left: var(--space-sm);">You</span>
                                            <?php else: ?>
                                                <?= getUserDisplayName($userId, "User #{$userId}") ?>
                                            <?php endif; ?>
                                        </h6>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                            User ID: <?= $userId ?> |
                                            Active Roles: <?= count($activeAssignments) ?> |
                                            Total Assignments: <?= count($assignments) ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn secondary sm" onclick="toggleUserAssignments(<?= $userId ?>)">
                                        <i class="fas fa-chevron-down" id="toggle-<?= $userId ?>"></i>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Assignment rows for user <?= $userId ?> -->
                    <?php foreach ($allAssignments as $assignment): ?>
                        <tr style="<?= $assignment['status'] === 'inactive' ? 'background: rgba(245, 158, 11, 0.05); border-left: 3px solid var(--warning);' : 'border-left: 3px solid transparent;' ?> display: none;" class="assignment-row user-<?= $userId ?>-assignments">
                            <td style="padding-left: calc(var(--space-lg) + var(--space-md));">
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?php if ($assignment['id']): ?>
                                        Assignment #<?= $assignment['id'] ?>
                                    <?php else: ?>
                                        Role Assignment
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge <?= $assignment['status'] === 'active' ? 'primary' : 'secondary' ?>" style="margin-bottom: var(--space-sm);">
                                        <?= htmlspecialchars($assignment['display_name'] ?? $assignment['role_name']) ?>
                                    </span>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        Scope: <strong><?= htmlspecialchars($assignment['scope'] ?? 'all') ?></strong>
                                        <?php if ($assignment['scope_value']): ?>
                                            | Value: <strong><?= htmlspecialchars($assignment['scope_value']) ?></strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $assignment['status'] === 'active' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($assignment['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?php if ($assignment['granted_by']): ?>
                                        <?php if ($assignment['granted_by'] == $user['id']): ?>
                                            You
                                        <?php else: ?>
                                            <?= getUserDisplayName($assignment['granted_by'], "User #{$assignment['granted_by']}") ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        System
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?= $assignment['granted_at'] ? date('M j, Y', strtotime($assignment['granted_at'])) : 'N/A' ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: var(--space-xs);">
                                    <?php
                                    $isKaizenAdmin = $assignment['user_id'] == 1;
                                    $isProtectedRole = $assignment['role_id'] == 1; // Admin role
                                    $isProtected = $isKaizenAdmin && $isProtectedRole;
                                    ?>

                                    <?php if ($assignment['status'] === 'active'): ?>
                                        <?php if ($isProtected): ?>
                                            <span class="btn info sm" style="background: #ccc; cursor: not-allowed; opacity: 0.6;" title="Kaizen Admin access is protected">
                                                <i class="fas fa-lock"></i> Protected
                                            </span>
                                        <?php else: ?>
                                            <?php if ($hasIdColumn): ?>
                                                <button class="btn info sm" onclick="updateScope(<?= $assignment['id'] ?>, '<?= $assignment['scope'] ?>', '<?= $assignment['scope_value'] ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn info sm" onclick="updateScopeComposite(<?= $assignment['user_id'] ?>, <?= $assignment['role_id'] ?>, '<?= $assignment['scope'] ?>', '<?= $assignment['scope_value'] ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                                                <?php if ($hasIdColumn): ?>
                                                    <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                                <?php else: ?>
                                                    <input type="hidden" name="user_id" value="<?= $assignment['user_id'] ?>">
                                                    <input type="hidden" name="role_id" value="<?= $assignment['role_id'] ?>">
                                                <?php endif; ?>
                                                <button type="submit" name="revoke_role" class="btn danger sm" onclick="return confirm('Revoke this role assignment?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($isProtected): ?>
                                            <span class="btn success sm" style="background: #ccc; cursor: not-allowed; opacity: 0.6;" title="Kaizen Admin access is protected">
                                                <i class="fas fa-lock"></i> Protected
                                            </span>
                                        <?php else: ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                                                <?php if ($hasIdColumn): ?>
                                                    <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                                <?php else: ?>
                                                    <input type="hidden" name="user_id" value="<?= $assignment['user_id'] ?>">
                                                    <input type="hidden" name="role_id" value="<?= $assignment['role_id'] ?>">
                                                <?php endif; ?>
                                                <button type="submit" name="restore_role" class="btn success sm" onclick="return confirm('Restore this role assignment?')">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- End assignment rows for user <?= $userId ?> -->

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>

<!-- Assign Roles Modal -->
<div class="modal fade " id="assignRolesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i>Assign User Roles
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                <div class="modal-body">
                    <div class="field">
                        <label for="userSearch">Search Users</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" class="input" id="userSearch"
                                   placeholder="Type name, email, or username to search..."
                                   onkeyup="searchUsers(this.value)" style="flex: 1;">
                            <button class="btn btn-secondary" type="button" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="muted" style="margin-top: 6px;">Start typing to search KaizenAuth users...</div>
                    </div>

                    <div id="userSelectionStatus" class="field" style="display: none;">
                        <div class="alert success">
                            <i class="fas fa-check"></i>
                            <strong>User Selected:</strong>
                            <span id="selectedUserDisplay"></span>
                            <button type="button" class="btn btn-secondary" onclick="clearSearch()" style="margin-left: 10px;">
                                Change User
                            </button>
                        </div>
                    </div>

                    <div id="searchResults" class="field" style="display: none;">
                        <label>Search Results:</label>
                        <div id="searchResultsList" style="max-height: 200px; overflow-y: auto; border: 1px solid var(--neutral-300); border-radius: 10px; padding: 10px;">
                            <!-- Results will be populated here -->
                        </div>
                    </div>

                    <!-- Hidden Form Fields -->
                    <input type="hidden" id="kaizen_user_id" name="kaizen_user_id" required>
                    <input type="hidden" id="kaizen_username" name="kaizen_username" required>
                    <input type="hidden" id="kaizen_email" name="kaizen_email">
                    <input type="hidden" id="kaizen_name" name="kaizen_name">

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                        <div>
                            <div class="field">
                                <label>Select Roles *</label>
                                <div style="display: grid; gap: 8px; max-height: 300px; overflow-y: auto; border: 1px solid var(--neutral-300); padding: 16px; border-radius: 10px;">
                                    <?php foreach ($availableRoles as $role): ?>
                                    <div style="padding: 12px; border: 1px solid var(--neutral-300); border-radius: 6px;">
                                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; margin: 0;">
                                            <input type="checkbox" name="selected_roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>" style="margin-top: 2px;">
                                            <div>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($role['display_name'] ?? $role['name']) ?></div>
                                                <div class="muted" style="margin: 2px 0;"><?= htmlspecialchars($role['description']) ?></div>
                                                <span class="badge info">Level <?= $role['hierarchy_level'] ?></span>
                                            </div>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="field">
                                <label for="scope">Permission Scope</label>
                                <select class="select" id="scope" name="scope" onchange="toggleScopeValue(this.value)">
                                    <option value="all">All (Global)</option>
                                    <option value="cross_department">Cross Department</option>
                                    <option value="department">Department Specific</option>
                                    <option value="process_area">Process Area</option>
                                    <option value="station">Station Specific</option>
                                    <option value="assigned_only">Assigned Only</option>
                                </select>
                            </div>
                            <div class="field" id="scope_value_group" style="display: none;">
                                <label class="" for="scope_value">Department</label>
                                <select class="select" id="scope_value" name="scope_value">
                                    <option value="">Select Department</option>
                                    <?php foreach ($availableDepartments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['dept_code']) ?>">
                                        <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: var(--space-sm);">Select the department for this role</div>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="" for="notes">Admin Notes</label>
                        <textarea class="input" id="notes" name="notes" rows="3"
                                  placeholder="Optional notes about this role assignment..." style="resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_roles" class="btn btn-primary">
                        <i class="fas fa-check"></i>Assign Roles
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Scope Modal -->
<div class="modal fade" id="updateScopeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i>Update Permission Scope
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post" id="updateScopeForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                <input type="hidden" id="update_assignment_id" name="assignment_id">
                <input type="hidden" id="update_user_id" name="user_id">
                <input type="hidden" id="update_role_id" name="role_id">
                <div class="modal-body">
                    <div class="field">
                        <label for="new_scope">Permission Scope</label>
                        <select class="select" id="new_scope" name="new_scope" onchange="toggleUpdateScopeValue(this.value)">
                            <option value="all">All (Global)</option>
                            <option value="cross_department">Cross Department</option>
                            <option value="department">Department Specific</option>
                            <option value="process_area">Process Area</option>
                            <option value="station">Station Specific</option>
                            <option value="assigned_only">Assigned Only</option>
                        </select>
                    </div>
                    <div class="field" id="update_scope_value_group">
                        <label for="new_scope_value">Department</label>
                        <select class="select" id="new_scope_value" name="new_scope_value">
                            <option value="">Select Department</option>
                            <?php foreach ($availableDepartments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['dept_code']) ?>">
                                <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="muted" style="margin-top: 6px;">Select the department for this role</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_scope" class="btn btn-primary">
                        <i class="fas fa-save"></i>Update Scope
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// User Search Functionality
let searchTimeout;

function searchUsers(query) {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (query.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        performUserSearch(query);
    }, 500);
}

function performUserSearch(query) {
    const resultsDiv = document.getElementById('searchResults');
    const resultsList = document.getElementById('searchResultsList');

    resultsDiv.style.display = 'block';
    resultsList.innerHTML = '<div style="padding: var(--space-md); text-align: center; color: var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Searching users...</div>';

    fetch('api/user_search.php?query=' + encodeURIComponent(query) + '&limit=10', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error('Authentication required - please refresh and login');
            } else if (response.status === 403) {
                throw new Error('Admin access required');
            } else {
                throw new Error('HTTP ' + response.status);
            }
        }
        return response.json();
    })
    .then(data => {
        displaySearchResults(data);
    })
    .catch(error => {
        console.error('Search error:', error);
        resultsList.innerHTML = '<div style="padding: var(--space-md); text-align: center; color: var(--error);"><i class="fas fa-exclamation-triangle"></i> Search failed: ' + error.message + '</div>';
    });
}

function displaySearchResults(data) {
    const resultsList = document.getElementById('searchResultsList');

    if (!data.success || !data.data || !data.data.users || data.data.users.length === 0) {
        resultsList.innerHTML = '<div style="padding: var(--space-md); text-align: center; color: var(--text-muted);"><i class="fas fa-search"></i> No users found</div>';
        return;
    }

    let html = '';
    data.data.users.forEach(user => {
        html += `
            <div onclick="selectUser('${user.id}', '${escapeHtml(user.username || '')}', '${escapeHtml(user.email || '')}', '${escapeHtml(user.name || '')}')" style="cursor: pointer; padding: var(--space-md); border-bottom: 1px solid var(--neutral-200); transition: all 0.2s ease;" onmouseover="this.style.background='var(--neutral-100)'" onmouseout="this.style.background='transparent'">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px; color: var(--text-default);">${escapeHtml(user.name || user.username || 'Unknown')}</div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 2px;">${escapeHtml(user.email || 'No email')}</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Username: ${escapeHtml(user.username || 'N/A')} | ID: ${user.id}</div>
                    </div>
                    <div>
                        <i class="fas fa-user-plus" style="color: var(--brand-primary);"></i>
                    </div>
                </div>
            </div>
        `;
    });

    resultsList.innerHTML = html;
}

function selectUser(id, username, email, name) {
    document.getElementById('kaizen_user_id').value = id;
    document.getElementById('kaizen_username').value = username;
    document.getElementById('kaizen_email').value = email;
    document.getElementById('kaizen_name').value = name;

    const displayName = name || username || ('User #' + id);
    document.getElementById('userSearch').value = displayName;

    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('userSelectionStatus').style.display = 'block';
    document.getElementById('selectedUserDisplay').textContent = displayName;

    const searchInput = document.getElementById('userSearch');
    searchInput.style.backgroundColor = '#d4edda';
    searchInput.style.borderColor = '#c3e6cb';
    searchInput.readOnly = true;
}

function clearSearch() {
    document.getElementById('userSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('userSelectionStatus').style.display = 'none';

    // Clear hidden form fields
    document.getElementById('kaizen_user_id').value = '';
    document.getElementById('kaizen_username').value = '';
    document.getElementById('kaizen_email').value = '';
    document.getElementById('kaizen_name').value = '';

    // Reset search input
    const searchInput = document.getElementById('userSearch');
    searchInput.style.backgroundColor = '';
    searchInput.style.borderColor = '';
    searchInput.readOnly = false;
    searchInput.placeholder = 'Type name, email, or username to search...';
}

function toggleScopeValue(scope) {
    const scopeValueGroup = document.getElementById('scope_value_group');
    if (scope === 'all' || scope === 'cross_department') {
        scopeValueGroup.style.display = 'none';
        document.getElementById('scope_value').required = false;
    } else if (scope === 'department') {
        scopeValueGroup.style.display = 'block';
        document.getElementById('scope_value').required = true;
        // Change label for department selection
        const label = scopeValueGroup.querySelector('label');
        if (label) label.textContent = 'Department';
    } else {
        scopeValueGroup.style.display = 'block';
        document.getElementById('scope_value').required = true;
        // Change label for other scopes
        const label = scopeValueGroup.querySelector('label');
        if (label) label.textContent = 'Scope Value';
    }
}

function toggleUpdateScopeValue(scope) {
    const scopeValueGroup = document.getElementById('update_scope_value_group');
    if (scope === 'all' || scope === 'cross_department') {
        scopeValueGroup.style.display = 'none';
        document.getElementById('new_scope_value').required = false;
    } else if (scope === 'department') {
        scopeValueGroup.style.display = 'block';
        document.getElementById('new_scope_value').required = true;
        // Change label for department selection
        const label = scopeValueGroup.querySelector('label');
        if (label) label.textContent = 'Department';
    } else {
        scopeValueGroup.style.display = 'block';
        document.getElementById('new_scope_value').required = true;
        // Change label for other scopes
        const label = scopeValueGroup.querySelector('label');
        if (label) label.textContent = 'Scope Value';
    }
}

function toggleUserAssignments(userId) {
    const assignmentRows = document.querySelectorAll('.user-' + userId + '-assignments');
    const toggleIcon = document.getElementById('toggle-' + userId);

    // Check if any rows are visible
    const anyVisible = Array.from(assignmentRows).some(row => row.style.display !== 'none');

    if (anyVisible) {
        // Hide all assignment rows for this user
        assignmentRows.forEach(row => row.style.display = 'none');
        toggleIcon.className = 'fas fa-chevron-down';
    } else {
        // Show all assignment rows for this user
        assignmentRows.forEach(row => row.style.display = 'table-row');
        toggleIcon.className = 'fas fa-chevron-up';
    }
}

function updateScope(assignmentId, currentScope, currentScopeValue) {
    document.getElementById('update_assignment_id').value = assignmentId;
    document.getElementById('new_scope').value = currentScope;
    document.getElementById('new_scope_value').value = currentScopeValue;

    // Clear composite key fields
    document.getElementById('update_user_id').value = '';
    document.getElementById('update_role_id').value = '';

    toggleUpdateScopeValue(currentScope);

    $('#updateScopeModal').modal('show');
}

function updateScopeComposite(userId, roleId, currentScope, currentScopeValue) {
    document.getElementById('update_user_id').value = userId;
    document.getElementById('update_role_id').value = roleId;
    document.getElementById('new_scope').value = currentScope || 'department';
    document.getElementById('new_scope_value').value = currentScopeValue || '';

    // Clear assignment_id field
    document.getElementById('update_assignment_id').value = '';

    toggleUpdateScopeValue(currentScope || 'department');

    $('#updateScopeModal').modal('show');
}

function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

// Initialize modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Reset form when assign roles modal opens
    $('#assignRolesModal').on('show.bs.modal', function() {
        clearSearch();

        // Uncheck all role checkboxes
        document.querySelectorAll('input[name="selected_roles[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Reset scope selection
        document.getElementById('scope').value = 'all';
        toggleScopeValue('all');
        document.getElementById('notes').value = '';
    });

    // Form validation for assign roles
    const assignForm = document.querySelector('#assignRolesModal form');
    if (assignForm) {
        assignForm.addEventListener('submit', function(e) {
            const userId = document.getElementById('kaizen_user_id').value;
            const selectedRoles = document.querySelectorAll('input[name="selected_roles[]"]:checked');

            if (!userId) {
                e.preventDefault();
                alert('Please search and select a user first.');
                return false;
            }

            if (selectedRoles.length === 0) {
                e.preventDefault();
                alert('Please select at least one role.');
                return false;
            }

            return true;
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>