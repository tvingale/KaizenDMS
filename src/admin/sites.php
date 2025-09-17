<?php
/**
 * Dms Management - Sites & Locations Management
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

$pageTitle = 'Dms Management - Sites & Locations';

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
                $stmt = $db->prepare("INSERT INTO dms_sites (site_code, site_name, address_line1, address_line2, city, state, country, postal_code, timezone, phone, email, is_main_site) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['site_code'],
                    $_POST['site_name'],
                    $_POST['address_line1'],
                    $_POST['address_line2'],
                    $_POST['city'],
                    $_POST['state'],
                    $_POST['country'],
                    $_POST['postal_code'],
                    $_POST['timezone'],
                    $_POST['phone'],
                    $_POST['email'],
                    isset($_POST['is_main_site']) ? 1 : 0
                ]);
                $message = 'Site created successfully!';
                $messageType = 'success';

            } elseif ($action === 'update') {
                $stmt = $db->prepare("UPDATE dms_sites SET site_code = ?, site_name = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, country = ?, postal_code = ?, timezone = ?, phone = ?, email = ?, is_main_site = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['site_code'],
                    $_POST['site_name'],
                    $_POST['address_line1'],
                    $_POST['address_line2'],
                    $_POST['city'],
                    $_POST['state'],
                    $_POST['country'],
                    $_POST['postal_code'],
                    $_POST['timezone'],
                    $_POST['phone'],
                    $_POST['email'],
                    isset($_POST['is_main_site']) ? 1 : 0,
                    $_POST['site_id']
                ]);
                $message = 'Site updated successfully!';
                $messageType = 'success';

            } elseif ($action === 'delete') {
                $stmt = $db->prepare("UPDATE dms_sites SET is_active = 0 WHERE id = ?");
                $stmt->execute([$_POST['site_id']]);
                $message = 'Site deactivated successfully!';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get sites
$sites = [];
try {
    $stmt = $db->query("SELECT * FROM dms_sites ORDER BY is_main_site DESC, site_name");
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = 'Error loading sites: ' . $e->getMessage();
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
                        <h1 class="admin-title">Sites & Locations Management</h1>
                        <p class="admin-subtitle">Manage multiple site locations and facilities</p>
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
        <!-- Sites List -->
        <div class="col-md-8">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-primary"></div>
                        <h5 class="admin-card-title">Sites & Locations</h5>
                    </div>
                    <div class="admin-button-group">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSiteModal">
                            Add Site
                        </button>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($sites)): ?>
                    <div class="alert alert-info">
                        No sites found. Create your first site location to get started.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Site Name</th>
                                    <th>Location</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sites as $site): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge badge-secondary"><?= htmlspecialchars($site['site_code']) ?></span>
                                            <?php if ($site['is_main_site']): ?>
                                            <span class="badge badge-warning ml-2">Main</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($site['site_name']) ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <?= htmlspecialchars($site['address_line1']) ?>
                                            <?php if ($site['address_line2']): ?>
                                            <br><small><?= htmlspecialchars($site['address_line2']) ?></small>
                                            <?php endif; ?>
                                            <br><small class="text-muted">
                                                <?= htmlspecialchars($site['city']) ?>, <?= htmlspecialchars($site['state']) ?> <?= htmlspecialchars($site['postal_code']) ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($site['phone'] || $site['email']): ?>
                                        <div>
                                            <?php if ($site['phone']): ?>
                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($site['phone']) ?><br>
                                            <?php endif; ?>
                                            <?php if ($site['email']): ?>
                                            <i class="fas fa-envelope"></i> <small><?= htmlspecialchars($site['email']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">No contact info</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $site['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $site['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <br><small class="text-muted"><?= htmlspecialchars($site['timezone']) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editSite(<?= htmlspecialchars(json_encode($site)) ?>)">
                                                Edit
                                            </button>
                                            <?php if ($site['is_active'] && !$site['is_main_site']): ?>
                                            <form method="POST" class="d-inline-flex">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="site_id" value="<?= $site['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to deactivate this site?')">
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

        <!-- Sites Statistics -->
        <div class="col-md-4">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-info"></div>
                        <h5 class="admin-card-title">Site Statistics</h5>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php
                    $activeSites = array_filter($sites, function($site) { return $site['is_active']; });
                    $inactiveSites = array_filter($sites, function($site) { return !$site['is_active']; });
                    $mainSites = array_filter($sites, function($site) { return $site['is_main_site']; });
                    $uniqueStates = array_unique(array_column($sites, 'state'));
                    ?>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-primary"></div>
                            Total Sites
                        </div>
                        <div class="stat-number"><?= count($sites) ?></div>
                    </div>

                    <hr>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-success"></div>
                            Active Sites
                        </div>
                        <div class="stat-number"><?= count($activeSites) ?></div>
                    </div>

                    <hr>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-warning"></div>
                            Main Sites
                        </div>
                        <div class="stat-number"><?= count($mainSites) ?></div>
                    </div>

                    <hr>

                    <div class="stat-content">
                        <div class="stat-label">
                            <div class="stat-indicator stat-info"></div>
                            States/Regions
                        </div>
                        <div class="stat-number"><?= count($uniqueStates) ?></div>
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-header-content">
                        <div class="admin-card-indicator admin-card-secondary"></div>
                        <h5 class="admin-card-title">Site Distribution</h5>
                    </div>
                </div>
                <div class="admin-card-body">
                    <?php foreach ($uniqueStates as $state): ?>
                    <?php $stateSites = array_filter($sites, function($s) use ($state) { return $s['state'] === $state && $s['is_active']; }); ?>
                    <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                        <span><?= htmlspecialchars($state) ?></span>
                        <span class="badge badge-light"><?= count($stateSites) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Site Modal -->
<div class="modal fade" id="addSiteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title">Add New Site</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="site_code">Site Code *</label>
                                <input type="text" class="form-control" id="site_code" name="site_code" required maxlength="10">
                                <small class="form-text text-muted">Unique identifier (e.g., B75, G44)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="site_name">Site Name *</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" required maxlength="100">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address_line1">Address Line 1 *</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                    </div>

                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2">
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" class="form-control" id="city" name="city" value="Ahmednagar" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="state">State *</label>
                                <input type="text" class="form-control" id="state" name="state" value="Maharashtra" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="postal_code">Postal Code *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="country">Country *</label>
                                <input type="text" class="form-control" id="country" name="country" value="India" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="timezone">Timezone</label>
                                <select class="form-control" id="timezone" name="timezone">
                                    <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">America/New_York (EST)</option>
                                    <option value="Europe/London">Europe/London (GMT)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_main_site" name="is_main_site">
                            <label class="form-check-label" for="is_main_site">
                                Mark as Main Site
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Site</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Site Modal -->
<div class="modal fade" id="editSiteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['dms_csrf_token'] ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="site_id" id="edit_site_id">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Site</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_site_code">Site Code *</label>
                                <input type="text" class="form-control" id="edit_site_code" name="site_code" required maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_site_name">Site Name *</label>
                                <input type="text" class="form-control" id="edit_site_name" name="site_name" required maxlength="100">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_address_line1">Address Line 1 *</label>
                        <input type="text" class="form-control" id="edit_address_line1" name="address_line1" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_address_line2">Address Line 2</label>
                        <input type="text" class="form-control" id="edit_address_line2" name="address_line2">
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_city">City *</label>
                                <input type="text" class="form-control" id="edit_city" name="city" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_state">State *</label>
                                <input type="text" class="form-control" id="edit_state" name="state" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_postal_code">Postal Code *</label>
                                <input type="text" class="form-control" id="edit_postal_code" name="postal_code" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_country">Country *</label>
                                <input type="text" class="form-control" id="edit_country" name="country" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_timezone">Timezone</label>
                                <select class="form-control" id="edit_timezone" name="timezone">
                                    <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">America/New_York (EST)</option>
                                    <option value="Europe/London">Europe/London (GMT)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_phone">Phone</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_main_site" name="is_main_site">
                            <label class="form-check-label" for="edit_is_main_site">
                                Mark as Main Site
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Site</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSite(site) {
    document.getElementById('edit_site_id').value = site.id;
    document.getElementById('edit_site_code').value = site.site_code;
    document.getElementById('edit_site_name').value = site.site_name;
    document.getElementById('edit_address_line1').value = site.address_line1;
    document.getElementById('edit_address_line2').value = site.address_line2 || '';
    document.getElementById('edit_city').value = site.city;
    document.getElementById('edit_state').value = site.state;
    document.getElementById('edit_country').value = site.country;
    document.getElementById('edit_postal_code').value = site.postal_code;
    document.getElementById('edit_timezone').value = site.timezone || 'Asia/Kolkata';
    document.getElementById('edit_phone').value = site.phone || '';
    document.getElementById('edit_email').value = site.email || '';
    document.getElementById('edit_is_main_site').checked = site.is_main_site == 1;

    $('#editSiteModal').modal('show');
}
</script>

<?php require_once '../includes/footer.php'; ?>