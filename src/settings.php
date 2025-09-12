<?php
/**
 * Dms Management - User Settings Page  
 * User preferences and configuration options
 */

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/kaizen_sso.php';
require_once 'includes/AccessControl.php';

// Initialize SSO
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);

// Check authentication
if (!$sso->isAuthenticated()) {
    header('Location: sso.php');
    exit;
}

$user = $sso->getUserInfo();
$db = getDB();

// Apply unified access control - require module access
$accessControl = AccessControl::requireAccess();

$pageTitle = 'Settings';

// Generate CSRF token
if (!isset($_SESSION['settings_csrf_token'])) {
    $_SESSION['settings_csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['settings_csrf_token'], $_POST['csrf_token'])) {
        $error = 'Security token invalid. Please refresh the page and try again.';
    } else {
    $success = false;
    $error = '';
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_general':
                // Handle general settings update
                try {
                    $appName = trim($_POST['app_name']) ?: 'Dms Management';
                    $itemsPerPage = intval($_POST['items_per_page']) ?: 25;
                    
                    // Save settings to database
                    $stmt = $db->prepare("
                        INSERT INTO dms_settings (setting_key, setting_value, updated_by, updated_at)
                        VALUES ('app_name', ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()
                    ");
                    $stmt->execute([$appName, $user['id']]);
                    
                    $stmt = $db->prepare("
                        INSERT INTO dms_settings (setting_key, setting_value, updated_by, updated_at)
                        VALUES ('items_per_page', ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()
                    ");
                    $stmt->execute([$itemsPerPage, $user['id']]);
                    
                    $success = true;
                    $_SESSION['success_message'] = "General settings updated successfully.";
                } catch (Exception $e) {
                    $error = "Failed to update general settings: " . $e->getMessage();
                }
                break;
                
            case 'update_notifications':
                // Handle notification settings update
                try {
                    $notifications = $_POST['notifications'] ?? [];
                    $notificationSettings = json_encode($notifications);
                    
                    $stmt = $db->prepare("
                        INSERT INTO dms_settings (setting_key, setting_value, updated_by, updated_at)
                        VALUES ('notification_preferences', ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()
                    ");
                    $stmt->execute([$notificationSettings, $user['id']]);
                    
                    $success = true;
                    $_SESSION['success_message'] = "Notification settings updated successfully.";
                } catch (Exception $e) {
                    $error = "Failed to update notification settings: " . $e->getMessage();
                }
                break;
                
            case 'clear_cache':
                // Handle cache clearing
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                }
                $success = true;
                $_SESSION['success_message'] = "System cache cleared successfully.";
                break;
        }
    }
    
    if ($success) {
        header('Location: settings.php');
        exit;
    } else if ($error) {
        $_SESSION['error_message'] = $error;
    }
    } // Close CSRF else block
}

// Load current settings from database
$settings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM dms_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Failed to load settings: " . $e->getMessage());
}

// Set default values
$currentAppName = $settings['app_name'] ?? 'Dms Management';
$currentItemsPerPage = $settings['items_per_page'] ?? '25';
$currentNotifications = json_decode($settings['notification_preferences'] ?? '[]', true);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cog"></i> Dms Management Settings
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Settings Navigation Tabs -->
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">
                                <i class="fas fa-sliders-h"></i> General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="notifications-tab" data-toggle="tab" href="#notifications" role="tab">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab">
                                <i class="fas fa-server"></i> System
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="settingsTabContent">
                        <!-- General Settings Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="update_general">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['settings_csrf_token'] ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="app_name" class="form-label">Application Name</label>
                                            <input type="text" class="form-control" id="app_name" name="app_name" value="<?= htmlspecialchars($currentAppName) ?>" required>
                                            <div class="form-text">The display name for this module</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="items_per_page" class="form-label">Items Per Page</label>
                                            <select class="form-control" id="items_per_page" name="items_per_page">
                                                <option value="10" <?= $currentItemsPerPage == '10' ? 'selected' : '' ?>>10</option>
                                                <option value="25" <?= $currentItemsPerPage == '25' ? 'selected' : '' ?>>25</option>
                                                <option value="50" <?= $currentItemsPerPage == '50' ? 'selected' : '' ?>>50</option>
                                                <option value="100" <?= $currentItemsPerPage == '100' ? 'selected' : '' ?>>100</option>
                                            </select>
                                            <div class="form-text">Number of documents to display per page</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_format" class="form-label">Date Format</label>
                                            <select class="form-control" id="date_format" name="date_format">
                                                <option value="Y-m-d" selected>YYYY-MM-DD (2024-01-15)</option>
                                                <option value="m/d/Y">MM/DD/YYYY (01/15/2024)</option>
                                                <option value="d/m/Y">DD/MM/YYYY (15/01/2024)</option>
                                                <option value="M j, Y">Month DD, YYYY (Jan 15, 2024)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">Timezone</label>
                                            <select class="form-control" id="timezone" name="timezone">
                                                <option value="UTC" selected>UTC</option>
                                                <option value="America/New_York">Eastern Time</option>
                                                <option value="America/Chicago">Central Time</option>
                                                <option value="America/Denver">Mountain Time</option>
                                                <option value="America/Los_Angeles">Pacific Time</option>
                                                <option value="Asia/Kolkata">India Standard Time</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save General Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Notifications Tab -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="action" value="update_notifications">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['settings_csrf_token'] ?>">
                                
                                <div class="mb-4">
                                    <h6 class="section-title">Email Notifications</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_new_dms" name="notifications[]" value="email_new_dms" checked>
                                        <label class="form-check-label" for="email_new_dms">
                                            New Document created
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_updated_dms" name="notifications[]" value="email_updated_dms">
                                        <label class="form-check-label" for="email_updated_dms">
                                            Document updated
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_deleted_dms" name="notifications[]" value="email_deleted_dms">
                                        <label class="form-check-label" for="email_deleted_dms">
                                            Document deleted
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="section-title">System Notifications</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="system_maintenance" name="notifications[]" value="system_maintenance" checked>
                                        <label class="form-check-label" for="system_maintenance">
                                            System maintenance alerts
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="security_alerts" name="notifications[]" value="security_alerts" checked>
                                        <label class="form-check-label" for="security_alerts">
                                            Security alerts
                                        </label>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-bell"></i> Save Notification Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- System Tab -->
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="mt-3">
                                <div class="mb-4">
                                    <h6 class="section-title">System Information</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold">Dms Management Version:</td>
                                            <td>1.0.0</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">PHP Version:</td>
                                            <td><?php echo PHP_VERSION; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Database:</td>
                                            <td>MySQL</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">KaizenAuth Integration:</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Active
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="mb-4">
                                    <h6 class="section-title">System Actions</h6>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['settings_csrf_token'] ?>">
                                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to clear the system cache?')">
                                            <i class="fas fa-trash"></i> Clear System Cache
                                        </button>
                                    </form>
                                </div>

                                <div class="mb-4">
                                    <h6 class="section-title">Database Statistics</h6>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM dms_{{TABLE_NAME}}");
                                        $stats = $stmt->fetch();
                                    } catch (Exception $e) {
                                        $stats = ['total' => 'N/A'];
                                    }
                                    ?>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold">Total documents:</td>
                                            <td><?php echo number_format($stats['total']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.section-title {
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.nav-tabs .nav-link {
    color: #495057;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    font-weight: 600;
}

.tab-content {
    padding-top: 1rem;
}
</style>

<?php include 'includes/footer.php'; ?>