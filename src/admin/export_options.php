<?php
/**
 * Dms Management - Export/Import Options
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

$pageTitle = 'Dms Management - Export/Import';

// Generate CSRF token
if (!isset($_SESSION['dms_csrf_token'])) {
    $_SESSION['dms_csrf_token'] = bin2hex(random_bytes(32));
}

// Get statistics for export
$stats = [];
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM dms_documents");
    $stats['total_entities'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM dms_categories WHERE is_active = 1");
    $stats['total_categories'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM dms_user_roles WHERE status = 'active'");
    $stats['total_users'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Error getting stats: " . $e->getMessage());
    $stats = ['total_entities' => 0, 'total_categories' => 0, 'total_users' => 0];
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
                        <h1 class="admin-title">Export/Import Data</h1>
                        <p class="admin-subtitle">Backup and restore Dms Management data</p>
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

    <div class="row">
        <!-- Export Section -->
        <div class="col-md-6">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-success"></div>
                        <h5 class="admin-card-title">Export Data</h5>
                    </div>
                </div>
                <div class="admin-card-body">
                    <p>Export your Dms Management data for backup or migration purposes.</p>
                    
                    <!-- Export Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stat-content text-center">
                                <div class="stat-number" style="font-size: 2rem; color: #007bff;"><?= $stats['total_entities'] ?></div>
                                <div class="stat-label">documents</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-content text-center">
                                <div class="stat-number" style="font-size: 2rem; color: #28a745;"><?= $stats['total_categories'] ?></div>
                                <div class="stat-label">Categories</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-content text-center">
                                <div class="stat-number" style="font-size: 2rem; color: #17a2b8;"><?= $stats['total_users'] ?></div>
                                <div class="stat-label">Users</div>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="export.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                        
                        <div class="form-group">
                            <label><strong>Export Format:</strong></label>
                            <div class="form-check">
                                <input type="radio" id="format_csv" name="format" value="csv" class="form-check-input" checked>
                                <label class="form-check-label" for="format_csv">CSV (Comma Separated Values)</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="format_json" name="format" value="json" class="form-check-input">
                                <label class="form-check-label" for="format_json">JSON (JavaScript Object Notation)</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="format_excel" name="format" value="excel" class="form-check-input">
                                <label class="form-check-label" for="format_excel">Excel (.xlsx)</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>Data to Export:</strong></label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="export_entities" name="export_data[]" value="entities" checked>
                                <label class="form-check-label" for="export_entities">documents (<?= $stats['total_entities'] ?> records)</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="export_categories" name="export_data[]" value="categories" checked>
                                <label class="form-check-label" for="export_categories">Categories (<?= $stats['total_categories'] ?> records)</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="export_users" name="export_data[]" value="users">
                                <label class="form-check-label" for="export_users">User Access Records (<?= $stats['total_users'] ?> records)</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="export_activity" name="export_data[]" value="activity">
                                <label class="form-check-label" for="export_activity">Activity Log (for audit purposes)</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </form>
                    
                    <div class="alert alert-info mt-3">
                        <small><strong>Note:</strong> This is a placeholder implementation. Add your specific export logic based on your data requirements.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Section -->
        <div class="col-md-6">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-warning"></div>
                        <h5 class="admin-card-title">Import Data</h5>
                    </div>
                </div>
                <div class="admin-card-body">
                    <p>Import data from CSV, JSON, or Excel files into Dms Management.</p>
                    
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Always backup your data before importing</li>
                            <li>Duplicate entries will be handled according to your settings</li>
                            <li>Large files may take time to process</li>
                            <li>Invalid data will be logged and skipped</li>
                        </ul>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" action="import.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                        
                        <div class="form-group">
                            <label for="import_file"><strong>Select File:</strong></label>
                            <input type="file" class="form-control-file" id="import_file" name="import_file" 
                                   accept=".csv,.json,.xlsx,.xls" required>
                            <small class="form-text text-muted">
                                Supported formats: CSV, JSON, Excel (.xlsx, .xls)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>Data Type:</strong></label>
                            <select name="import_type" class="form-control" required>
                                <option value="">Select data type...</option>
                                <option value="entities">documents</option>
                                <option value="categories">Categories</option>
                                <option value="users">User Access Records</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>Import Options:</strong></label>
                            <div class="form-check">
                                <input type="radio" id="mode_add" name="import_mode" value="add" class="form-check-input" checked>
                                <label class="form-check-label" for="mode_add">Add new records only (skip duplicates)</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="mode_update" name="import_mode" value="update" class="form-check-input">
                                <label class="form-check-label" for="mode_update">Update existing records</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="mode_replace" name="import_mode" value="replace" class="form-check-input">
                                <label class="form-check-label" for="mode_replace">Replace all data (dangerous!)</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-upload"></i> Import Data
                        </button>
                    </form>
                    
                    <div class="alert alert-info mt-3">
                        <small><strong>Note:</strong> This is a placeholder implementation. Add your specific import logic based on your data requirements.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Export/Import History -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-info"></div>
                        <h5 class="admin-card-title">Recent Activity</h5>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="alert alert-info">
                        <strong>Implementation Note:</strong> This section would show recent export/import activities from the activity log.
                        Query the dms_activity_log table for 'export' and 'import' actions to display recent operations.
                    </div>
                    
                    <!-- Placeholder for activity log -->
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Records</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        No recent export/import activities found.
                                        <br><small>Activities will appear here once you start using the export/import features.</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>