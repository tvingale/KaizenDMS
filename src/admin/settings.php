<?php
/**
 * Dms Management - Module Settings
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

$pageTitle = 'Dms Management - Settings';

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
        // Handle settings updates here
        $message = 'Settings saved successfully!';
        $messageType = 'success';
    }
}

// Get current settings (placeholder - implement your settings storage)
$settings = [
    'module_title' => 'Dms Management',
    'default_category' => 1,
    'enable_notifications' => true,
    'auto_assign' => false,
    'require_approval' => false,
    'default_priority' => 'medium',
    'items_per_page' => 25,
    'timezone' => 'Asia/Kolkata'
];

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
                        <h1 class="admin-title">Module Settings</h1>
                        <p class="admin-subtitle">Configure Dms Management system preferences</p>
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

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
        
        <div class="row">
            <!-- General Settings -->
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-content">
                            <div class="admin-card-indicator admin-card-primary"></div>
                            <h5 class="admin-card-title">General Settings</h5>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-group">
                            <label for="module_title" class="form-label">Module Title</label>
                            <input type="text" class="form-control" id="module_title" name="module_title" 
                                   value="<?= htmlspecialchars($settings['module_title']) ?>">
                            <small class="form-text text-muted">Display name for the module</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="default_category" class="form-label">Default Category</label>
                            <select class="form-control" id="default_category" name="default_category">
                                <?php
                                try {
                                    $stmt = $db->query("SELECT id, name FROM dms_categories WHERE is_active = 1 ORDER BY name");
                                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($categories as $category):
                                ?>
                                <option value="<?= $category['id'] ?>" <?= $settings['default_category'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; } catch (Exception $e) { echo '<option value="">No categories available</option>'; } ?>
                            </select>
                            <small class="form-text text-muted">Default category for new documents</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="default_priority" class="form-label">Default Priority</label>
                            <select class="form-control" id="default_priority" name="default_priority">
                                <option value="low" <?= $settings['default_priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= $settings['default_priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= $settings['default_priority'] === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="urgent" <?= $settings['default_priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                            <small class="form-text text-muted">Default priority for new documents</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="items_per_page" class="form-label">Items per Page</label>
                            <select class="form-control" id="items_per_page" name="items_per_page">
                                <option value="10" <?= $settings['items_per_page'] == 10 ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= $settings['items_per_page'] == 25 ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= $settings['items_per_page'] == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $settings['items_per_page'] == 100 ? 'selected' : '' ?>>100</option>
                            </select>
                            <small class="form-text text-muted">Number of items to display per page in lists</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Behavior Settings -->
            <div class="col-md-6">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-content">
                            <div class="admin-card-indicator admin-card-success"></div>
                            <h5 class="admin-card-title">Behavior Settings</h5>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="enable_notifications" 
                                       name="enable_notifications" <?= $settings['enable_notifications'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="enable_notifications">Enable Notifications</label>
                            </div>
                            <small class="form-text text-muted">Send notifications for document updates</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="auto_assign" 
                                       name="auto_assign" <?= $settings['auto_assign'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="auto_assign">Auto-assign documents</label>
                            </div>
                            <small class="form-text text-muted">Automatically assign documents to available users</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="require_approval" 
                                       name="require_approval" <?= $settings['require_approval'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="require_approval">Require Approval</label>
                            </div>
                            <small class="form-text text-muted">Require admin approval for certain actions</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-control" id="timezone" name="timezone">
                                <option value="Asia/Kolkata" <?= $settings['timezone'] === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (IST)</option>
                                <option value="UTC" <?= $settings['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <option value="America/New_York" <?= $settings['timezone'] === 'America/New_York' ? 'selected' : '' ?>>America/New_York (EST)</option>
                                <option value="Europe/London" <?= $settings['timezone'] === 'Europe/London' ? 'selected' : '' ?>>Europe/London (GMT)</option>
                                <option value="Asia/Tokyo" <?= $settings['timezone'] === 'Asia/Tokyo' ? 'selected' : '' ?>>Asia/Tokyo (JST)</option>
                            </select>
                            <small class="form-text text-muted">Default timezone for date/time display</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div class="admin-card-header-content">
                            <div class="admin-card-indicator admin-card-info"></div>
                            <h5 class="admin-card-title">System Information</h5>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-content">
                                    <div class="stat-label">Module Version</div>
                                    <div class="stat-number" style="font-size: 1.2rem;">1.0.0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-content">
                                    <div class="stat-label">PHP Version</div>
                                    <div class="stat-number" style="font-size: 1.2rem;"><?= PHP_VERSION ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-content">
                                    <div class="stat-label">Database</div>
                                    <div class="stat-number" style="font-size: 1.2rem;">
                                        <?php
                                        try {
                                            $stmt = $db->query("SELECT VERSION() as version");
                                            $version = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo 'MySQL ' . substr($version['version'], 0, 6);
                                        } catch (Exception $e) {
                                            echo 'MySQL';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-content">
                                    <div class="stat-label">KaizenAuth</div>
                                    <div class="stat-number" style="font-size: 1.2rem;">
                                        <span class="badge badge-success">Connected</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <strong>Configuration Info:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Module Prefix:</strong> <code>dms_</code></li>
                                <li><strong>App URL:</strong> <code><?= htmlspecialchars(APP_URL) ?></code></li>
                                <li><strong>Debug Mode:</strong> <?= DEBUG_MODE ? '<span class="badge badge-warning">Enabled</span>' : '<span class="badge badge-success">Disabled</span>' ?></li>
                                <li><strong>RBAC:</strong> <?= ENABLE_RBAC ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-secondary">Disabled</span>' ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="admin-button-group">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                    <button type="reset" class="btn btn-outline-secondary">Reset to Defaults</button>
                    <a href="../dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <strong>Implementation Note:</strong> This is a placeholder settings interface. Add your specific configuration options and persistence logic here.
                    Consider storing settings in a dedicated settings table or configuration files.
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>