<?php
/**
 * Dms Management - Category Management
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

$pageTitle = 'Dms Management - Categories';

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
                $stmt = $db->prepare("INSERT INTO dms_categories (name, description, color, icon, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['color'],
                    $_POST['icon'],
                    $user['id']
                ]);
                $message = 'Category created successfully!';
                $messageType = 'success';
                
            } elseif ($action === 'update') {
                $stmt = $db->prepare("UPDATE dms_categories SET name = ?, description = ?, color = ?, icon = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'], 
                    $_POST['color'],
                    $_POST['icon'],
                    $_POST['category_id']
                ]);
                $message = 'Category updated successfully!';
                $messageType = 'success';
                
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("UPDATE dms_categories SET is_active = 0 WHERE id = ?");
                $stmt->execute([$_POST['category_id']]);
                $message = 'Category deactivated successfully!';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get categories
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM dms_categories ORDER BY sort_order, name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = 'Error loading categories: ' . $e->getMessage();
    $messageType = 'danger';
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
                        <h1 class="admin-title">Category Management</h1>
                        <p class="admin-subtitle">Organize documents with categories</p>
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
        <!-- Categories List -->
        <div class="col-md-8">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-primary"></div>
                        <h5 class="admin-card-title">document Categories</h5>
                    </div>
                    <div class="admin-button-group">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                            Add Category
                        </button>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($categories)): ?>
                    <div class="alert alert-info">
                        No categories found. Create your first category to get started.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>documents</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span style="color: <?= htmlspecialchars($category['color']) ?>; margin-right: 8px;">
                                                <?= htmlspecialchars($category['icon']) ?>
                                            </span>
                                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($category['description']) ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php
                                            try {
                                                $stmt = $db->prepare("SELECT COUNT(*) FROM dms_documents WHERE category_id = ?");
                                                $stmt->execute([$category['id']]);
                                                echo $stmt->fetchColumn();
                                            } catch (Exception $e) {
                                                echo '0';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="admin-button-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)">
                                                Edit
                                            </button>
                                            <?php if ($category['is_active']): ?>
                                            <form method="POST" class="d-inline-flex">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to deactivate this category?')">
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

        <!-- Category Statistics -->
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
                    $activeCategories = array_filter($categories, function($cat) { return $cat['is_active']; });
                    $inactiveCategories = array_filter($categories, function($cat) { return !$cat['is_active']; });
                    ?>
                    
                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-primary"></div>
                            Total Categories
                        </div>
                        <div class="stat-number"><?= count($categories) ?></div>
                    </div>
                    
                    <hr>
                    
                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-success"></div>
                            Active Categories
                        </div>
                        <div class="stat-number"><?= count($activeCategories) ?></div>
                    </div>
                    
                    <hr>
                    
                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-secondary"></div>
                            Inactive Categories
                        </div>
                        <div class="stat-number"><?= count($inactiveCategories) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="color">Color</label>
                                <input type="color" class="form-control" id="color" name="color" value="#C53A3A">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="icon">Icon (Emoji)</label>
                                <input type="text" class="form-control" id="icon" name="icon" value="📁" placeholder="📁">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="category_id" id="edit_category_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Category Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_color">Color</label>
                                <input type="color" class="form-control" id="edit_color" name="color" value="#C53A3A">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_icon">Icon (Emoji)</label>
                                <input type="text" class="form-control" id="edit_icon" name="icon">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_color').value = category.color || '#007bff';
    document.getElementById('edit_icon').value = category.icon || '📁';
    $('#editCategoryModal').modal('show');
}
</script>

<?php require_once '../includes/footer.php'; ?>