<?php
/**
 * Roles & Permissions Management - Modern Design
 * Manage system roles and their associated permissions
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

// Set page title
$pageTitle = 'Roles & Permissions Management';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['roles_csrf_token'])) {
        $_SESSION['roles_csrf_token'] = bin2hex(random_bytes(32));
    }
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['roles_csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token invalid. Please refresh the page and try again.';
        $messageType = 'danger';
    } else {
        // Process form actions here
        if (isset($_POST['create_role'])) {
            try {
                $roleName = trim($_POST['role_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $priority = intval($_POST['priority'] ?? 5);
                
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
                    INSERT INTO dms_roles (name, description, permissions, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$roleName, $description, $description]);
                
                $message = 'Role "' . htmlspecialchars($roleName) . '" created successfully!';
                $messageType = 'success';
                
                // Refresh the data
                header('Location: roles_permissions.php');
                exit;
                
            } catch (Exception $e) {
                $message = 'Error creating role: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['roles_csrf_token'])) {
    $_SESSION['roles_csrf_token'] = bin2hex(random_bytes(32));
}

// Get existing roles and permissions
$stmt = $db->query("SELECT * FROM dms_roles ORDER BY name");
$roles = $stmt->fetchAll();

$stmt = $db->query("
    SELECT ur.*, r.name as role_name, ur.user_id
    FROM dms_user_roles ur 
    LEFT JOIN dms_roles r ON ur.role_id = r.id 
    ORDER BY ur.granted_at DESC 
    LIMIT 10
");
$recentAssignments = $stmt->fetchAll();

require_once '../includes/header.php';
echo '<link rel="stylesheet" href="admin-styles.css">';
?>

<div class="container-fluid">
    
    <!-- Clean Professional Header -->
    <div class="row">
        <div class="col-12">
            <div class="admin-header-card">
                <div class="admin-header-accent"></div>
                <div class="admin-header-content">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="admin-header-text">
                                <h1 class="admin-title">Roles & Permissions</h1>
                                <p class="admin-subtitle">Manage user roles and system permissions</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="admin-header-meta">
                                <div class="admin-action">
                                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-arrow-left"></i> Back to Admin
                                    </a>
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

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="modern-stat-card">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body bg-white p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="stat-icon bg-danger">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="stat-number"><?= count($roles) ?></div>
                                <div class="stat-label">System Roles</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="modern-stat-card">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body bg-white p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="stat-icon bg-light border">
                                    <i class="fas fa-shield-alt text-muted"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="stat-number">12</div>
                                <div class="stat-label">Permissions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="modern-stat-card">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body bg-white p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="stat-icon bg-light border">
                                    <i class="fas fa-user-check text-muted"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="stat-number"><?= count($recentAssignments) ?></div>
                                <div class="stat-label">Active Assignments</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="modern-stat-card">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body bg-white p-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="stat-icon bg-light border">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="stat-number">RBAC</div>
                                <div class="stat-label">System Status</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        
        <!-- Roles Management -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-users-cog mr-2 text-white"></i>
                        <span class="text-white">System Roles</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 modern-table">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4 py-3">Role Name</th>
                                    <th class="border-0 px-4 py-3">Description</th>
                                    <th class="border-0 px-4 py-3">Status</th>
                                    <th class="border-0 px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="role-avatar me-3">
                                                <i class="fas fa-user-tag text-danger"></i>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold"><?= htmlspecialchars($role['name']) ?></div>
                                                <small class="text-muted">ID: <?= $role['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-muted"><?= htmlspecialchars($role['description'] ?? 'No description') ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge badge-success px-3 py-2">Active</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-shield-alt mr-1"></i>Permissions
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-bolt mr-2 text-white"></i>
                        <span class="text-white">Quick Actions</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <button class="btn btn-danger btn-lg" data-toggle="modal" data-target="#createRoleModal">
                            <i class="fas fa-plus mr-2"></i>Create New Role
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="fas fa-download mr-2"></i>Export Roles
                        </button>
                        <button class="btn btn-outline-dark">
                            <i class="fas fa-sync-alt mr-2"></i>Sync Permissions
                        </button>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="text-muted mb-3">Recent Assignments</h6>
                    <div class="recent-assignments">
                        <?php foreach (array_slice($recentAssignments, 0, 5) as $assignment): ?>
                        <div class="assignment-item d-flex align-items-center mb-2">
                            <div class="assignment-avatar me-2">
                                <i class="fas fa-user-circle text-muted"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="d-block font-weight-bold">User <?= $assignment['user_id'] ?></small>
                                <small class="text-muted"><?= $assignment['role_name'] ?></small>
                            </div>
                            <small class="text-muted"><?= date('M j', strtotime($assignment['granted_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title text-white">
                    <i class="fas fa-plus mr-2"></i>Create New Role
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['roles_csrf_token'] ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">Role Name</label>
                                <input type="text" class="form-control" name="role_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-weight-bold">Priority Level</label>
                                <select class="form-control" name="priority">
                                    <option value="1">Low</option>
                                    <option value="5" selected>Medium</option>
                                    <option value="10">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label font-weight-bold">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_role" class="btn btn-danger">
                        <i class="fas fa-plus mr-2"></i>Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modern-page-header .header-icon-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-danger {
    background: #dc3545;
}

.modern-stat-card {
    transition: all 0.3s ease;
}

.modern-stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.modern-table tbody tr {
    transition: all 0.2s ease;
}

.modern-table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
}

.role-avatar {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: rgba(220, 53, 69, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.assignment-item {
    padding: 8px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.assignment-item:hover {
    background: rgba(0,0,0,0.05);
}

.assignment-avatar {
    font-size: 1.2rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>