<?php
/**
 * RBAC Admin Dashboard - Kaizen UI Compliant
 * Administrative hub following Kaizen design system
 */

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/kaizen_sso.php';
require_once '../includes/AccessControl.php';

// Initialize RBAC system
if (file_exists(__DIR__ . '/../includes/AdditivePermissionManager.php')) {
    require_once '../includes/AdditivePermissionManager.php';
}

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

// Apply unified access control - require admin access
$accessControl = AccessControl::requireAccess('admin');

// Initialize RBAC manager if available
$permissionManager = null;
$rbacEnabled = false;
if (class_exists('AdditivePermissionManager')) {
    try {
        $permissionManager = new AdditivePermissionManager($db);
        $rbacEnabled = true;
    } catch (Exception $e) {
        error_log("Failed to initialize AdditivePermissionManager: " . $e->getMessage());
    }
}

$pageTitle = 'RBAC Administration';

// Get system statistics
$stats = [];

// User statistics
try {
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM dms_user_roles WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['active_users'] = 0;
}

// Role statistics
try {
    $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE is_system_role = 1");
    $stats['system_roles'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['system_roles'] = 0;
}

// Permission statistics
try {
    $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE is_system_permission = 1");
    $stats['system_permissions'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['system_permissions'] = 0;
}

// Assignment statistics
try {
    $stmt = $db->query("SELECT COUNT(*) FROM dms_user_roles WHERE status = 'active'");
    $stats['active_assignments'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['active_assignments'] = 0;
}

// Document statistics
try {
    $stmt = $db->query("SELECT COUNT(*) FROM dms_documents WHERE status = 'active'");
    $stats['active_documents'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['active_documents'] = 0;
}

// Category statistics
try {
    $stmt = $db->query("SELECT COUNT(*) FROM dms_categories WHERE status = 'active'");
    $stats['active_categories'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['active_categories'] = 0;
}

// Recent activity
$recentActivity = [];
try {
    $stmt = $db->query("
        SELECT entity_type, action, new_values, created_at
        FROM dms_activity_log
        WHERE entity_type IN ('rbac_role_assignment', 'document', 'category')
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recentActivity = $stmt->fetchAll();
} catch (Exception $e) {
    $recentActivity = [];
}

// System health check
$systemHealth = [];
$healthScore = 0;
$totalChecks = 0;

// Check 1: RBAC System
$totalChecks++;
if ($rbacEnabled) {
    $systemHealth['rbac'] = ['status' => 'healthy', 'message' => 'RBAC system active'];
    $healthScore++;
} else {
    $systemHealth['rbac'] = ['status' => 'warning', 'message' => 'RBAC system not fully initialized'];
}

// Check 2: Database connectivity
$totalChecks++;
try {
    $db->query("SELECT 1");
    $systemHealth['database'] = ['status' => 'healthy', 'message' => 'Database connection active'];
    $healthScore++;
} catch (Exception $e) {
    $systemHealth['database'] = ['status' => 'error', 'message' => 'Database connection issues'];
}

// Check 3: User authentication
$totalChecks++;
if ($sso->isAuthenticated()) {
    $systemHealth['auth'] = ['status' => 'healthy', 'message' => 'KaizenAuth SSO operational'];
    $healthScore++;
} else {
    $systemHealth['auth'] = ['status' => 'error', 'message' => 'Authentication system issues'];
}

// Check 4: Admin access
$totalChecks++;
try {
    if ($accessControl) {
        $systemHealth['admin_access'] = ['status' => 'healthy', 'message' => 'Admin access control working'];
        $healthScore++;
    }
} catch (Exception $e) {
    $systemHealth['admin_access'] = ['status' => 'error', 'message' => 'Admin access control issues'];
}

$healthPercentage = $totalChecks > 0 ? round(($healthScore / $totalChecks) * 100) : 0;

require_once '../includes/header.php';
?>

<!-- Kaizen UI Design System Styles -->
<style>
:root {
    --brand-primary: #C53A3A;
    --brand-primary-dark: #A72E2E;
    --neutral-100: #F6F7F8;
    --neutral-300: #E6E9EC;
    --neutral-600: #6B7280;
    --text-default: #111827;
    --white: #FFFFFF;

    --success: #16A34A;
    --warning: #F59E0B;
    --error: #DC2626;
    --info: #2563EB;
    --pending: #9CA3AF;

    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --shadow-soft: 0 6px 18px rgba(16,24,40,0.06);
}

/* Reset and base styles */
* { box-sizing: border-box; }

body {
    font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
    background: var(--neutral-100);
    color: var(--text-default);
    line-height: 1.5;
    margin: 0;
}

/* Kaizen page layout */
.kaizen-container {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 20px;
}

/* Kaizen page header */
.kaizen-page-header {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    padding: 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px;
    color: var(--text-default);
}

.page-subtitle {
    color: var(--neutral-600);
    font-size: 14px;
    margin: 0;
}

/* Health indicator */
.health-indicator {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--neutral-100);
    border-radius: var(--radius-md);
}

.health-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: var(--white);
}

.health-circle.healthy { background: var(--success); }
.health-circle.warning { background: var(--warning); color: var(--text-default); }
.health-circle.error { background: var(--error); }

/* Grid system following Kaizen guidelines */
.kaizen-grid {
    display: grid;
    gap: 16px;
}

.kaizen-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.kaizen-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.kaizen-grid.cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.kaizen-grid.cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }

@media (max-width: 900px) {
    .kaizen-grid.cols-4,
    .kaizen-grid.cols-6 { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 600px) {
    .kaizen-grid.cols-2,
    .kaizen-grid.cols-3 { grid-template-columns: 1fr; }
}

/* Kaizen cards */
.kaizen-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft);
    padding: 20px;
}

/* KPI cards */
.kpi-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.kpi-number {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-default);
}

.kpi-label {
    color: var(--neutral-600);
    font-size: 14px;
    margin-bottom: 4px;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--white);
}

/* Status badges following Kaizen design */
.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: var(--white);
}

.badge.success { background: var(--success); }
.badge.warning { background: var(--warning); color: var(--text-default); }
.badge.error { background: var(--error); }
.badge.pending { background: var(--pending); }
.badge.info { background: var(--info); }

/* Kaizen buttons */
.btn {
    border: 0;
    cursor: pointer;
    border-radius: var(--radius-lg);
    padding: 10px 16px;
    font-weight: 600;
    box-shadow: var(--shadow-soft);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn:focus {
    outline: 3px solid rgba(197, 58, 58, 0.25);
}

.btn-primary {
    background: var(--brand-primary);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--brand-primary-dark);
}

.btn-secondary {
    background: var(--white);
    color: var(--text-default);
    border: 1px solid var(--neutral-300);
}

.btn-secondary:hover {
    background: var(--neutral-100);
}

/* Navigation cards */
.nav-card {
    border-left: 4px solid transparent;
    transition: all 0.2s ease;
}

.nav-card:hover {
    transform: translateY(-2px);
    border-left-color: var(--brand-primary);
}

.nav-card.primary {
    border-left-color: var(--brand-primary);
}

.nav-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Activity timeline */
.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--neutral-300);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--neutral-100);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.activity-content {
    flex-grow: 1;
}

.activity-description {
    font-size: 14px;
    color: var(--text-default);
    margin-bottom: 4px;
}

.activity-time {
    font-size: 13px;
    color: var(--neutral-600);
}

/* Health status items */
.health-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid var(--neutral-300);
}

.health-item:last-child {
    border-bottom: none;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-dot.healthy { background: var(--success); }
.status-dot.warning { background: var(--warning); }
.status-dot.error { background: var(--error); }

.health-component {
    font-weight: 600;
    color: var(--text-default);
}

.health-message {
    font-size: 13px;
    color: var(--neutral-600);
}

/* Quick actions */
.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    padding: 24px;
    background: var(--neutral-100);
    border-radius: var(--radius-lg);
}

@media (max-width: 768px) {
    .kaizen-container {
        padding: 0 16px;
        margin: 16px auto;
    }

    .kaizen-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .quick-actions {
        flex-direction: column;
        align-items: center;
    }

    .quick-actions .btn {
        width: 100%;
        max-width: 240px;
        justify-content: center;
    }
}
</style>

<div class="kaizen-container">

    <!-- Kaizen Page Header -->
    <div class="kaizen-page-header">
        <div>
            <h1 class="page-title">RBAC Administration</h1>
            <p class="page-subtitle">Role-based access control management dashboard</p>
        </div>
        <div class="health-indicator">
            <div class="health-circle <?= $healthPercentage >= 80 ? 'healthy' : ($healthPercentage >= 60 ? 'warning' : 'error') ?>">
                <?= $healthPercentage ?>%
            </div>
            <div>
                <div style="font-weight: 600; font-size: 14px;">System Health</div>
                <div class="page-subtitle"><?= $healthScore ?>/<?= $totalChecks ?> checks passed</div>
            </div>
        </div>
    </div>

    <!-- KPI Overview -->
    <div class="kaizen-grid cols-6" style="margin-bottom: 24px;">
        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Active Users</div>
                    <div class="kpi-number"><?= $stats['active_users'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--brand-primary);">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">System Roles</div>
                    <div class="kpi-number"><?= $stats['system_roles'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--info);">
                    <i class="fas fa-id-badge"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Permissions</div>
                    <div class="kpi-number"><?= $stats['system_permissions'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--success);">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Role Assignments</div>
                    <div class="kpi-number"><?= $stats['active_assignments'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--warning);">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Documents</div>
                    <div class="kpi-number"><?= $stats['active_documents'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--pending);">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>

        <div class="kaizen-card">
            <div class="kpi-card">
                <div>
                    <div class="kpi-label">Categories</div>
                    <div class="kpi-number"><?= $stats['active_categories'] ?></div>
                </div>
                <div class="kpi-icon" style="background: var(--neutral-600);">
                    <i class="fas fa-tags"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Management Cards -->
    <div class="kaizen-grid cols-4" style="margin-bottom: 24px;">

        <!-- RBAC Management -->
        <div class="kaizen-card nav-card primary">
            <h3 style="margin: 0 0 8px; font-size: 18px; color: var(--text-default);">
                <i class="fas fa-shield-alt" style="color: var(--brand-primary);"></i> RBAC Management
            </h3>
            <p style="color: var(--neutral-600); font-size: 14px; margin: 0 0 16px;">
                Comprehensive role-based access control system
            </p>

            <div class="nav-actions">
                <a href="roles_permissions.php" class="btn btn-primary">
                    <i class="fas fa-users-cog"></i> Roles & Permissions
                </a>
                <a href="../module_users.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> User Role Assignment
                </a>
                <?php if ($rbacEnabled): ?>
                <a href="../tools/rbac_integration_test_fixed.php" class="btn btn-secondary">
                    <i class="fas fa-vial"></i> System Test
                </a>
                <?php endif; ?>
            </div>

            <div style="margin-top: 16px;">
                <?php if ($rbacEnabled): ?>
                <span class="badge success">
                    <i class="fas fa-check-circle"></i> RBAC Active
                </span>
                <?php else: ?>
                <span class="badge warning">
                    <i class="fas fa-exclamation-triangle"></i> Legacy Mode
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Management -->
        <div class="kaizen-card nav-card">
            <h3 style="margin: 0 0 8px; font-size: 18px; color: var(--text-default);">
                <i class="fas fa-folder-open" style="color: var(--info);"></i> Document Management
            </h3>
            <p style="color: var(--neutral-600); font-size: 14px; margin: 0 0 16px;">
                Manage documents with scope-based access control
            </p>

            <div class="nav-actions">
                <a href="../document_list.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> All Documents
                </a>
                <a href="../document_create.php" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Create Document
                </a>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
            </div>

            <div style="margin-top: 16px; font-size: 13px; color: var(--neutral-600);">
                <i class="fas fa-chart-line"></i>
                <?= $stats['active_documents'] ?> active documents in <?= $stats['active_categories'] ?> categories
            </div>
        </div>

        <!-- Master Data Management -->
        <div class="kaizen-card nav-card">
            <h3 style="margin: 0 0 8px; font-size: 18px; color: var(--text-default);">
                <i class="fas fa-database" style="color: var(--success);"></i> Master Data Management
            </h3>
            <p style="color: var(--neutral-600); font-size: 14px; margin: 0 0 16px;">
                Manage organizational structure and reference data
            </p>

            <div class="nav-actions">
                <a href="departments.php" class="btn btn-primary">
                    <i class="fas fa-building"></i> Departments
                </a>
                <a href="sites.php" class="btn btn-secondary">
                    <i class="fas fa-map-marker-alt"></i> Sites & Locations
                </a>
                <a href="document_types.php" class="btn btn-secondary">
                    <i class="fas fa-file-alt"></i> Document Types
                </a>
            </div>

            <div style="margin-top: 16px; font-size: 13px; color: var(--neutral-600);">
                <i class="fas fa-chart-bar"></i>
                Organizational structure and reference data
            </div>
        </div>

        <!-- System Configuration -->
        <div class="kaizen-card nav-card">
            <h3 style="margin: 0 0 8px; font-size: 18px; color: var(--text-default);">
                <i class="fas fa-cogs" style="color: var(--neutral-600);"></i> System Configuration
            </h3>
            <p style="color: var(--neutral-600); font-size: 14px; margin: 0 0 16px;">
                System settings and configuration management
            </p>

            <div class="nav-actions">
                <a href="settings.php" class="btn btn-primary">
                    <i class="fas fa-cog"></i> System Settings
                </a>
                <a href="export_options.php" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export Options
                </a>
                <a href="../api/" class="btn btn-secondary" target="_blank">
                    <i class="fas fa-code"></i> API Documentation
                </a>
            </div>

            <div style="margin-top: 16px; font-size: 13px; color: var(--neutral-600);">
                <i class="fas fa-server"></i>
                KaizenAuth SSO Integration Active
            </div>
        </div>

    </div>

    <!-- System Status and Activity -->
    <div class="kaizen-grid cols-2">

        <!-- System Health Status -->
        <div class="kaizen-card">
            <h3 style="margin: 0 0 16px; font-size: 18px; color: var(--text-default);">
                <i class="fas fa-heartbeat" style="color: var(--info);"></i> System Health Status
            </h3>

            <div style="margin-bottom: 20px; text-align: center;">
                <div class="health-circle <?= $healthPercentage >= 80 ? 'healthy' : ($healthPercentage >= 60 ? 'warning' : 'error') ?>"
                     style="width: 80px; height: 80px; margin: 0 auto 12px; font-size: 18px;">
                    <?= $healthPercentage ?>%
                </div>
                <div style="font-weight: 600;">Overall System Health</div>
                <div class="page-subtitle"><?= $healthScore ?> of <?= $totalChecks ?> checks passed</div>
            </div>

            <div>
                <?php foreach ($systemHealth as $component => $health): ?>
                <div class="health-item">
                    <div class="status-dot <?= $health['status'] ?>"></div>
                    <div style="flex-grow: 1;">
                        <div class="health-component"><?= ucfirst(str_replace('_', ' ', $component)) ?></div>
                        <div class="health-message"><?= $health['message'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="kaizen-card">
            <h3 style="margin: 0 0 16px; font-size: 18px; color: var(--text-default);">
                <i class="fas fa-clock" style="color: var(--warning);"></i> Recent Activity
            </h3>

            <?php if (empty($recentActivity)): ?>
            <div style="text-align: center; color: var(--neutral-600); padding: 40px 20px;">
                <i class="fas fa-clock" style="font-size: 48px; margin-bottom: 12px; opacity: 0.3;"></i>
                <p>No recent activity recorded</p>
            </div>
            <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($recentActivity as $activity):
                    $data = json_decode($activity['new_values'], true);
                    $iconClass = match($activity['entity_type']) {
                        'rbac_role_assignment' => 'fas fa-user-check',
                        'document' => 'fas fa-file-alt',
                        'category' => 'fas fa-tag',
                        default => 'fas fa-info-circle'
                    };
                    $iconColor = match($activity['action']) {
                        'role_assigned' => 'var(--success)',
                        'role_revoked' => 'var(--warning)',
                        'created' => 'var(--info)',
                        'updated' => 'var(--brand-primary)',
                        'deleted' => 'var(--error)',
                        default => 'var(--neutral-600)'
                    };
                ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="<?= $iconClass ?>" style="color: <?= $iconColor ?>; font-size: 14px;"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-description">
                            <strong><?= ucfirst(str_replace('_', ' ', $activity['action'])) ?></strong>
                            <?= ucfirst(str_replace('_', ' ', $activity['entity_type'])) ?>
                            <?php if (isset($data['username'])): ?>
                                for <?= htmlspecialchars($data['username']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="activity-time"><?= date('M j, Y H:i', strtotime($activity['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Quick Actions Footer -->
    <div class="quick-actions" style="margin-top: 24px;">
        <a href="roles_permissions.php" class="btn btn-primary">
            <i class="fas fa-shield-alt"></i> Manage RBAC
        </a>
        <a href="../module_users.php" class="btn btn-secondary">
            <i class="fas fa-users"></i> User Management
        </a>
        <a href="../document_create.php" class="btn btn-secondary">
            <i class="fas fa-plus"></i> New Document
        </a>
        <a href="settings.php" class="btn btn-secondary">
            <i class="fas fa-cog"></i> Settings
        </a>
        <?php if ($rbacEnabled): ?>
        <a href="../tools/rbac_integration_test_fixed.php" class="btn btn-secondary">
            <i class="fas fa-vial"></i> System Test
        </a>
        <?php endif; ?>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>