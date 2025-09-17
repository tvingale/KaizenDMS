<?php
/**
 * Advanced RBAC Management Interface
 * Complete role and permission management system
 */

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/kaizen_sso.php';
require_once '../includes/AccessControl.php';
require_once '../includes/AdditivePermissionManager.php';

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

// Initialize permission manager
$permissionManager = new AdditivePermissionManager($db);

$pageTitle = 'Advanced RBAC Management';

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_role_permissions') {
    header('Content-Type: application/json');
    $role_id = intval($_GET['role_id'] ?? 0);

    if ($role_id > 0) {
        $stmt = $db->prepare("
            SELECT p.id, p.name, p.display_name, p.category, p.scope_level,
                   CASE WHEN rp.role_id IS NOT NULL THEN 1 ELSE 0 END as assigned
            FROM dms_permissions p
            LEFT JOIN dms_role_permissions rp ON p.id = rp.permission_id AND rp.role_id = ?
            ORDER BY p.category, p.name
        ");
        $stmt->execute([$role_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo json_encode([]);
    }
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_SESSION['rbac_csrf_token'])) {
        $_SESSION['rbac_csrf_token'] = bin2hex(random_bytes(32));
    }

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['rbac_csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token invalid. Please refresh the page and try again.';
        $messageType = 'danger';
    } else {
        try {
            // Handle role-permission assignment
            if (isset($_POST['update_role_permissions'])) {
                $role_id = intval($_POST['role_id']);
                $permission_ids = $_POST['permission_ids'] ?? [];

                // Clear existing permissions for this role
                $stmt = $db->prepare("DELETE FROM dms_role_permissions WHERE role_id = ?");
                $stmt->execute([$role_id]);

                // Add new permissions
                $stmt = $db->prepare("
                    INSERT INTO dms_role_permissions (role_id, permission_id, granted_scope, granted_by, granted_at)
                    VALUES (?, ?, 'default', ?, NOW())
                ");

                $assigned_count = 0;
                foreach ($permission_ids as $permission_id) {
                    $stmt->execute([$role_id, intval($permission_id), $user['id']]);
                    $assigned_count++;
                }

                $message = "Successfully updated role permissions. {$assigned_count} permissions assigned.";
                $messageType = 'success';
            }

            // Handle custom role creation
            if (isset($_POST['create_custom_role'])) {
                $role_name = trim($_POST['role_name']);
                $display_name = trim($_POST['display_name']);
                $description = trim($_POST['description']);
                $hierarchy_level = intval($_POST['hierarchy_level']);
                $scope = $_POST['scope'];

                $stmt = $db->prepare("
                    INSERT INTO dms_roles (name, role_name, display_name, description, hierarchy_level, scope, is_system_role, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([$role_name, $role_name, $display_name, $description, $hierarchy_level, $scope]);

                $message = "Custom role '{$display_name}' created successfully.";
                $messageType = 'success';
            }

        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['rbac_csrf_token'])) {
    $_SESSION['rbac_csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch data for display
$roles = $db->query("SELECT * FROM dms_roles ORDER BY hierarchy_level, name")->fetchAll(PDO::FETCH_ASSOC);
$permissions = $db->query("SELECT * FROM dms_permissions ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);
$permission_categories = $db->query("SELECT DISTINCT category FROM dms_permissions ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Get role statistics
$role_stats = [];
foreach ($roles as $role) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM dms_role_permissions WHERE role_id = ?");
    $stmt->execute([$role['id']]);
    $role_stats[$role['id']] = $stmt->fetchColumn();
}

// Get user assignment counts
$user_stats = [];
foreach ($roles as $role) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM dms_user_roles WHERE role_id = ? AND status = 'active'");
    $stmt->execute([$role['id']]);
    $user_stats[$role['id']] = $stmt->fetchColumn();
}

require_once '../includes/header.php';
?>

<link href="admin-styles.css" rel="stylesheet">

<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12">
            <div class="admin-header-card">
                <div class="admin-header-accent"></div>
                <div class="admin-header-content">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="admin-header-text">
                                <h1 class="admin-title">🔐 Advanced RBAC Management</h1>
                                <p class="admin-subtitle">Comprehensive role-based access control system</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-header-meta">
                                <div class="admin-meta-text">
                                    <div class="admin-name"><?= htmlspecialchars($user['name'] ?? $user['username']) ?></div>
                                    <div class="admin-login">RBAC Administrator</div>
                                </div>
                                <div class="admin-action">
                                    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Back to Admin</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- RBAC Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body bg-primary text-white p-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-number"><?= count($roles) ?></div>
                            <div class="stat-label">Total Roles</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body bg-success text-white p-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-key text-white"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-number"><?= count($permissions) ?></div>
                            <div class="stat-label">Permissions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body bg-info text-white p-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-link text-white"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-number"><?= array_sum($role_stats) ?></div>
                            <div class="stat-label">Role-Permission Mappings</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body bg-warning text-white p-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-user-check text-white"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-number"><?= array_sum($user_stats) ?></div>
                            <div class="stat-label">Active User Assignments</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="rbacTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="roles-tab" data-toggle="tab" href="#roles" role="tab">
                                <i class="fas fa-users mr-2"></i>Role Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="permissions-tab" data-toggle="tab" href="#permissions" role="tab">
                                <i class="fas fa-key mr-2"></i>Permission Assignment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="hierarchy-tab" data-toggle="tab" href="#hierarchy" role="tab">
                                <i class="fas fa-sitemap mr-2"></i>Role Hierarchy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="overview-tab" data-toggle="tab" href="#overview" role="tab">
                                <i class="fas fa-chart-bar mr-2"></i>System Overview
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="rbacTabContent">

                        <!-- Role Management Tab -->
                        <div class="tab-pane fade show active" id="roles" role="tabpanel">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">System Roles</h5>
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#createRoleModal">
                                        <i class="fas fa-plus mr-2"></i>Create Custom Role
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Role</th>
                                                <th>Hierarchy</th>
                                                <th>Scope</th>
                                                <th>Permissions</th>
                                                <th>Users</th>
                                                <th>Type</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="role-icon me-3">
                                                            <i class="fas fa-<?= $role['is_system_role'] ? 'cog' : 'user-tag' ?> text-<?= $role['is_system_role'] ? 'primary' : 'secondary' ?>"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-weight-bold"><?= htmlspecialchars($role['display_name'] ?? $role['name']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($role['role_name'] ?? $role['name']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-outline-primary">
                                                        Level <?= $role['hierarchy_level'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?=
                                                        $role['scope'] === 'all' ? 'danger' :
                                                        ($role['scope'] === 'cross_department' ? 'warning' :
                                                        ($role['scope'] === 'department' ? 'info' : 'secondary'))
                                                    ?>">
                                                        <?= ucwords(str_replace('_', ' ', $role['scope'] ?? 'assigned_only')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success"><?= $role_stats[$role['id']] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info"><?= $user_stats[$role['id']] ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($role['is_system_role']): ?>
                                                        <span class="badge badge-primary">System</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Custom</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" onclick="editRolePermissions(<?= $role['id'] ?>, '<?= htmlspecialchars($role['display_name'] ?? $role['name']) ?>')">
                                                            <i class="fas fa-key mr-1"></i>Permissions
                                                        </button>
                                                        <?php if (!$role['is_system_role']): ?>
                                                        <button class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-edit mr-1"></i>Edit
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Permission Assignment Tab -->
                        <div class="tab-pane fade" id="permissions" role="tabpanel">
                            <div class="p-4">
                                <h5 class="mb-4">Permission Assignment</h5>
                                <div id="permissionAssignmentContent">
                                    <div class="text-center py-5">
                                        <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Select a role from the Role Management tab to assign permissions</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Role Hierarchy Tab -->
                        <div class="tab-pane fade" id="hierarchy" role="tabpanel">
                            <div class="p-4">
                                <h5 class="mb-4">Role Hierarchy Visualization</h5>
                                <div class="hierarchy-container">
                                    <?php
                                    $system_roles = array_filter($roles, function($r) { return $r['is_system_role']; });
                                    usort($system_roles, function($a, $b) { return $a['hierarchy_level'] - $b['hierarchy_level']; });
                                    ?>

                                    <div class="hierarchy-tree">
                                        <?php foreach ($system_roles as $i => $role): ?>
                                        <div class="hierarchy-level">
                                            <div class="hierarchy-card level-<?= $role['hierarchy_level'] ?>">
                                                <div class="hierarchy-header">
                                                    <div class="hierarchy-title"><?= htmlspecialchars($role['display_name']) ?></div>
                                                    <div class="hierarchy-level-badge">Level <?= $role['hierarchy_level'] ?></div>
                                                </div>
                                                <div class="hierarchy-details">
                                                    <div class="hierarchy-scope"><?= ucwords(str_replace('_', ' ', $role['scope'])) ?> Scope</div>
                                                    <div class="hierarchy-permissions"><?= $role_stats[$role['id']] ?> Permissions</div>
                                                    <div class="hierarchy-users"><?= $user_stats[$role['id']] ?> Users</div>
                                                </div>
                                            </div>
                                            <?php if ($i < count($system_roles) - 1): ?>
                                            <div class="hierarchy-connector"></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Overview Tab -->
                        <div class="tab-pane fade" id="overview" role="tabpanel">
                            <div class="p-4">
                                <h5 class="mb-4">RBAC System Overview</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Permission Categories</h6>
                                                <?php foreach ($permission_categories as $category):
                                                    $count = count(array_filter($permissions, function($p) use ($category) { return $p['category'] === $category; }));
                                                ?>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-capitalize"><?= str_replace('_', ' ', $category) ?></span>
                                                    <span class="badge badge-secondary"><?= $count ?></span>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">System Health</h6>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>System Roles</span>
                                                    <span class="badge badge-success"><?= count(array_filter($roles, function($r) { return $r['is_system_role']; })) ?>/7</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Total Permissions</span>
                                                    <span class="badge badge-success"><?= count($permissions) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Active Mappings</span>
                                                    <span class="badge badge-success"><?= array_sum($role_stats) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>RBAC Status</span>
                                                    <span class="badge badge-success">Operational</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Custom Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['rbac_csrf_token'] ?>">
                <input type="hidden" name="create_custom_role" value="1">

                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-plus mr-2"></i>Create Custom Role
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">Role Name *</label>
                                <input type="text" class="form-control" name="role_name" required>
                                <small class="form-text text-muted">Internal role identifier (lowercase, no spaces)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">Display Name *</label>
                                <input type="text" class="form-control" name="display_name" required>
                                <small class="form-text text-muted">User-friendly role name</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">Hierarchy Level</label>
                                <select class="form-control" name="hierarchy_level" required>
                                    <option value="">Select hierarchy level...</option>
                                    <option value="75">75 - Below Operator</option>
                                    <option value="65">65 - Between Line Lead and Supervisor</option>
                                    <option value="55">55 - Between Supervisor and Engineer</option>
                                    <option value="45">45 - Between Engineer and Dept Owner</option>
                                    <option value="35">35 - Between Dept Owner and PSO</option>
                                </select>
                                <small class="form-text text-muted">Lower numbers = higher authority</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">Default Scope</label>
                                <select class="form-control" name="scope" required>
                                    <option value="assigned_only">Assigned Only</option>
                                    <option value="station">Station Level</option>
                                    <option value="process_area">Process Area</option>
                                    <option value="department">Department</option>
                                    <option value="cross_department">Cross Department</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label font-weight-bold">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                        <small class="form-text text-muted">Describe the role's purpose and responsibilities</small>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Permission Assignment Modal -->
<div class="modal fade" id="permissionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title text-white" id="permissionModalTitle">
                    <i class="fas fa-key mr-2"></i>Assign Permissions
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" id="permissionForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['rbac_csrf_token'] ?>">
                <input type="hidden" name="update_role_permissions" value="1">
                <input type="hidden" name="role_id" id="roleIdInput">

                <div class="modal-body p-0">
                    <div id="permissionContent">
                        <!-- Dynamic content loaded via JavaScript -->
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save mr-2"></i>Save Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.role-icon {
    margin-right: 12px;
}

.badge-outline-primary {
    color: #007bff;
    border: 1px solid #007bff;
    background: transparent;
}

.hierarchy-container {
    max-width: 800px;
    margin: 0 auto;
}

.hierarchy-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.hierarchy-level {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 10px 0;
}

.hierarchy-card {
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    width: 300px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.hierarchy-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.hierarchy-card.level-10 { border-color: #dc3545; }
.hierarchy-card.level-20 { border-color: #fd7e14; }
.hierarchy-card.level-30 { border-color: #ffc107; }
.hierarchy-card.level-40 { border-color: #28a745; }
.hierarchy-card.level-50 { border-color: #17a2b8; }
.hierarchy-card.level-60 { border-color: #6f42c1; }
.hierarchy-card.level-70 { border-color: #6c757d; }

.hierarchy-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 12px;
}

.hierarchy-title {
    font-weight: bold;
    font-size: 1.1rem;
    flex: 1;
}

.hierarchy-level-badge {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
}

.hierarchy-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    font-size: 0.875rem;
}

.hierarchy-connector {
    width: 2px;
    height: 30px;
    background: #dee2e6;
    margin: 5px 0;
}

.hierarchy-scope {
    color: #6f42c1;
    font-weight: 500;
}

.hierarchy-permissions {
    color: #28a745;
    font-weight: 500;
}

.hierarchy-users {
    color: #17a2b8;
    font-weight: 500;
    grid-column: span 2;
    text-align: center;
}
</style>

<script>
function editRolePermissions(roleId, roleName) {
    document.getElementById('roleIdInput').value = roleId;
    document.getElementById('permissionModalTitle').innerHTML = '<i class="fas fa-key mr-2"></i>Assign Permissions - ' + roleName;

    // Load permissions via AJAX
    fetch('?action=get_role_permissions&role_id=' + roleId)
        .then(response => response.json())
        .then(data => {
            let content = '<div class="p-4">';

            // Group permissions by category
            let categories = {};
            data.forEach(perm => {
                if (!categories[perm.category]) {
                    categories[perm.category] = [];
                }
                categories[perm.category].push(perm);
            });

            // Generate content for each category
            Object.keys(categories).sort().forEach(category => {
                content += '<div class="mb-4">';
                content += '<h6 class="text-primary mb-3"><i class="fas fa-folder mr-2"></i>' + category.charAt(0).toUpperCase() + category.slice(1).replace('_', ' ') + '</h6>';
                content += '<div class="row">';

                categories[category].forEach(perm => {
                    let scopeColor = perm.scope_level === 'all' ? 'danger' :
                                   (perm.scope_level === 'cross_department' ? 'warning' :
                                   (perm.scope_level === 'department' ? 'info' : 'secondary'));

                    content += '<div class="col-md-6 mb-2">';
                    content += '<div class="custom-control custom-checkbox">';
                    content += '<input type="checkbox" class="custom-control-input" id="perm_' + perm.id + '" name="permission_ids[]" value="' + perm.id + '"' + (perm.assigned ? ' checked' : '') + '>';
                    content += '<label class="custom-control-label" for="perm_' + perm.id + '">';
                    content += '<div class="d-flex justify-content-between align-items-center">';
                    content += '<span>' + perm.display_name + '</span>';
                    content += '<span class="badge badge-' + scopeColor + ' ml-2">' + perm.scope_level.replace('_', ' ') + '</span>';
                    content += '</div>';
                    content += '<small class="text-muted d-block">' + perm.name + '</small>';
                    content += '</label>';
                    content += '</div>';
                    content += '</div>';
                });

                content += '</div>';
                content += '</div>';
            });

            content += '</div>';

            document.getElementById('permissionContent').innerHTML = content;
            $('#permissionModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading permissions:', error);
            alert('Error loading permissions. Please try again.');
        });
}

// Auto-hide alerts
setTimeout(() => {
    $('.alert').alert('close');
}, 5000);
</script>

<?php require_once '../includes/footer.php'; ?>