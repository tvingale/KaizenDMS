<?php
/**
 * Dms Management - Admin Management Dashboard
 * Central hub for all administrative functions
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

// Apply unified access control - require admin access
$db = getDB();
$accessControl = AccessControl::requireAccess('admin');

$pageTitle = 'Dms Management Admin Panel';

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1>Dms Management Administration</h1>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">User Management</div>
                <div class="card-body">
                    <p>Manage user access and roles</p>
                    <a href="../module_users.php" class="btn btn-primary">Manage Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Categories</div>
                <div class="card-body">
                    <p>Manage categories</p>
                    <a href="categories.php" class="btn btn-outline-primary">Manage Categories</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Settings</div>
                <div class="card-body">
                    <p>System settings</p>
                    <a href="settings.php" class="btn btn-outline-primary">Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>