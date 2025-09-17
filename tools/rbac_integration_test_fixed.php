<?php
/**
 * RBAC Integration Test - Fixed Version
 * Comprehensive test of RBAC system integration with AccessControl.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBAC Integration Test</title>
    <style>
        body { font-family: "Segoe UI", system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; }
        .step.success { border-left-color: #28a745; background: #d4edda; }
        .step.error { border-left-color: #dc3545; background: #f8d7da; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .test-result { padding: 8px; border-radius: 4px; margin: 5px 0; }
        .test-pass { background: #d4edda; color: #155724; }
        .test-fail { background: #f8d7da; color: #721c24; }
        .test-info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 RBAC Integration Test Suite (Fixed)</h1>

        <?php

        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';

            $db = getDB();
            echo '<p class="success">✅ Database connection successful</p>';

            // Create mock user for testing
            $mock_user = [
                'id' => 1,
                'username' => 'test_user',
                'name' => 'Test User',
                'email' => 'test@example.com'
            ];

            // Test 1: AccessControl Class Loading
            echo '<div class="step">';
            echo '<h3>🔧 Test 1: AccessControl Class Integration</h3>';

            try {
                // Test if AccessControl loads without errors (with correct parameters)
                require_once __DIR__ . '/../includes/AccessControl.php';
                $accessControl = new AccessControl($db, $mock_user);
                echo '<div class="test-result test-pass">✅ AccessControl class instantiated successfully with correct parameters</div>';

                // Test if AdditivePermissionManager is available
                if (file_exists(__DIR__ . '/../includes/AdditivePermissionManager.php')) {
                    echo '<div class="test-result test-pass">✅ AdditivePermissionManager file found</div>';

                    require_once __DIR__ . '/../includes/AdditivePermissionManager.php';
                    if (class_exists('AdditivePermissionManager')) {
                        echo '<div class="test-result test-pass">✅ AdditivePermissionManager class loaded</div>';

                        try {
                            $permManager = new AdditivePermissionManager($db);
                            echo '<div class="test-result test-pass">✅ AdditivePermissionManager instantiated successfully</div>';
                        } catch (Exception $e) {
                            echo '<div class="test-result test-fail">❌ AdditivePermissionManager instantiation failed: ' . $e->getMessage() . '</div>';
                        }
                    } else {
                        echo '<div class="test-result test-fail">❌ AdditivePermissionManager class not found after require</div>';
                    }
                } else {
                    echo '<div class="test-result test-info">ℹ️ AdditivePermissionManager file not found - using legacy mode</div>';
                }

            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ AccessControl instantiation failed: ' . $e->getMessage() . '</div>';
            }

            echo '</div>';

            // Test 2: RBAC Database Status
            echo '<div class="step">';
            echo '<h3>🗄️ Test 2: RBAC Database Status</h3>';

            // Test role queries
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE id >= 7");
                $rbac_roles = $stmt->fetchColumn();
                echo '<div class="test-result test-pass">✅ RBAC roles query: ' . $rbac_roles . ' roles found</div>';

                if ($rbac_roles > 0) {
                    $stmt = $db->query("SELECT id, name, role_name, display_name FROM dms_roles WHERE id >= 7 ORDER BY hierarchy_level");
                    echo '<table><tr><th>ID</th><th>Name</th><th>Role Name</th><th>Display Name</th></tr>';
                    while ($row = $stmt->fetch()) {
                        echo '<tr><td>' . $row['id'] . '</td><td>' . $row['name'] . '</td><td>' . $row['role_name'] . '</td><td>' . $row['display_name'] . '</td></tr>';
                    }
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ RBAC roles query failed: ' . $e->getMessage() . '</div>';
            }

            // Test permission queries
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions");
                $total_permissions = $stmt->fetchColumn();
                echo '<div class="test-result test-pass">✅ Total permissions: ' . $total_permissions . '</div>';

                $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE is_system_permission = 1");
                $system_permissions = $stmt->fetchColumn();
                echo '<div class="test-result test-pass">✅ System permissions: ' . $system_permissions . '</div>';
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ Permissions query failed: ' . $e->getMessage() . '</div>';
            }

            // Test role-permission mappings
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
                $mappings = $stmt->fetchColumn();
                echo '<div class="test-result test-pass">✅ Role-permission mappings: ' . $mappings . '</div>';

                // Show mapping distribution
                $stmt = $db->query("
                    SELECT r.role_name, r.display_name, COUNT(rp.permission_id) as permission_count
                    FROM dms_roles r
                    LEFT JOIN dms_role_permissions rp ON r.id = rp.role_id
                    WHERE r.id >= 7
                    GROUP BY r.id, r.role_name, r.display_name
                    ORDER BY r.hierarchy_level
                ");
                echo '<table><tr><th>Role</th><th>Display Name</th><th>Permissions</th></tr>';
                while ($row = $stmt->fetch()) {
                    echo '<tr><td>' . $row['role_name'] . '</td><td>' . $row['display_name'] . '</td><td>' . $row['permission_count'] . '</td></tr>';
                }
                echo '</table>';

            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ Role-permission mappings query failed: ' . $e->getMessage() . '</div>';
            }

            echo '</div>';

            // Test 3: AdditivePermissionManager Functionality
            echo '<div class="step">';
            echo '<h3>🔍 Test 3: RBAC Permission System</h3>';

            if (class_exists('AdditivePermissionManager')) {
                try {
                    $permManager = new AdditivePermissionManager($db);

                    // Test with mock user ID
                    $test_user_id = 1;

                    // Check if user has any role assignments
                    try {
                        $stmt = $db->prepare("SELECT COUNT(*) FROM dms_user_roles WHERE user_id = ? AND status = 'active'");
                        $stmt->execute([$test_user_id]);
                        $user_roles = $stmt->fetchColumn();
                        echo '<div class="test-result test-info">ℹ️ Test user (ID: ' . $test_user_id . ') has ' . $user_roles . ' active role assignments</div>';

                        if ($user_roles === 0) {
                            // Create a test role assignment for demonstration
                            echo '<div class="test-result test-info">ℹ️ Creating temporary test role assignment for demonstration...</div>';
                            try {
                                $stmt = $db->prepare("INSERT INTO dms_user_roles (user_id, role_id, status, granted_at) VALUES (?, 13, 'active', NOW())");
                                $stmt->execute([$test_user_id]);
                                echo '<div class="test-result test-pass">✅ Test role assignment created (system_admin for testing)</div>';
                            } catch (Exception $e) {
                                echo '<div class="test-result test-info">ℹ️ Could not create test role assignment: ' . $e->getMessage() . '</div>';
                            }
                        }

                        // Test effective permissions calculation
                        $start_time = microtime(true);
                        $effective_permissions = $permManager->calculateEffectivePermissions($test_user_id);
                        $calc_time = microtime(true) - $start_time;

                        if (is_array($effective_permissions)) {
                            echo '<div class="test-result test-pass">✅ Permission calculation: ' . count($effective_permissions) . ' permissions in ' . number_format($calc_time * 1000, 2) . 'ms</div>';

                            // Show sample permissions
                            if (count($effective_permissions) > 0) {
                                echo '<div class="test-result test-info">ℹ️ Sample effective permissions:</div>';
                                echo '<table><tr><th>Permission</th><th>Scope</th><th>Context</th></tr>';
                                $sample_count = 0;
                                foreach ($effective_permissions as $perm) {
                                    if ($sample_count >= 5) break;
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($perm['permission_name'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($perm['scope_qualifier'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($perm['context_type'] ?? 'default') . '</td>';
                                    echo '</tr>';
                                    $sample_count++;
                                }
                                echo '</table>';
                            } else {
                                echo '<div class="test-result test-info">ℹ️ No effective permissions calculated (user may need role assignments)</div>';
                            }
                        }

                        // Test specific permission checks
                        $test_permissions = ['documents.view.all', 'documents.create', 'users.manage.all'];
                        foreach ($test_permissions as $perm_name) {
                            try {
                                $has_perm = $permManager->hasPermission($test_user_id, $perm_name);
                                echo '<div class="test-result test-info">ℹ️ Permission "' . $perm_name . '": ' . ($has_perm ? 'GRANTED' : 'DENIED') . '</div>';
                            } catch (Exception $e) {
                                echo '<div class="test-result test-fail">❌ Permission check "' . $perm_name . '" failed: ' . $e->getMessage() . '</div>';
                            }
                        }

                        // Clean up test role assignment if we created it
                        if ($user_roles === 0) {
                            try {
                                $stmt = $db->prepare("DELETE FROM dms_user_roles WHERE user_id = ? AND role_id = 13");
                                $stmt->execute([$test_user_id]);
                                echo '<div class="test-result test-info">ℹ️ Test role assignment cleaned up</div>';
                            } catch (Exception $e) {
                                // Ignore cleanup errors
                            }
                        }

                    } catch (Exception $e) {
                        echo '<div class="test-result test-fail">❌ User role assignment check failed: ' . $e->getMessage() . '</div>';
                    }

                } catch (Exception $e) {
                    echo '<div class="test-result test-fail">❌ AdditivePermissionManager functionality test failed: ' . $e->getMessage() . '</div>';
                }
            } else {
                echo '<div class="test-result test-info">ℹ️ AdditivePermissionManager not available - functionality tests skipped</div>';
            }

            echo '</div>';

            // Test 4: System Health Summary
            echo '<div class="step">';
            echo '<h3>🏥 Test 4: RBAC System Health Summary</h3>';

            $health_checks = [];
            $total_score = 0;
            $max_score = 0;

            // Check 1: RBAC roles
            $max_score++;
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE id >= 7");
                $rbac_roles = $stmt->fetchColumn();
                if ($rbac_roles >= 7) {
                    $health_checks['roles'] = ['status' => 'pass', 'message' => 'RBAC roles complete (' . $rbac_roles . ' roles)'];
                    $total_score++;
                } else {
                    $health_checks['roles'] = ['status' => 'fail', 'message' => 'RBAC roles incomplete (' . $rbac_roles . ' roles)'];
                }
            } catch (Exception $e) {
                $health_checks['roles'] = ['status' => 'fail', 'message' => 'Role check failed: ' . $e->getMessage()];
            }

            // Check 2: Permissions
            $max_score++;
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions");
                $permissions = $stmt->fetchColumn();
                if ($permissions >= 15) {
                    $health_checks['permissions'] = ['status' => 'pass', 'message' => 'Permissions complete (' . $permissions . ' permissions)'];
                    $total_score++;
                } else {
                    $health_checks['permissions'] = ['status' => 'fail', 'message' => 'Permissions incomplete (' . $permissions . ' permissions)'];
                }
            } catch (Exception $e) {
                $health_checks['permissions'] = ['status' => 'fail', 'message' => 'Permission check failed: ' . $e->getMessage()];
            }

            // Check 3: Role-Permission mappings
            $max_score++;
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
                $mappings = $stmt->fetchColumn();
                if ($mappings >= 30) {
                    $health_checks['mappings'] = ['status' => 'pass', 'message' => 'Mappings complete (' . $mappings . ' mappings)'];
                    $total_score++;
                } else {
                    $health_checks['mappings'] = ['status' => 'fail', 'message' => 'Mappings incomplete (' . $mappings . ' mappings)'];
                }
            } catch (Exception $e) {
                $health_checks['mappings'] = ['status' => 'fail', 'message' => 'Mapping check failed: ' . $e->getMessage()];
            }

            // Check 4: AccessControl integration
            $max_score++;
            try {
                $accessControl = new AccessControl($db, $mock_user);
                $health_checks['access_control'] = ['status' => 'pass', 'message' => 'AccessControl integration working'];
                $total_score++;
            } catch (Exception $e) {
                $health_checks['access_control'] = ['status' => 'fail', 'message' => 'AccessControl failed: ' . $e->getMessage()];
            }

            // Check 5: AdditivePermissionManager
            $max_score++;
            if (class_exists('AdditivePermissionManager')) {
                try {
                    $permManager = new AdditivePermissionManager($db);
                    $health_checks['permission_manager'] = ['status' => 'pass', 'message' => 'AdditivePermissionManager working'];
                    $total_score++;
                } catch (Exception $e) {
                    $health_checks['permission_manager'] = ['status' => 'fail', 'message' => 'AdditivePermissionManager failed: ' . $e->getMessage()];
                }
            } else {
                $health_checks['permission_manager'] = ['status' => 'info', 'message' => 'AdditivePermissionManager not available (legacy mode)'];
            }

            // Display health check results
            foreach ($health_checks as $check_name => $result) {
                $class = $result['status'] === 'pass' ? 'test-pass' : ($result['status'] === 'fail' ? 'test-fail' : 'test-info');
                $icon = $result['status'] === 'pass' ? '✅' : ($result['status'] === 'fail' ? '❌' : 'ℹ️');
                echo '<div class="test-result ' . $class . '">' . $icon . ' ' . $result['message'] . '</div>';
            }

            // Calculate overall health
            $health_percentage = $max_score > 0 ? round(($total_score / $max_score) * 100) : 0;

            echo '<div style="margin-top: 20px; padding: 15px; border-radius: 5px; ' .
                 ($health_percentage >= 80 ? 'background: #d4edda; color: #155724;' :
                  ($health_percentage >= 60 ? 'background: #fff3cd; color: #856404;' : 'background: #f8d7da; color: #721c24;')) . '">';
            echo '<h4>🎯 RBAC System Health: ' . $health_percentage . '%</h4>';
            echo '<p>Health Score: ' . $total_score . ' / ' . $max_score . ' checks passed</p>';

            if ($health_percentage >= 80) {
                echo '<p><strong>✅ RBAC system is healthy and ready for admin interface development!</strong></p>';
                echo '<p><strong>Recommended Next Steps:</strong></p>';
                echo '<ul>';
                echo '<li>✅ Overhaul admin/roles_permissions.php with RBAC management</li>';
                echo '<li>✅ Update module_users.php for multi-role assignments</li>';
                echo '<li>✅ Integrate RBAC with document management pages</li>';
                echo '<li>✅ Create comprehensive RBAC testing scenarios</li>';
                echo '</ul>';
            } elseif ($health_percentage >= 60) {
                echo '<p><strong>⚠️ RBAC system is functional but needs some attention.</strong></p>';
            } else {
                echo '<p><strong>❌ RBAC system needs significant work before proceeding.</strong></p>';
            }
            echo '</div>';

            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="step error">';
            echo '<h3>❌ Fatal Error in RBAC Integration Test</h3>';
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }

        ?>

        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p><strong>RBAC Integration Test Suite (Fixed)</strong></p>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>