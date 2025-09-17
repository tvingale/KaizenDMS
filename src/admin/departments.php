<?php
/**
 * Dms Management - Department Management
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

$pageTitle = 'Dms Management - Departments';

// Generate CSRF token
if (!isset($_SESSION['dms_csrf_token'])) {
    $_SESSION['dms_csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['dms_csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token invalid. Please refresh and try again.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO dms_departments (dept_code, dept_name, description, parent_dept_id, manager_user_id, manager_name, manager_email, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['dept_code'],
                    $_POST['dept_name'],
                    $_POST['description'],
                    empty($_POST['parent_dept_id']) ? null : $_POST['parent_dept_id'],
                    empty($_POST['manager_user_id']) ? null : intval($_POST['manager_user_id']),
                    $_POST['manager_name'] ?? null,
                    $_POST['manager_email'] ?? null,
                    $_POST['sort_order'] ?? 999
                ]);
                $message = 'Department created successfully!';
                $messageType = 'success';

            } elseif ($action === 'update') {
                $stmt = $db->prepare("UPDATE dms_departments SET dept_code = ?, dept_name = ?, description = ?, parent_dept_id = ?, manager_user_id = ?, manager_name = ?, manager_email = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['dept_code'],
                    $_POST['dept_name'],
                    $_POST['description'],
                    empty($_POST['parent_dept_id']) ? null : $_POST['parent_dept_id'],
                    empty($_POST['manager_user_id']) ? null : intval($_POST['manager_user_id']),
                    $_POST['manager_name'] ?? null,
                    $_POST['manager_email'] ?? null,
                    $_POST['sort_order'] ?? 999,
                    $_POST['department_id']
                ]);
                $message = 'Department updated successfully!';
                $messageType = 'success';

            } elseif ($action === 'delete') {
                $stmt = $db->prepare("UPDATE dms_departments SET is_active = 0 WHERE id = ?");
                $stmt->execute([$_POST['department_id']]);
                $message = 'Department deactivated successfully!';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get departments with hierarchy
$departments = [];
$parentDepartments = [];
try {
    $stmt = $db->query("SELECT * FROM dms_departments ORDER BY sort_order, dept_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get parent departments for dropdown
    $stmt = $db->query("SELECT id, dept_name FROM dms_departments WHERE is_active = 1 ORDER BY dept_name");
    $parentDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = 'Error loading departments: ' . $e->getMessage();
    $messageType = 'danger';
}

// Generate CSRF token for user search
if (!isset($_SESSION['user_search_csrf_token'])) {
    $_SESSION['user_search_csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../includes/header.php';
?>

<link href="../admin/admin-styles.css" rel="stylesheet">

<div class="container-fluid">
    <!-- Admin Header -->
    <div class="admin-header-card">
        <div class="admin-header-accent"></div>
        <div class="admin-header-content">
            <div class="row">
                <div class="col-md-8">
                    <div class="admin-header-text">
                        <h1 class="admin-title">Department Management</h1>
                        <p class="admin-subtitle">Organize your company structure with departments</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-header-meta">
                        <div class="admin-meta-text">
                            <div class="admin-name"><?= htmlspecialchars($user['name'] ?? $user['username']) ?></div>
                            <div class="admin-login">Administrator</div>
                        </div>
                        <div class="admin-action">
                            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Departments List -->
        <div class="col-md-8">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-primary"></div>
                        <h5 class="admin-card-title">Company Departments</h5>
                    </div>
                    <div class="admin-button-group">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addDepartmentModal">
                            Add Department
                        </button>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($departments)): ?>
                    <div class="alert alert-info">
                        No departments found. Create your first department to get started.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Department</th>
                                    <th>Manager</th>
                                    <th>Parent Dept</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments as $department): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($department['dept_code']) ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($department['dept_name']) ?></strong>
                                            <?php if ($department['description']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($department['description']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($department['manager_name']): ?>
                                        <div>
                                            <?= htmlspecialchars($department['manager_name']) ?>
                                            <?php if ($department['manager_email']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($department['manager_email']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($department['parent_dept_id']): ?>
                                            <?php
                                            $parentDept = array_filter($departments, function($d) use ($department) {
                                                return $d['id'] == $department['parent_dept_id'];
                                            });
                                            $parentDept = reset($parentDept);
                                            ?>
                                            <span class="badge badge-light"><?= htmlspecialchars($parentDept['dept_name'] ?? 'Unknown') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Root level</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $department['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $department['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editDepartment(<?= htmlspecialchars(json_encode($department)) ?>)">
                                                Edit
                                            </button>
                                            <?php if ($department['is_active']): ?>
                                            <form method="POST" class="d-inline-flex">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="department_id" value="<?= $department['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to deactivate this department?')">
                                                    Deactivate
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Department Statistics -->
        <div class="col-md-4">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-info"></div>
                        <h5 class="admin-card-title">Statistics</h5>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php
                    $activeDepartments = array_filter($departments, function($dept) { return $dept['is_active']; });
                    $inactiveDepartments = array_filter($departments, function($dept) { return !$dept['is_active']; });
                    $rootDepartments = array_filter($departments, function($dept) { return !$dept['parent_dept_id']; });
                    $subDepartments = array_filter($departments, function($dept) { return $dept['parent_dept_id']; });
                    ?>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-primary"></div>
                            Total Departments
                        </div>
                        <div class="stat-number"><?= count($departments) ?></div>
                    </div>

                    <hr>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-success"></div>
                            Active Departments
                        </div>
                        <div class="stat-number"><?= count($activeDepartments) ?></div>
                    </div>

                    <hr>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-info"></div>
                            Root Departments
                        </div>
                        <div class="stat-number"><?= count($rootDepartments) ?></div>
                    </div>

                    <hr>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-warning"></div>
                            Sub-Departments
                        </div>
                        <div class="stat-number"><?= count($subDepartments) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title">Add New Department</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dept_code">Department Code *</label>
                                <input type="text" class="form-control" id="dept_code" name="dept_code" required maxlength="20">
                                <small class="form-text text-muted">Unique identifier (e.g., QA, MFG, ENG)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dept_name">Department Name *</label>
                                <input type="text" class="form-control" id="dept_name" name="dept_name" required maxlength="100">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="parent_dept_id">Parent Department</label>
                                <select class="form-control" id="parent_dept_id" name="parent_dept_id">
                                    <option value="">None (Root Level)</option>
                                    <?php foreach ($parentDepartments as $parent): ?>
                                    <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['dept_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sort_order">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="999" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="manager_search">Department Manager</label>
                        <div style="position: relative;">
                            <input type="text" class="form-control" id="manager_search"
                                   placeholder="Search for user to assign as manager..."
                                   onkeyup="searchManagerUsers(this.value)" autocomplete="off">
                            <div id="manager_search_results" style="position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: white; border: 1px solid #ddd; border-top: none; z-index: 1000; display: none;"></div>
                        </div>
                        <small class="form-text text-muted">Start typing to search for users. Email will be auto-filled.</small>

                        <!-- Hidden fields for selected user -->
                        <input type="hidden" id="manager_user_id" name="manager_user_id">
                        <input type="hidden" id="manager_name" name="manager_name">
                        <input type="hidden" id="manager_email" name="manager_email">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="department_id" id="edit_department_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_dept_code">Department Code *</label>
                                <input type="text" class="form-control" id="edit_dept_code" name="dept_code" required maxlength="20">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_dept_name">Department Name *</label>
                                <input type="text" class="form-control" id="edit_dept_name" name="dept_name" required maxlength="100">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_parent_dept_id">Parent Department</label>
                                <select class="form-control" id="edit_parent_dept_id" name="parent_dept_id">
                                    <option value="">None (Root Level)</option>
                                    <?php foreach ($parentDepartments as $parent): ?>
                                    <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['dept_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_sort_order">Sort Order</label>
                                <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_manager_search">Department Manager</label>
                        <div style="position: relative;">
                            <input type="text" class="form-control" id="edit_manager_search"
                                   placeholder="Search for user to assign as manager..."
                                   onkeyup="searchEditManagerUsers(this.value)" autocomplete="off">
                            <div id="edit_manager_search_results" style="position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: white; border: 1px solid #ddd; border-top: none; z-index: 1000; display: none;"></div>
                        </div>
                        <small class="form-text text-muted">Start typing to search for users. Current: <span id="current_manager_display">Not assigned</span></small>

                        <!-- Hidden fields for selected user -->
                        <input type="hidden" id="edit_manager_user_id" name="manager_user_id">
                        <input type="hidden" id="edit_manager_name" name="manager_name">
                        <input type="hidden" id="edit_manager_email" name="manager_email">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearManagerSelection()" style="margin-top: 5px;">
                            <i class="fas fa-times"></i> Clear Manager
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDepartment(department) {
    document.getElementById('edit_department_id').value = department.id;
    document.getElementById('edit_dept_code').value = department.dept_code;
    document.getElementById('edit_dept_name').value = department.dept_name;
    document.getElementById('edit_description').value = department.description || '';
    document.getElementById('edit_parent_dept_id').value = department.parent_dept_id || '';
    document.getElementById('edit_sort_order').value = department.sort_order || 999;

    // Handle manager assignment
    if (department.manager_name) {
        document.getElementById('edit_manager_search').value = department.manager_name;
        document.getElementById('edit_manager_name').value = department.manager_name;
        document.getElementById('edit_manager_email').value = department.manager_email || '';
        document.getElementById('current_manager_display').textContent = department.manager_name;
    } else {
        document.getElementById('edit_manager_search').value = '';
        document.getElementById('edit_manager_name').value = '';
        document.getElementById('edit_manager_email').value = '';
        document.getElementById('current_manager_display').textContent = 'Not assigned';
    }

    $('#editDepartmentModal').modal('show');
}

// User search functionality for manager assignment
let searchTimeout;

function searchManagerUsers(query) {
    clearTimeout(searchTimeout);

    if (query.length < 2) {
        document.getElementById('manager_search_results').style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        const encodedQuery = encodeURIComponent(query);
        const url = `../api/dms_user_search.php?q=${encodedQuery}&csrf_token=<?= urlencode($_SESSION['user_search_csrf_token']) ?>`;

        fetch(url)
        .then(response => response.json())
        .then(data => {
            displayManagerSearchResults(data, 'manager_search_results');
        })
        .catch(error => {
            console.error('User search error:', error);
        });
    }, 300);
}

function searchEditManagerUsers(query) {
    clearTimeout(searchTimeout);

    if (query.length < 2) {
        document.getElementById('edit_manager_search_results').style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        const encodedQuery = encodeURIComponent(query);
        const url = `../api/dms_user_search.php?q=${encodedQuery}&csrf_token=<?= urlencode($_SESSION['user_search_csrf_token']) ?>`;

        fetch(url)
        .then(response => response.json())
        .then(data => {
            displayManagerSearchResults(data, 'edit_manager_search_results');
        })
        .catch(error => {
            console.error('User search error:', error);
        });
    }, 300);
}

function displayManagerSearchResults(data, resultsContainerId) {
    const resultsContainer = document.getElementById(resultsContainerId);

    if (data.status !== 'success' || !data.data || !data.data.users || data.data.users.length === 0) {
        resultsContainer.innerHTML = '<div style="padding: 12px; color: #666; text-align: center;">No users found</div>';
        resultsContainer.style.display = 'block';
        return;
    }

    let html = '';
    data.data.users.forEach(user => {
        const userName = user.name || user.username || 'Unknown';
        const userEmail = user.email || 'No email';
        const userDept = user.department || '';
        const userRole = user.role || '';

        html += `
            <div onclick="selectManager('${user.id}', '${escapeHtml(user.username || '')}', '${escapeHtml(user.email || '')}', '${escapeHtml(user.name || '')}', '${resultsContainerId}')"
                 style="cursor: pointer; padding: 12px; border-bottom: 1px solid #eee; transition: all 0.2s ease;"
                 onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='white'">
                <div style="font-weight: 600; margin-bottom: 4px; color: #333;">${escapeHtml(userName)}</div>
                <div style="font-size: 12px; color: #666; margin-bottom: 2px;">${escapeHtml(userEmail)}</div>
                <div style="font-size: 11px; color: #999; display: flex; justify-content: space-between;">
                    <span>ID: ${user.id}</span>
                    <span>${escapeHtml(userRole)}${userDept ? ' • ' + escapeHtml(userDept) : ''}</span>
                </div>
            </div>
        `;
    });

    resultsContainer.innerHTML = html;
    resultsContainer.style.display = 'block';
}

function selectManager(id, username, email, name, resultsContainerId) {
    const isEdit = resultsContainerId.includes('edit_');
    const prefix = isEdit ? 'edit_' : '';

    const displayName = name || username || ('User #' + id);

    // Set form values
    document.getElementById(prefix + 'manager_search').value = displayName;
    document.getElementById(prefix + 'manager_user_id').value = id;
    document.getElementById(prefix + 'manager_name').value = displayName;
    document.getElementById(prefix + 'manager_email').value = email;

    // Update current manager display for edit form
    if (isEdit) {
        document.getElementById('current_manager_display').textContent = displayName;
    }

    // Hide results
    document.getElementById(resultsContainerId).style.display = 'none';
}

function clearManagerSelection() {
    document.getElementById('edit_manager_search').value = '';
    document.getElementById('edit_manager_user_id').value = '';
    document.getElementById('edit_manager_name').value = '';
    document.getElementById('edit_manager_email').value = '';
    document.getElementById('current_manager_display').textContent = 'Not assigned';
    document.getElementById('edit_manager_search_results').style.display = 'none';
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return (text || '').replace(/[&<>"']/g, m => map[m]);
}

// Hide search results when clicking outside
document.addEventListener('click', function(e) {
    const managerResults = document.getElementById('manager_search_results');
    const editManagerResults = document.getElementById('edit_manager_search_results');

    if (!e.target.closest('#manager_search') && !e.target.closest('#manager_search_results')) {
        if (managerResults) managerResults.style.display = 'none';
    }

    if (!e.target.closest('#edit_manager_search') && !e.target.closest('#edit_manager_search_results')) {
        if (editManagerResults) editManagerResults.style.display = 'none';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>