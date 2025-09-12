<?php
/**
 * Dms Management - User Profile Page
 * Display user information and document statistics from JWT token
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

$pageTitle = 'My Profile';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> My Profile
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="profile-section">
                                <h6 class="section-title">Basic Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="profile-label"><strong>User ID:</strong></td>
                                        <td><?php echo htmlspecialchars($user['user_id'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="profile-label"><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="profile-label"><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="profile-label"><strong>Role:</strong></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($user['role'] ?? 'User'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="profile-section">
                                <h6 class="section-title">System Access</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="profile-label"><strong>Dms Management Access:</strong></td>
                                        <td>
                                            <?php if ($accessControl->hasModuleAccess()): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Authorized
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-times"></i> Limited Access
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="profile-label"><strong>Last Login:</strong></td>
                                        <td><?php echo date('M j, Y g:i A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="profile-label"><strong>Session Status:</strong></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-circle"></i> Active
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="profile-section">
                                <h6 class="section-title">Module Permissions</h6>
                                <div class="row">
                                    <?php
                                    $permissions = [
                                        'view_dms' => 'View documents',
                                        'create_dms' => 'Create documents',
                                        'edit_dms' => 'Edit documents',
                                        'delete_dms' => 'Delete documents',
                                        'manage_dms' => 'Manage Dms Management'
                                    ];
                                    
                                    foreach ($permissions as $permission => $label):
                                        $hasPermission = $accessControl->hasPermission($permission);
                                    ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="permission-item">
                                            <?php if ($hasPermission): ?>
                                                <i class="fas fa-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle text-muted"></i>
                                            <?php endif; ?>
                                            <span class="<?php echo $hasPermission ? 'text-success' : 'text-muted'; ?>">
                                                <?php echo htmlspecialchars($label); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <a href="<?php echo KAIZENAUTH_BASE_URL; ?>/profile" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Edit Profile (KaizenAuth)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-section {
    margin-bottom: 2rem;
}

.section-title {
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.profile-label {
    width: 120px;
    color: #6c757d;
}

.permission-item {
    padding: 0.25rem 0;
}

.permission-item i {
    width: 20px;
    margin-right: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?>