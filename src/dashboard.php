<?php
/**
 * Dms Dashboard
 */

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/kaizen_sso.php';
require_once 'includes/AccessControl.php';

// Initialize SSO and check authentication
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
$accessControl = AccessControl::requireAccess();

$pageTitle = 'Dms Dashboard';

// Get statistics
$stats = [];
try {
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_documents,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_documents,
            COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_documents,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_documents
        FROM dms_documents
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching stats: ' . $e->getMessage());
    $stats = ['total_documents' => 0, 'active_documents' => 0, 'inactive_documents' => 0, 'recent_documents' => 0];
}

// Generate CSRF token
if (!isset($_SESSION['dms_csrf_token'])) {
    $_SESSION['dms_csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Header - Kaizen Typography -->
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">Document Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
    
    <!-- KPI Cards - Kaizen Design Pattern -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="color: var(--neutral-600); font-size: 13px;">Total Documents</div>
                <div style="font-size: 28px; font-weight: 700; color: var(--text-default);"><?= $stats['total_documents'] ?></div>
            </div>
            <div style="width: 40px; height: 40px; background: var(--neutral-300); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-alt" style="color: var(--neutral-600);"></i>
            </div>
        </div>
        
        <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="color: var(--neutral-600); font-size: 13px;">Active Documents</div>
                <div style="font-size: 28px; font-weight: 700; color: var(--text-default);"><?= $stats['active_documents'] ?></div>
            </div>
            <span class="badge bg-success" style="padding: 6px 12px;">Active</span>
        </div>
        
        <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="color: var(--neutral-600); font-size: 13px;">Inactive Documents</div>
                <div style="font-size: 28px; font-weight: 700; color: var(--text-default);"><?= $stats['inactive_documents'] ?></div>
            </div>
            <span class="badge bg-pending" style="padding: 6px 12px;">Inactive</span>
        </div>
        
        <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="color: var(--neutral-600); font-size: 13px;">Recent (7 days)</div>
                <div style="font-size: 28px; font-weight: 700; color: var(--text-default);"><?= $stats['recent_documents'] ?></div>
            </div>
            <span class="badge bg-info" style="padding: 6px 12px;">New</span>
        </div>
    </div>
    
    <!-- Quick Actions - Kaizen Button Hierarchy -->
    <div class="card">
        <div class="card-header">
            <h3 style="font-size: 18px; font-weight: 600; margin: 0;">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                <!-- Only ONE primary button per page - Kaizen rule -->
                <a href="document_create.php" class="btn btn-primary btn-icon">
                    <i class="fas fa-plus"></i> Create New Document
                </a>
                
                <!-- Secondary and tertiary buttons -->
                <a href="document_list.php" class="btn btn-secondary btn-icon">
                    <i class="fas fa-list"></i> View All Documents
                </a>
                
                <?php if ($accessControl->hasRole('admin')): ?>
                <a href="admin/index.php" class="btn btn-secondary btn-icon">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
                <?php endif; ?>
                
                <a href="module_users.php" class="btn btn-tertiary btn-icon">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>