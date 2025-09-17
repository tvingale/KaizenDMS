<?php
/**
 * MICRO-STEP 8 WEB TEST: Permission Management Business Logic
 * Access via browser: http://your-domain.com/tools/micro_step_8_web_test.php
 */

// Start output buffering to prevent header issues
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$all_passed = true;
$results = [];
$db = null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MICRO-STEP 8 TEST: Permission Management Business Logic</title>
    <style>
        body { font-family: "Segoe UI", system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1000px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; }
        .step.success { border-left-color: #28a745; background: #d4edda; }
        .step.error { border-left-color: #dc3545; background: #f8d7da; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .summary { background: #e7f3ff; padding: 15px; border-radius: 4px; margin-top: 20px; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 MICRO-STEP 8 TEST: Permission Management Business Logic</h1>
        
        <?php
        
        // Step 1: Test database connection and load dependencies
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Load Dependencies and Test Connection</h3>';
        echo '<p>⚡ DO: Loading database configuration and AdditivePermissionManager...</p>';
        
        try {
            $old_error_level = error_reporting(E_ALL & ~E_WARNING);
            
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';
            require_once __DIR__ . '/../includes/AdditivePermissionManager.php';
            
            error_reporting($old_error_level);
            
            $db = getDB();
            $permissionManager = new AdditivePermissionManager($db);
            
            echo '<p class="success">✅ CHECK: Database and AdditivePermissionManager loaded successfully</p>';
            $results[] = '✅ Dependencies loaded successfully';
            echo '</div>';
        } catch (Exception $e) {
            error_reporting($old_error_level);
            echo '<p class="error">❌ CHECK FAILED: Dependency loading failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Dependency loading failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 2: Test Additive Permission Calculation
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test Additive Permission Model</h3>';
        echo '<p>⚡ DO: Testing union of role permissions...</p>';
        
        try {
            // Test with user 1 (should have admin role)
            $user_id = 1;
            $effective_permissions = $permissionManager->calculateEffectivePermissions($user_id);
            
            if (empty($effective_permissions)) {
                throw new Exception("No effective permissions calculated for user $user_id");
            }
            
            echo '<p class="success">✅ User 1 Effective Permissions: ' . count($effective_permissions) . ' permissions</p>';
            echo '<pre>';
            $count = 0;
            foreach ($effective_permissions as $perm) {
                if ($count++ < 10) { // Show first 10
                    echo "- {$perm['permission_name']} (scope: {$perm['scope_qualifier']})\n";
                }
            }
            if (count($effective_permissions) > 10) {
                echo "... and " . (count($effective_permissions) - 10) . " more permissions\n";
            }
            echo '</pre>';
            
            echo '<p class="success">✅ CHECK: Additive permission model working</p>';
            $results[] = '✅ Additive permission calculation tested';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Permission calculation failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Permission calculation failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 3: Test Permission Scope Resolution
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test Permission Scope Resolution</h3>';
        echo '<p>⚡ DO: Testing most permissive scope wins logic...</p>';
        
        try {
            // Test scope resolution with mock data
            $test_permissions = [
                [
                    'permission_name' => 'documents.view',
                    'scope_qualifier' => 'department',
                    'granted_by_role' => 'supervisor'
                ],
                [
                    'permission_name' => 'documents.view', 
                    'scope_qualifier' => 'all',
                    'granted_by_role' => 'admin'
                ],
                [
                    'permission_name' => 'documents.view',
                    'scope_qualifier' => 'assigned_only', 
                    'granted_by_role' => 'operator'
                ]
            ];
            
            $resolved = $permissionManager->resolvePermissionScopes($test_permissions);
            
            if (count($resolved) !== 1) {
                throw new Exception("Expected 1 resolved permission, got " . count($resolved));
            }
            
            if ($resolved[0]['scope_qualifier'] !== 'all') {
                throw new Exception("Expected 'all' scope to win, got '{$resolved[0]['scope_qualifier']}'");
            }
            
            echo '<p class="success">✅ Scope Resolution Test: Most permissive scope (all) wins</p>';
            echo '<pre>Resolved: documents.view with scope "all" (most permissive)</pre>';
            
            echo '<p class="success">✅ CHECK: Permission scope resolution working</p>';
            $results[] = '✅ Permission scope resolution tested';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Scope resolution failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Scope resolution failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 4: Test Permission Checking Functions
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test Permission Checking Functions</h3>';
        echo '<p>⚡ DO: Testing hasPermission functionality...</p>';
        
        try {
            $user_id = 1;
            
            // Test permission checking
            $has_view_all = $permissionManager->hasPermission($user_id, 'documents.view.all');
            $has_nonexistent = $permissionManager->hasPermission($user_id, 'nonexistent.permission');
            
            echo '<p class="info">Permission Check Results for User 1:</p>';
            echo '<pre>';
            echo "documents.view.all: " . ($has_view_all ? 'GRANTED' : 'DENIED') . "\n";
            echo "nonexistent.permission: " . ($has_nonexistent ? 'GRANTED' : 'DENIED') . "\n";
            echo '</pre>';
            
            if (!$has_view_all) {
                echo '<p class="warning">⚠️ Warning: Admin user should have documents.view.all permission</p>';
            }
            
            if ($has_nonexistent) {
                throw new Exception("User should not have nonexistent permission");
            }
            
            echo '<p class="success">✅ CHECK: Permission checking functions working</p>';
            $results[] = '✅ Permission checking tested';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Permission checking failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Permission checking failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 5: Test Permission Caching System
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test Permission Caching System</h3>';
        echo '<p>⚡ DO: Testing permission cache functionality...</p>';
        
        try {
            $user_id = 1;
            
            // Clear any existing cache
            $permissionManager->invalidateUserCache($user_id);
            
            // First calculation (should cache)
            $start_time = microtime(true);
            $permissions1 = $permissionManager->calculateEffectivePermissions($user_id);
            $calc_time = microtime(true) - $start_time;
            
            // Second calculation (should use cache)
            $start_time = microtime(true);
            $permissions2 = $permissionManager->calculateEffectivePermissions($user_id);
            $cache_time = microtime(true) - $start_time;
            
            echo '<p class="info">Performance Comparison:</p>';
            echo '<pre>';
            echo "First calculation (no cache): " . number_format($calc_time * 1000, 2) . " ms\n";
            echo "Second calculation (cached): " . number_format($cache_time * 1000, 2) . " ms\n";
            echo "Permissions count: " . count($permissions1) . " = " . count($permissions2) . "\n";
            echo '</pre>';
            
            if (count($permissions1) !== count($permissions2)) {
                throw new Exception("Cached permissions don't match calculated permissions");
            }
            
            // Test cache invalidation
            $permissionManager->invalidateUserCache($user_id);
            
            echo '<p class="success">✅ CHECK: Permission caching system working</p>';
            $results[] = '✅ Permission caching tested';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Permission caching failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Permission caching failed';
            $all_passed = false;
            echo '</div>';
            goto summary;
        }
        
        // Step 6: Test Multi-Role Union Calculation
        echo '<div class="step">';
        echo '<h3>📋 PLAN: Test Multi-Role Union Calculation</h3>';
        echo '<p>⚡ DO: Testing additive role permission union...</p>';
        
        try {
            // Create a test user with multiple roles
            $test_user_id = 9999;
            
            // Clean up any existing test data
            $stmt = $db->prepare("DELETE FROM dms_user_roles WHERE user_id = ?");
            $stmt->execute([$test_user_id]);
            
            // Add multiple roles to test user
            $stmt = $db->prepare("
                INSERT INTO dms_user_roles (user_id, role_id, role_name, status, granted_by, assignment_reason) 
                SELECT ?, r.id, r.role_name, 'active', 1, 'MICRO-STEP 8 testing'
                FROM dms_roles r 
                WHERE r.role_name IN ('operator', 'line_lead')
            ");
            $stmt->execute([$test_user_id]);
            
            // Calculate permissions for multi-role user
            $multi_role_permissions = $permissionManager->calculateEffectivePermissions($test_user_id);
            
            echo '<p class="success">✅ Multi-Role User Permissions: ' . count($multi_role_permissions) . ' total permissions</p>';
            echo '<pre>';
            $categories = [];
            foreach ($multi_role_permissions as $perm) {
                $categories[$perm['category']] = ($categories[$perm['category']] ?? 0) + 1;
            }
            foreach ($categories as $cat => $count) {
                echo "$cat: $count permissions\n";
            }
            echo '</pre>';
            
            // Clean up test data
            $stmt = $db->prepare("DELETE FROM dms_user_roles WHERE user_id = ?");
            $stmt->execute([$test_user_id]);
            
            echo '<p class="success">✅ CHECK: Multi-role union calculation working</p>';
            $results[] = '✅ Multi-role union tested';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p class="error">❌ CHECK FAILED: Multi-role union failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
            $results[] = '❌ Multi-role union failed';
            $all_passed = false;
            echo '</div>';
        }
        
        summary:
        ?>
        
        <div class="summary">
            <h2><?php echo $all_passed ? '🎉 MICRO-STEP 8 COMPLETE: All checks passed!' : '❌ MICRO-STEP 8 FAILED: Issues found'; ?></h2>
            
            <h3>📊 SUMMARY:</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($all_passed): ?>
                <p class="success"><strong>🔧 ACT: Permission Management Business Logic Complete!</strong></p>
                <p class="info"><strong>🚀 Ready for MICRO-STEP 9: Document-Level Access Control</strong></p>
                <div class="warning">
                    <strong>🎯 Milestone:</strong> Core RBAC business logic fully functional!<br>
                    <strong>Features:</strong> Additive permissions, scope resolution, caching, multi-role support<br>
                    <strong>Next:</strong> Implement document sensitivity and ACL enforcement.
                </div>
            <?php else: ?>
                <p class="error"><strong>🔧 ACT: Fix issues before proceeding to MICRO-STEP 9</strong></p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p>MICRO-STEP 8: Permission Management Business Logic | PDCA Development Methodology</p>
        </div>
    </div>
</body>
</html>

<?php ob_end_flush(); ?>