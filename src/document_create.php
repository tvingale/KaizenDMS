<?php
/**
 * Document create
 */

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/kaizen_sso.php';
require_once 'includes/AccessControl.php';

$sso = new KaizenSSO([
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
]);

if (!$sso->isAuthenticated()) {
    header('Location: sso.php');
    exit;
}

$user = $sso->getUserInfo();
$db = getDB();
$accessControl = AccessControl::requireAccess();

$pageTitle = 'Document create';

// Generate CSRF token
if (!isset($_SESSION['dms_csrf_token'])) {
    $_SESSION['dms_csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Kaizen Page Header -->
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">Create Document</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="document_list.php">Documents</a></li>
                <li class="breadcrumb-item active">Create</li>
            </ol>
        </nav>
    </div>
    
    <!-- Kaizen Inline Banner for Info -->
    <div style="background: var(--info); color: var(--white); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px;">
        <strong>Form Ready:</strong> This document creation form follows Kaizen design patterns. Add your specific fields and validation.
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Document create</h5>
                </div>
                <div class="card-body">
                    <!-- Add your create form/content here -->
                    <p>This is where you'll implement the create functionality for Document.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>