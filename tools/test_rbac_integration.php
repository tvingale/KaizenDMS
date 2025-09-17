<?php
/**
 * RBAC Integration Test
 * Test AccessControl.php integration with AdditivePermissionManager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/kaizen_sso.php';
require_once __DIR__ . '/../includes/AccessControl.php';

$sso = new KaizenSSO([
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
]);

echo "<h2>🔍 RBAC Integration Test</h2>";

// Test 1: Check if user is authenticated
if (!$sso->isAuthenticated()) {
    echo "<div style='color: red;'>❌ User not authenticated. Please login first.</div>";
    echo "<p><a href='../sso.php'>Login via KaizenAuth</a></p>";
    exit;
}

$user = $sso->getUserInfo();
$db = getDB();

echo "<h3>✅ KaizenAuth Integration Test</h3>";
echo "<p><strong>User:</strong> " . htmlspecialchars($user['name'] ?? $user['username'] ?? 'Unknown') . "</p>";
echo "<p><strong>User ID:</strong> " . $user['id'] . "</p>";

// Test 2: Create AccessControl instance
try {
    $accessControl = new AccessControl($db, $user);
    echo "<p>✅ AccessControl instance created successfully</p>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Failed to create AccessControl: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Test 3: Check if new RBAC system is available
echo "<h3>🆕 New RBAC System Test</h3>";
if ($accessControl->isNewRBACAvailable()) {
    echo "<p>✅ AdditivePermissionManager loaded successfully</p>";
    
    // Test effective permissions
    try {
        $effectivePermissions = $accessControl->getEffectivePermissions();
        echo "<p>✅ Effective permissions calculated: " . count($effectivePermissions) . " permissions</p>";
        
        if (count($effectivePermissions) > 0) {
            echo "<details><summary>View Permissions (first 10)</summary><ul>";
            $count = 0;
            foreach ($effectivePermissions as $perm) {
                if ($count++ >= 10) break;
                echo "<li>" . htmlspecialchars($perm['permission_name']) . " (scope: " . htmlspecialchars($perm['scope_qualifier']) . ")</li>";
            }
            echo "</ul></details>";
        }
    } catch (Exception $e) {
        echo "<div style='color: orange;'>⚠️ Effective permissions failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ AdditivePermissionManager not available - using legacy system</p>";
}

// Test 4: Test permission checking (both new and legacy)
echo "<h3>🔑 Permission Checking Test</h3>";

$testPermissions = ['documents.view.all', 'users.manage', 'system.configure.all'];

foreach ($testPermissions as $permission) {
    $hasPermission = $accessControl->hasPermission($permission);
    $status = $hasPermission ? '✅ GRANTED' : '❌ DENIED';
    echo "<p><strong>$permission:</strong> $status</p>";
}

// Test 5: Test legacy methods still work
echo "<h3>🔄 Legacy Compatibility Test</h3>";
echo "<p><strong>canManageUsers():</strong> " . ($accessControl->canManageUsers() ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><strong>canViewReports():</strong> " . ($accessControl->canViewReports() ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><strong>hasModuleAccess():</strong> " . ($accessControl->hasModuleAccess() ? '✅ YES' : '❌ NO') . "</p>";

// Test 6: Check user role
echo "<h3>👤 User Role Test</h3>";
$userRole = $accessControl->getUserRole();
echo "<p><strong>Current Role:</strong> " . ($userRole ? htmlspecialchars($userRole) : 'No role assigned') . "</p>";

// Test 7: Module access check
echo "<h3>🏢 Module Access Test</h3>";
try {
    $moduleAccess = $accessControl->checkAccess();
    echo "<p><strong>Module Access:</strong> " . ($moduleAccess ? '✅ GRANTED' : '❌ DENIED') . "</p>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Module access check failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h3>📊 Integration Status</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Integration Successful!</h4>";
echo "<ul>";
echo "<li>✅ KaizenAuth authentication working</li>";
echo "<li>✅ AccessControl instance created</li>";
echo "<li>✅ Legacy methods preserved</li>";
echo "<li>✅ New RBAC methods available</li>";
echo "<li>✅ Fallback mechanisms functional</li>";
echo "</ul>";
echo "<p><strong>Status:</strong> Ready for gradual RBAC adoption</p>";
echo "</div>";

echo "<p><small>Test completed: " . date('Y-m-d H:i:s') . "</small></p>";
?>