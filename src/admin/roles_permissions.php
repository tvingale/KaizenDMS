<?php
/**
 * RBAC Management - Kaizen UI Compliant
 * Role and permission management interface following Kaizen design system
 */

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/kaizen_sso.php';
require_once '../includes/AccessControl.php';

// Check authentication and admin access
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);

if (!$sso->isAuthenticated()) {
    header('Location: ../sso.php');
    exit;
}

$user = $sso->getUserInfo();
$db = getDB();
$accessControl = AccessControl::requireAccess('admin');

// Initialize permission manager if available
$permissionManager = null;
if (file_exists('../includes/AdditivePermissionManager.php')) {
    require_once '../includes/AdditivePermissionManager.php';
    try {
        $permissionManager = new AdditivePermissionManager($db);
    } catch (Exception $e) {
        error_log("Could not initialize AdditivePermissionManager: " . $e->getMessage());
    }
}

$pageTitle = 'RBAC Management';

// Check if enhanced RBAC tables exist
$rbacTablesExist = false;
try {
    $stmt = $db->query("SHOW TABLES LIKE 'dms_permissions'");
    if ($stmt->fetchColumn()) {
        $rbacTablesExist = true;
    }
} catch (Exception $e) {
    $rbacTablesExist = false;
}

// Handle AJAX requests for permission management
if (isset($_GET['action']) && $_GET['action'] === 'get_role_permissions') {
    header('Content-Type: application/json');
    $role_id = intval($_GET['role_id'] ?? 0);

    if ($role_id > 0 && $rbacTablesExist) {
        try {
            $stmt = $db->prepare("
                SELECT p.id, p.name, p.display_name, p.category,
                       CASE WHEN rp.role_id IS NOT NULL THEN 1 ELSE 0 END as assigned
                FROM dms_permissions p
                LEFT JOIN dms_role_permissions rp ON p.id = rp.permission_id AND rp.role_id = ?
                WHERE p.is_system_permission = 1
                ORDER BY p.category, p.display_name
            ");
            $stmt->execute([$role_id]);
            $permissions = $stmt->fetchAll();

            echo json_encode(['success' => true, 'permissions' => $permissions]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid role or RBAC not available']);
    }
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['rbac_csrf_token'])) {
        $_SESSION['rbac_csrf_token'] = bin2hex(random_bytes(32));
    }

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['rbac_csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token invalid. Please refresh the page and try again.';
        $messageType = 'error';
    } else {
        try {
            if (isset($_POST['create_role'])) {
                $roleName = trim($_POST['role_name'] ?? '');
                $displayName = trim($_POST['display_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $hierarchyLevel = intval($_POST['hierarchy_level'] ?? 50);

                if (empty($roleName)) {
                    throw new Exception('Role name is required');
                }

                // Check if role already exists
                $stmt = $db->prepare("SELECT id FROM dms_roles WHERE name = ?");
                $stmt->execute([$roleName]);
                if ($stmt->fetchColumn()) {
                    throw new Exception('Role with this name already exists');
                }

                // Create the role
                $stmt = $db->prepare("
                    INSERT INTO dms_roles (name, display_name, description, hierarchy_level, is_system_role, created_at)
                    VALUES (?, ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([$roleName, $displayName, $description, $hierarchyLevel]);

                $message = 'Role "' . htmlspecialchars($roleName) . '" created successfully!';
                $messageType = 'success';

            } elseif (isset($_POST['assign_permissions']) && $rbacTablesExist) {
                $roleId = intval($_POST['role_id']);
                $permissions = $_POST['permissions'] ?? [];

                // Clear existing permissions
                $stmt = $db->prepare("DELETE FROM dms_role_permissions WHERE role_id = ?");
                $stmt->execute([$roleId]);

                // Assign new permissions
                if (!empty($permissions)) {
                    $stmt = $db->prepare("INSERT INTO dms_role_permissions (role_id, permission_id) VALUES (?, ?)");
                    foreach ($permissions as $permissionId) {
                        $stmt->execute([$roleId, intval($permissionId)]);
                    }
                }

                $message = 'Permissions updated successfully!';
                $messageType = 'success';
            }

        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['rbac_csrf_token'])) {
    $_SESSION['rbac_csrf_token'] = bin2hex(random_bytes(32));
}

// Get existing roles
$stmt = $db->query("SELECT * FROM dms_roles ORDER BY hierarchy_level ASC, name");
$roles = $stmt->fetchAll();

// Get statistics
$stats = [];
try {
    $stmt = $db->query("SELECT COUNT(*) FROM dms_roles");
    $stats['total_roles'] = $stmt->fetchColumn();

    if ($rbacTablesExist) {
        $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE is_system_permission = 1");
        $stats['total_permissions'] = $stmt->fetchColumn();

        $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
        $stats['total_mappings'] = $stmt->fetchColumn();
    } else {
        $stats['total_permissions'] = 'N/A';
        $stats['total_mappings'] = 'N/A';
    }

    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM dms_user_roles WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats = ['total_roles' => 0, 'total_permissions' => 0, 'total_mappings' => 0, 'active_users' => 0];
}

require_once '../includes/header.php';
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
    --shadow-soft: 0 6px 18px rgba(16,24,40,0.06);
}

/* Reset and base styles */
* { box-sizing: border-box; }

body {
    font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
    background: var(--neutral-100);
    color: var(--text-default);
    line-height: 1.5;
    margin: 0;
}

/* Kaizen page layout */
.kaizen-container {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 20px;
}

/* Kaizen page header */
.kaizen-page-header {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    padding: 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px;
    color: var(--text-default);
}

.page-subtitle {
    color: var(--neutral-600);
    font-size: 14px;
    margin: 0;
}

/* Grid system */
.kaizen-grid {
    display: grid;
    gap: 16px;
}

.kaizen-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.kaizen-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.kaizen-grid.cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

@media (max-width: 900px) {
    .kaizen-grid.cols-3,
    .kaizen-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
    .kaizen-grid.cols-2 { grid-template-columns: 1fr; }
}

/* Kaizen cards */
.kaizen-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    padding: 20px;
}

/* KPI cards */
.kpi-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.kpi-number {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-default);
}

.kpi-label {
    color: var(--neutral-600);
    font-size: 14px;
    margin-bottom: 4px;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--white);
}

/* Status badges */
.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: var(--white);
}

.badge.success { background: var(--success); }
.badge.warning { background: var(--warning); color: var(--text-default); }
.badge.error { background: var(--error); }
.badge.pending { background: var(--pending); }
.badge.info { background: var(--info); }

/* Alert banners */
.alert {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

.alert .close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: inherit;
}

/* Kaizen buttons */
.btn {
    border: 0;
    cursor: pointer;
    border-radius: var(--radius-lg);
    padding: 10px 16px;
    font-weight: 600;
    box-shadow: var(--shadow-soft);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn:focus {
    outline: 3px solid rgba(197, 58, 58, 0.25);
}

.btn-primary {
    background: var(--brand-primary);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--brand-primary-dark);
}

.btn-secondary {
    background: var(--white);
    color: var(--text-default);
    border: 1px solid var(--neutral-300);
}

.btn-secondary:hover {
    background: var(--neutral-100);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

/* Kaizen forms */
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 10px 12px;
    border-radius: var(--radius-md);
    border: 1px solid var(--neutral-300);
    background: var(--white);
    font-size: 14px;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: 3px solid rgba(37, 99, 235, 0.25);
    border-color: var(--info);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

/* Role hierarchy display */
.role-hierarchy {
    display: flex;
    align-items: center;
    gap: 8px;
}

.hierarchy-level {
    background: var(--neutral-300);
    color: var(--text-default);
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    font-size: 11px;
    font-weight: 600;
}

.role-system {
    background: var(--brand-primary);
    color: var(--white);
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    font-size: 11px;
    font-weight: 600;
}

/* Tables */
.kaizen-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--white);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-soft);
}

.kaizen-table th {
    background: var(--neutral-100);
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: var(--text-default);
    border-bottom: 1px solid var(--neutral-300);
}

.kaizen-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--neutral-300);
    font-size: 14px;
}

.kaizen-table tbody tr:hover {
    background: var(--neutral-100);
}

.kaizen-table tbody tr:last-child td {
    border-bottom: none;
}

/* Permission grid */
.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
    max-height: 400px;
    overflow-y: auto;
    padding: 16px;
    border: 1px solid var(--neutral-300);
    border-radius: var(--radius-md);
    background: var(--white);
}

.permission-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border-radius: var(--radius-sm);
    transition: background-color 0.2s;
}

.permission-item:hover {
    background: var(--neutral-100);
}

.permission-checkbox {
    margin: 0;
}

.permission-details {
    flex-grow: 1;
}

.permission-name {
    font-weight: 600;
    font-size: 13px;
    color: var(--text-default);
}

.permission-description {
    font-size: 12px;
    color: var(--neutral-600);
    margin-top: 2px;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--neutral-300);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--neutral-600);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--neutral-300);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .kaizen-container {
        padding: 0 16px;
        margin: 16px auto;
    }

    .kaizen-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .permission-grid {
        grid-template-columns: 1fr;
    }

    .kaizen-table {
        font-size: 12px;
    }

    .kaizen-table th,
    .kaizen-table td {
        padding: 8px 12px;
    }
}
</style>

<div class="kaizen-container">

    <!-- Kaizen Page Header -->
    <div class="kaizen-page-header">
        <div>
            <h1 class="page-title">RBAC Management</h1>
            <p class="page-subtitle">Role and permission management system</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="openCreateRoleModal()">
                <i class="fas fa-plus"></i> Create New Role
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
    <div class="alert <?= $messageType ?>">
        <span><?= htmlspecialchars($message) ?></span>
        <button class="close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
    <?php endif; ?>

    <!-- System Status Warning -->
    <?php if (!$rbacTablesExist): ?>
    <div class="alert info">
        <span><strong>Info:</strong> Advanced RBAC features require additional database tables. Running in legacy compatibility mode.</span>
    </div>
    <?php endif; ?>

    <!-- Statistics Overview -->
    <div class="kaizen-grid cols-4" style="margin-bottom: 24px;">
        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Total Roles</div>
                    <div class="kpi-number"><?= $stats['total_roles'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--brand-primary);">
                    <i class="fas fa-id-badge"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Permissions</div>
                    <div class="kpi-number"><?= $stats['total_permissions'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--info);">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Mappings</div>
                    <div class="kpi-number"><?= $stats['total_mappings'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--success);">
                    <i class="fas fa-link"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Active Users</div>
                    <div class="kpi-number"><?= $stats['active_users'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--warning);">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles Management -->
    <div class="kaizen-card">
        <h3 style="margin: 0 0 16px; font-size: 18px; color: var(--text-default);">
            <i class="fas fa-users-cog" style="color: var(--brand-primary);"></i> System Roles
        </h3>

        <?php if (empty($roles)): ?>
        <div style="text-align: center; color: var(--neutral-600); padding: 40px 20px;">
            <i class="fas fa-id-badge" style="font-size: 48px; margin-bottom: 12px; opacity: 0.3;"></i>
            <p>No roles found. Create your first role to get started.</p>
        </div>
        <?php else: ?>
        <table class="kaizen-table">
            <thead>
                <tr>
                    <th>Role Details</th>
                    <th>Hierarchy Level</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td>
                        <div>
                            <div style="font-weight: 600; color: var(--text-default);">
                                <?= htmlspecialchars($role['display_name'] ?: $role['name']) ?>
                            </div>
                            <div style="font-size: 12px; color: var(--neutral-600); margin-top: 2px;">
                                <?= htmlspecialchars($role['description'] ?: 'No description') ?>
                            </div>
                            <div style="font-size: 11px; color: var(--neutral-600); margin-top: 2px;">
                                ID: <?= $role['id'] ?> | Name: <?= htmlspecialchars($role['name']) ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="hierarchy-level"><?= $role['hierarchy_level'] ?: 'N/A' ?></span>
                    </td>
                    <td>
                        <?php if ($role['is_system_role'] ?? false): ?>
                        <span class="role-system">System</span>
                        <?php else: ?>
                        <span class="badge info">Custom</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge success">Active</span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <?php if ($rbacTablesExist): ?>
                            <button class="btn btn-sm btn-secondary" onclick="managePermissions(<?= $role['id'] ?>, '<?= htmlspecialchars($role['display_name'] ?: $role['name']) ?>')">
                                <i class="fas fa-shield-alt"></i> Permissions
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-secondary" onclick="editRole(<?= $role['id'] ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- Create Role Modal -->
<div id="createRoleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Create New Role</h2>
            <button class="modal-close" onclick="closeCreateRoleModal()">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['rbac_csrf_token'] ?>">
            <div class="modal-body">
                <div class="kaizen-grid cols-2">
                    <div class="form-group">
                        <label class="form-label">Role Name *</label>
                        <input type="text" name="role_name" class="form-input" required
                               placeholder="e.g., quality_inspector">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="display_name" class="form-input"
                               placeholder="e.g., Quality Inspector">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea"
                              placeholder="Brief description of this role's purpose..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Hierarchy Level</label>
                    <select name="hierarchy_level" class="form-select">
                        <option value="70">Operator (70)</option>
                        <option value="60">Line Lead (60)</option>
                        <option value="50" selected>Supervisor (50)</option>
                        <option value="40">Engineer (40)</option>
                        <option value="30">Department Owner (30)</option>
                        <option value="20">PSO (20)</option>
                        <option value="10">System Admin (10)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateRoleModal()">Cancel</button>
                <button type="submit" name="create_role" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Role
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Permissions Modal -->
<?php if ($rbacTablesExist): ?>
<div id="permissionsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Manage Permissions - <span id="roleNameDisplay"></span></h2>
            <button class="modal-close" onclick="closePermissionsModal()">&times;</button>
        </div>
        <form method="post" id="permissionsForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['rbac_csrf_token'] ?>">
            <input type="hidden" name="role_id" id="selectedRoleId">
            <div class="modal-body">
                <div id="permissionsGrid" class="permission-grid">
                    <!-- Permissions will be loaded here via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePermissionsModal()">Cancel</button>
                <button type="submit" name="assign_permissions" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// Modal management functions
function openCreateRoleModal() {
    document.getElementById('createRoleModal').classList.add('show');
}

function closeCreateRoleModal() {
    document.getElementById('createRoleModal').classList.remove('show');
}

function closePermissionsModal() {
    document.getElementById('permissionsModal').classList.remove('show');
}

// Role editing function
function editRole(roleId) {
    alert('Role editing functionality - Role ID: ' + roleId);
}

// Permission management
<?php if ($rbacTablesExist): ?>
function managePermissions(roleId, roleName) {
    document.getElementById('selectedRoleId').value = roleId;
    document.getElementById('roleNameDisplay').textContent = roleName;

    // Load permissions via AJAX
    fetch('?action=get_role_permissions&role_id=' + roleId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPermissions(data.permissions);
                document.getElementById('permissionsModal').classList.add('show');
            } else {
                alert('Error loading permissions: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load permissions');
        });
}

function displayPermissions(permissions) {
    const grid = document.getElementById('permissionsGrid');
    grid.innerHTML = '';

    // Group permissions by category
    const categories = {};
    permissions.forEach(perm => {
        const category = perm.category || 'General';
        if (!categories[category]) {
            categories[category] = [];
        }
        categories[category].push(perm);
    });

    // Display permissions by category
    Object.keys(categories).forEach(category => {
        const categoryDiv = document.createElement('div');
        categoryDiv.style.gridColumn = '1 / -1';
        categoryDiv.innerHTML = '<h4 style="margin: 16px 0 8px; color: var(--brand-primary); border-bottom: 1px solid var(--neutral-300); padding-bottom: 4px;">' + category + '</h4>';
        grid.appendChild(categoryDiv);

        categories[category].forEach(perm => {
            const item = document.createElement('div');
            item.className = 'permission-item';
            item.innerHTML = `
                <input type="checkbox" class="permission-checkbox" name="permissions[]"
                       value="${perm.id}" ${perm.assigned ? 'checked' : ''}>
                <div class="permission-details">
                    <div class="permission-name">${perm.display_name || perm.name}</div>
                    <div class="permission-description">${perm.name}</div>
                </div>
            `;
            grid.appendChild(item);
        });
    });
}
<?php endif; ?>

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>