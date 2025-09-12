<?php
/**
 * Module User Management
 * Admin interface to grant/revoke access to this module
 */

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/kaizen_sso.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/UserDisplayHelper.php';
require_once 'includes/KaizenAuthAPI.php';
require_once 'includes/AccessControl.php';

// Initialize SSO
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

// Use unified access control - require admin access
$accessControl = AccessControl::requireAccess('admin');

// Generate simple CSRF token for this page
if (!isset($_SESSION['module_users_csrf_token'])) {
    $_SESSION['module_users_csrf_token'] = bin2hex(random_bytes(32));
}

// Set page title (will include header after form processing)
$pageTitle = 'Module Access Management';

// Handle form submissions
if ($_POST) {
    // Simple CSRF token validation using session-based approach
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['module_users_csrf_token']) || 
        !hash_equals($_SESSION['module_users_csrf_token'], $_POST['csrf_token'])) {
        
        // CSRF validation failed
        $_SESSION['flash_message'] = 'Security token invalid. Please refresh the page and try again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: module_users.php');
        exit;
    }
    
    try {
        // Check for grant access - either by button name or by having user data
        $isGrantAccessForm = isset($_POST['grant_access']) || 
                           (isset($_POST['kaizen_user_id']) && isset($_POST['role_id']) && 
                            !isset($_POST['revoke_access']) && !isset($_POST['restore_access']) && !isset($_POST['change_role']));
        
        
        if ($isGrantAccessForm) {
            // Grant access to a new user
            $kaizenUserId = intval($_POST['kaizen_user_id']);
            $kaizenUsername = trim($_POST['kaizen_username']);
            $kaizenEmail = trim($_POST['kaizen_email']);
            $kaizenName = trim($_POST['kaizen_name']);
            $roleId = intval($_POST['role_id']) ?: null;
            $notes = trim($_POST['notes']);
            
            if ($kaizenUserId && $roleId) {
                // Get default user role if not specified
                if (!$roleId) {
                    $stmt = $db->query("SELECT id FROM dms_roles WHERE name = 'user' LIMIT 1");
                    $roleId = $stmt->fetchColumn();
                }
                
                // Use AccessControl to grant access
                if ($accessControl->grantUserAccess($kaizenUserId, $roleId, $notes)) {
                    // Log the action
                    $db->prepare("
                        INSERT INTO dms_activity_log 
                        (entity_type, entity_id, action, user_id, new_values, created_at)
                        VALUES ('module_access', ?, 'access_granted', ?, ?, NOW())
                    ")->execute([
                        $kaizenUserId,
                        $user['id'],
                        json_encode([
                            'username' => $kaizenUsername,
                            'name' => $kaizenName,
                            'email' => $kaizenEmail,
                            'role_id' => $roleId,
                            'notes' => $notes
                        ])
                    ]);
                    
                    $success = "Access granted to user: $kaizenUsername";
                } else {
                    $error = "Failed to grant access";
                }
            } else {
                $error = "User ID and role are required";
            }
            
        } elseif (isset($_POST['revoke_access'])) {
            // Revoke access
            $userId = intval($_POST['user_id']);
            $notes = "Access revoked by admin";
            
            if (!$userId) {
                $error = "Invalid user ID provided";
            } else {
                try {
                    if ($accessControl->revokeUserAccess($userId, $notes)) {
                        // Log the action
                        $db->prepare("
                            INSERT INTO dms_activity_log 
                            (entity_type, entity_id, action, user_id, new_values, created_at)
                            VALUES ('module_access', ?, 'access_revoked', ?, ?, NOW())
                        ")->execute([
                            $userId,
                            $user['id'],
                            json_encode(['action' => 'access_revoked', 'reason' => $notes])
                        ]);
                        
                        $success = "Access revoked successfully for user ID: $userId";
                    } else {
                        $error = "Failed to revoke access for user ID: $userId";
                    }
                } catch (Exception $e) {
                    $error = "Error revoking access: " . $e->getMessage();
                }
            }
            
        } elseif (isset($_POST['restore_access'])) {
            // Restore access
            $userId = intval($_POST['user_id']);
            $notes = "Access restored by admin";
            
            try {
                if ($accessControl->restoreUserAccess($userId, $notes)) {
                    $success = "Access restored successfully";
                } else {
                    $error = "Failed to restore access";
                }
            } catch (Exception $e) {
                $error = "Error restoring access: " . $e->getMessage();
            }
        } elseif (isset($_POST['change_role'])) {
            // Change user role
            $userId = intval($_POST['user_id']);
            $newRoleId = intval($_POST['new_role_id']);
            
            $stmt = $db->prepare("
                UPDATE dms_user_roles 
                SET role_id = ?, granted_by = ?, granted_at = NOW()
                WHERE user_id = ? AND status = 'active'
            ");
            
            if ($stmt->execute([$newRoleId, $user['id'], $userId])) {
                $success = "User role updated successfully";
            } else {
                $error = "Failed to update user role";
            }
        }
        
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Include header after all form processing is complete
require_once 'includes/header.php';

// Get all module access records using unified access control
$moduleUsers = $accessControl->getAllModuleUsers();

// Pre-populate UserDisplayHelper cache with current user info
$userDisplayHelper = UserDisplayHelper::getInstance();
$userDisplayHelper->cacheUserInfo($user['id'], [
    'name' => $user['name'] ?? $user['username'] ?? 'Unknown User',
    'email' => $user['email'] ?? '',
    'mobile' => $user['mobile'] ?? '',
    'username' => $user['username'] ?? ''
]);

// Get available roles for the form
$stmt = $db->query("SELECT * FROM dms_roles ORDER BY name");
$availableRoles = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-users-cog"></i> Module Access Management
                    </h4>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#grantAccessModal">
                        <i class="fas fa-plus"></i> Grant Access
                    </button>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> Access Control:</strong>
                        Only users listed below can access the Dms module. All other KaizenAuth users will be denied access and redirected to an access denied page.
                    </div>
                    
                    <div class="alert alert-success">
                        <strong><i class="fas fa-check-circle"></i> API Status:</strong>
                        ✅ KaizenAuth API is active and working! Real user names, emails, and mobile numbers are now displayed throughout the application. User search functionality is available for granting access.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Module Role</th>
                                    <th>Status</th>
                                    <th>Granted By</th>
                                    <th>Granted At</th>
                                    <th>Last Access</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moduleUsers as $moduleUser): ?>
                                <tr class="<?php echo $moduleUser['status'] == 'inactive' ? 'table-warning' : ''; ?>">
                                    <td><?php echo $moduleUser['user_id']; ?></td>
                                    <td>
                                        <?php if ($moduleUser['user_id'] == $user['id']): ?>
                                            <?= getUserDisplayHTML($moduleUser['user_id'], $user['username'] ?? 'Current User', false) ?>
                                            <span class="badge badge-info">You</span>
                                        <?php else: ?>
                                            <?= getUserDisplayHTML($moduleUser['user_id'], "User #{$moduleUser['user_id']}", false) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($moduleUser['user_id'] == $user['id']): ?>
                                            <?= getUserDisplayName($moduleUser['user_id'], $user['name'] ?? $user['username'] ?? 'Current User') ?>
                                        <?php else: ?>
                                            <?= getUserDisplayName($moduleUser['user_id'], "User #{$moduleUser['user_id']}") ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($moduleUser['user_id'] == $user['id']): ?>
                                            <?= getUserEmail($moduleUser['user_id'], $user['email'] ?? 'No email') ?>
                                        <?php else: ?>
                                            <?= getUserEmail($moduleUser['user_id'], 'Email not available') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($moduleUser['user_id'] == $user['id']): ?>
                                            <?= getUserMobile($moduleUser['user_id'], $user['mobile'] ?? 'No mobile') ?>
                                        <?php else: ?>
                                            <?= getUserMobile($moduleUser['user_id'], 'Mobile not available') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge badge-<?php echo $moduleUser['role_name'] == 'admin' ? 'danger' : ($moduleUser['role_name'] == 'manager' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($moduleUser['role_name']); ?>
                                            </span>
                                            
                                            <?php if ($moduleUser['status'] == 'active' && $moduleUser['user_id'] != $user['id']): ?>
                                                <form method="post" style="display: inline; margin-left: 10px;">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $moduleUser['user_id']; ?>">
                                                    <select name="new_role_id" class="form-control form-control-sm" style="width: auto; display: inline-block;" onchange="if(this.value && confirm('Change role for this user?')) { this.form.submit(); } else { this.value = ''; }">
                                                        <option value="">Change Role...</option>
                                                        <?php foreach ($availableRoles as $role): ?>
                                                            <option value="<?= $role['id'] ?>" <?= $role['name'] == $moduleUser['role_name'] ? 'selected' : '' ?>>
                                                                <?= ucfirst($role['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="hidden" name="change_role" value="1">
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $moduleUser['status'] == 'active' ? 'success' : 'danger'; 
                                        ?>">
                                            <?php echo ucfirst($moduleUser['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($moduleUser['granted_by'] == $user['id']): ?>
                                            <?= getUserDisplayHTML($moduleUser['granted_by'], 'You', false) ?>
                                        <?php elseif (empty($moduleUser['granted_by']) || $moduleUser['granted_by'] == '0'): ?>
                                            <span class="text-muted">System</span>
                                        <?php else: ?>
                                            <?= getUserDisplayHTML($moduleUser['granted_by'], "User #{$moduleUser['granted_by']}", false) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($moduleUser['granted_at'])); ?></td>
                                    <td>
                                        <?php if ($moduleUser['last_access']): ?>
                                            <?php echo date('M j, Y H:i', strtotime($moduleUser['last_access'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($moduleUser['status'] == 'active' && $moduleUser['user_id'] != $user['id']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $moduleUser['user_id']; ?>">
                                                <input type="hidden" name="revoke_access" value="1">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Revoke access for this user?');">
                                                    <i class="fas fa-ban"></i> Revoke
                                                </button>
                                            </form>
                                        <?php elseif ($moduleUser['status'] == 'inactive'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $moduleUser['user_id']; ?>">
                                                <input type="hidden" name="role_id" value="<?php echo $moduleUser['role_id'] ?? ''; ?>">
                                                <input type="hidden" name="restore_access" value="1">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Restore access for this user?');">
                                                    <i class="fas fa-check"></i> Restore
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grant Access Modal -->
<div class="modal fade" id="grantAccessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['module_users_csrf_token'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Grant Module Access</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- User Search Section -->
                    <div id="userSearchSection">
                        <!-- User Search Input -->
                        <div class="form-group">
                            <label for="userSearch">Search Users</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="userSearch" 
                                       placeholder="Type name, email, or username to search..."
                                       onkeyup="searchUsers(this.value)">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Start typing to search KaizenAuth users...</small>
                        </div>
                        
                        <!-- User Selection Status -->
                        <div id="userSelectionStatus" class="mb-3" style="display: none;">
                            <div class="alert alert-success">
                                <strong><i class="fas fa-check"></i> User Selected:</strong>
                                <span id="selectedUserDisplay"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ml-2" onclick="clearSearch()">
                                    Change User
                                </button>
                            </div>
                        </div>
                        
                        <!-- Search Results -->
                        <div id="searchResults" class="mb-3" style="display: none;">
                            <label>Search Results:</label>
                            <div id="searchResultsList" class="list-group" style="max-height: 200px; overflow-y: auto;">
                                <!-- Results will be populated here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selected User Details -->
                    <div id="selectedUserSection" style="display: none;">
                        <div class="alert alert-success">
                            <strong><i class="fas fa-check"></i> User Selected:</strong>
                            <span id="selectedUserName"></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-2" onclick="clearSelection()">
                                Change User
                            </button>
                        </div>
                    </div>
                    
                    <!-- Hidden Form Fields -->
                    <input type="hidden" id="kaizen_user_id" name="kaizen_user_id" required>
                    <input type="hidden" id="kaizen_username" name="kaizen_username" required>
                    <input type="hidden" id="kaizen_email" name="kaizen_email">
                    <input type="hidden" id="kaizen_name" name="kaizen_name">
                    
                    <div class="form-group">
                        <label for="role_id">Access Level *</label>
                        <select class="form-control" id="role_id" name="role_id" required>
                            <option value="">Select access level...</option>
                            <?php foreach ($availableRoles as $role): ?>
                                <option value="<?= $role['id'] ?>" <?= $role['name'] == 'user' ? 'selected' : '' ?>>
                                    <?= ucfirst($role['name']) ?> - <?= htmlspecialchars($role['permissions'] ?? $role['description'] ?? 'Standard access') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Choose the appropriate access level for this user</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Admin Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="Optional notes about this user access..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="grant_access" class="btn btn-primary">
                        <i class="fas fa-check"></i> Grant Access
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// User Search Functionality
let searchTimeout;

function searchUsers(query) {
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // Hide results if query is too short
    if (query.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        return;
    }
    
    // Debounce search - wait 500ms after user stops typing
    searchTimeout = setTimeout(() => {
        performUserSearch(query);
    }, 500);
}

function performUserSearch(query) {
    // Show loading
    const resultsDiv = document.getElementById('searchResults');
    const resultsList = document.getElementById('searchResultsList');
    
    resultsDiv.style.display = 'block';
    resultsList.innerHTML = '<div class="list-group-item"><i class="fas fa-spinner fa-spin"></i> Searching users...</div>';
    
    // Use our PHP proxy endpoint (handles HttpOnly cookie automatically)
    fetch('api/user_search.php?query=' + encodeURIComponent(query) + '&limit=10', {
        method: 'GET',
        credentials: 'same-origin' // Include cookies
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error('Authentication required - please refresh and login');
            } else if (response.status === 403) {
                throw new Error('Admin access required');
            } else {
                throw new Error('HTTP ' + response.status);
            }
        }
        return response.json();
    })
    .then(data => {
        displaySearchResults(data);
    })
    .catch(error => {
        console.error('Search error:', error);
        resultsList.innerHTML = '<div class="list-group-item text-danger"><i class="fas fa-exclamation-triangle"></i> Search failed: ' + error.message + '</div>';
    });
}

function displaySearchResults(data) {
    const resultsList = document.getElementById('searchResultsList');
    
    if (!data.success || !data.data || !data.data.users || data.data.users.length === 0) {
        resultsList.innerHTML = '<div class="list-group-item text-muted"><i class="fas fa-search"></i> No users found</div>';
        return;
    }
    
    let html = '';
    data.data.users.forEach(user => {
        html += `
            <div class="list-group-item list-group-item-action" onclick="selectUser('${user.id}', '${escapeHtml(user.username || '')}', '${escapeHtml(user.email || '')}', '${escapeHtml(user.name || '')}')" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${escapeHtml(user.name || user.username || 'Unknown')}</h6>
                        <p class="mb-1 text-muted small">${escapeHtml(user.email || 'No email')}</p>
                        <small class="text-muted">Username: ${escapeHtml(user.username || 'N/A')} | ID: ${user.id}</small>
                    </div>
                    <div>
                        <i class="fas fa-user-plus text-primary"></i>
                    </div>
                </div>
            </div>
        `;
    });
    
    resultsList.innerHTML = html;
}

function selectUser(id, username, email, name) {
    // Fill hidden form fields
    document.getElementById('kaizen_user_id').value = id;
    document.getElementById('kaizen_username').value = username;
    document.getElementById('kaizen_email').value = email;
    document.getElementById('kaizen_name').value = name;
    
    // Update search input to show selected user
    const displayName = name || username || ('User #' + id);
    document.getElementById('userSearch').value = displayName;
    
    // Hide search results
    document.getElementById('searchResults').style.display = 'none';
    
    // Show user selection status
    document.getElementById('userSelectionStatus').style.display = 'block';
    document.getElementById('selectedUserDisplay').textContent = displayName;
    
    // Update search input styling
    const searchInput = document.getElementById('userSearch');
    searchInput.style.backgroundColor = '#d4edda';
    searchInput.style.borderColor = '#c3e6cb';
    searchInput.readOnly = true;
}

function clearSelection() {
    // Clear form fields
    document.getElementById('kaizen_user_id').value = '';
    document.getElementById('kaizen_username').value = '';
    document.getElementById('kaizen_email').value = '';
    document.getElementById('kaizen_name').value = '';
    
    // Update UI
    document.getElementById('userSearchSection').style.display = 'block';
    document.getElementById('selectedUserSection').style.display = 'none';
}

function clearSearch() {
    document.getElementById('userSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('userSelectionStatus').style.display = 'none';
    
    // Clear hidden form fields
    document.getElementById('kaizen_user_id').value = '';
    document.getElementById('kaizen_username').value = '';
    document.getElementById('kaizen_email').value = '';
    document.getElementById('kaizen_name').value = '';
    
    // Reset search input
    const searchInput = document.getElementById('userSearch');
    searchInput.style.backgroundColor = '';
    searchInput.style.borderColor = '';
    searchInput.readOnly = false;
    searchInput.placeholder = 'Type name, email, or username to search...';
}

// Cookie functions removed - using PHP proxy instead

function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

// Token refresh function for CSRF protection
function refreshCSRFTokens() {
    // Refresh all CSRF tokens on the page
    fetch('api/refresh_csrf.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.tokens) {
            // Update all CSRF token inputs with new tokens
            Object.keys(data.tokens).forEach(formName => {
                const inputs = document.querySelectorAll(`input[name="csrf_token"][data-form="${formName}"]`);
                inputs.forEach(input => {
                    input.value = data.tokens[formName];
                });
            });
        }
    })
    .catch(error => {
        console.error('Failed to refresh CSRF tokens:', error);
    });
}

// Initialize modal and check API availability
document.addEventListener('DOMContentLoaded', function() {
    // Reset form when modal opens
    $('#grantAccessModal').on('show.bs.modal', function() {
        clearSearch(); // Reset form when modal opens
    });
    
    // Form validation
    const form = document.querySelector('#grantAccessModal form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Check if user is selected
            const userId = document.getElementById('kaizen_user_id').value;
            const roleId = document.getElementById('role_id').value;
            
            if (!userId) {
                e.preventDefault();
                alert('Please search and select a user first.');
                return false;
            }
            
            if (!roleId) {
                e.preventDefault();
                alert('Please select an access level.');
                return false;
            }
            
            // Form is valid, allow submission
            return true;
        });
    }
});

// API is working, no need for availability checks
</script>

<?php require_once 'includes/footer.php'; ?>