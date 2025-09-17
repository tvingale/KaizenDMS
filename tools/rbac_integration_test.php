<?php
/**
 * RBAC Integration Test
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
        <h1>🧪 RBAC Integration Test Suite</h1>

        <?php

        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';
            require_once __DIR__ . '/../includes/AccessControl.php';

            $db = getDB();
            echo '<p class="success">✅ Database connection successful</p>';

            // Test 1: AccessControl Class Loading
            echo '<div class="step">';
            echo '<h3>🔧 Test 1: AccessControl Class Integration</h3>';

            try {
                // Test if AccessControl loads without errors
                $accessControl = new AccessControl($db);
                echo '<div class="test-result test-pass">✅ AccessControl class instantiated successfully</div>';

                // Test if AdditivePermissionManager is available
                if (class_exists('AdditivePermissionManager')) {
                    echo '<div class="test-result test-pass">✅ AdditivePermissionManager class available</div>';

                    try {
                        $permManager = new AdditivePermissionManager($db);
                        echo '<div class="test-result test-pass">✅ AdditivePermissionManager instantiated successfully</div>';
                    } catch (Exception $e) {
                        echo '<div class="test-result test-fail">❌ AdditivePermissionManager instantiation failed: ' . $e->getMessage() . '</div>';
                    }
                } else {
                    echo '<div class="test-result test-info">ℹ️ AdditivePermissionManager class not found - using legacy mode</div>';
                }

            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ AccessControl instantiation failed: ' . $e->getMessage() . '</div>';
            }

            echo '</div>';

            // Test 2: Database Integration
            echo '<div class="step">';
            echo '<h3>🗄️ Test 2: RBAC Database Integration</h3>';

            // Test role queries
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE is_system_role = 1");
                $system_roles = $stmt->fetchColumn();
                echo '<div class="test-result test-pass">✅ System roles query successful: ' . $system_roles . ' roles found</div>';
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ System roles query failed: ' . $e->getMessage() . '</div>';
            }

            // Test permission queries
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE is_system_permission = 1");
                $system_permissions = $stmt->fetchColumn();
                echo '<div class="test-result test-pass">✅ System permissions query successful: ' . $system_permissions . ' permissions found</div>';
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ System permissions query failed: ' . $e->getMessage() . '</div>';
            }

            // Test role-permission mappings
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
                $mappings = $stmt->fetchColumn();
                echo '<div class="test-result test-pass">✅ Role-permission mappings query successful: ' . $mappings . ' mappings found</div>';
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ Role-permission mappings query failed: ' . $e->getMessage() . '</div>';
            }

            echo '</div>';

            // Test 3: Permission Calculation (if AdditivePermissionManager is available)
            echo '<div class="step">';
            echo '<h3>🔍 Test 3: Permission Calculation System</h3>';

            if (class_exists('AdditivePermissionManager')) {
                try {
                    $permManager = new AdditivePermissionManager($db);

                    // Test with a mock user ID (using 1 as test)
                    $test_user_id = 1;

                    // Test effective permissions calculation
                    try {
                        $start_time = microtime(true);
                        $effective_permissions = $permManager->calculateEffectivePermissions($test_user_id);
                        $calc_time = microtime(true) - $start_time;

                        if (is_array($effective_permissions)) {
                            echo '<div class="test-result test-pass">✅ Permission calculation successful: ' . count($effective_permissions) . ' permissions calculated in ' . number_format($calc_time * 1000, 2) . 'ms</div>';

                            // Show sample permissions
                            if (count($effective_permissions) > 0) {
                                echo '<div class="test-result test-info">ℹ️ Sample permissions for user ' . $test_user_id . ':</div>';
                                echo '<table>';
                                echo '<tr><th>Permission</th><th>Scope</th><th>Role</th></tr>';
                                $sample_count = 0;
                                foreach ($effective_permissions as $perm) {
                                    if ($sample_count >= 5) break; // Show only first 5
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($perm['permission_name'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($perm['scope_qualifier'] ?? 'N/A') . '</td>';
                                    echo '<td>' . htmlspecialchars($perm['role_name'] ?? 'N/A') . '</td>';
                                    echo '</tr>';
                                    $sample_count++;
                                }
                                echo '</table>';
                            } else {
                                echo '<div class="test-result test-info">ℹ️ No permissions found for test user (this may be expected if user has no role assignments)</div>';
                            }
                        } else {
                            echo '<div class="test-result test-fail">❌ Permission calculation returned non-array result</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="test-result test-fail">❌ Permission calculation failed: ' . $e->getMessage() . '</div>';
                    }

                    // Test specific permission check
                    try {
                        $has_view_all = $permManager->hasPermission($test_user_id, 'documents.view.all');
                        echo '<div class="test-result test-info">ℹ️ Test permission check (documents.view.all): ' . ($has_view_all ? 'GRANTED' : 'DENIED') . '</div>';
                    } catch (Exception $e) {
                        echo '<div class="test-result test-fail">❌ Specific permission check failed: ' . $e->getMessage() . '</div>';
                    }

                } catch (Exception $e) {
                    echo '<div class="test-result test-fail">❌ Permission manager test failed: ' . $e->getMessage() . '</div>';
                }
            } else {
                echo '<div class="test-result test-info">ℹ️ AdditivePermissionManager not available - permission calculation tests skipped</div>';
            }

            echo '</div>';

            // Test 4: Legacy Integration
            echo '<div class="step">';
            echo '<h3>🔄 Test 4: Legacy System Integration</h3>';

            try {
                // Test legacy role check
                $legacy_roles = ['admin', 'manager', 'user'];
                foreach ($legacy_roles as $role) {
                    try {
                        $stmt = $db->prepare("SELECT COUNT(*) FROM dms_roles WHERE name = ? AND id <= 4");
                        $stmt->execute([$role]);
                        $count = $stmt->fetchColumn();
                        if ($count > 0) {
                            echo '<div class="test-result test-pass">✅ Legacy role "' . $role . '" preserved</div>';
                        } else {
                            echo '<div class="test-result test-info">ℹ️ Legacy role "' . $role . '" not found (may have been cleaned up)</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="test-result test-fail">❌ Legacy role check for "' . $role . '" failed: ' . $e->getMessage() . '</div>';
                    }
                }

                // Test if there are any user role assignments
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM dms_user_roles");
                    $user_roles = $stmt->fetchColumn();
                    echo '<div class="test-result test-info">ℹ️ Total user role assignments: ' . $user_roles . '</div>';

                    if ($user_roles > 0) {
                        $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM dms_user_roles WHERE status = 'active'");
                        $active_users = $stmt->fetchColumn();
                        echo '<div class="test-result test-info">ℹ️ Users with active role assignments: ' . $active_users . '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="test-result test-fail">❌ User role assignment check failed: ' . $e->getMessage() . '</div>';
                }

            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ Legacy integration test failed: ' . $e->getMessage() . '</div>';
            }

            echo '</div>';

            // Test 5: System Health Check
            echo '<div class="step">';
            echo '<h3>🏥 Test 5: RBAC System Health Check</h3>';

            $health_score = 0;
            $total_checks = 0;

            // Check 1: Role completeness
            $total_checks++;
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_roles WHERE is_system_role = 1");
                $rbac_roles = $stmt->fetchColumn();
                if ($rbac_roles >= 7) {
                    echo '<div class="test-result test-pass">✅ Role system complete (' . $rbac_roles . ' roles)</div>';
                    $health_score++;
                } else {
                    echo '<div class="test-result test-fail">❌ Role system incomplete (only ' . $rbac_roles . ' roles)</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ Role completeness check failed</div>';
            }

            // Check 2: Permission completeness
            $total_checks++;
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_permissions WHERE is_system_permission = 1");
                $system_perms = $stmt->fetchColumn();
                if ($system_perms >= 15) {
                    echo '<div class="test-result test-pass">✅ Permission system complete (' . $system_perms . ' permissions)</div>';
                    $health_score++;
                } else {
                    echo '<div class="test-result test-fail">❌ Permission system incomplete (only ' . $system_perms . ' permissions)</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ Permission completeness check failed</div>';
            }

            // Check 3: Role-Permission mapping completeness
            $total_checks++;
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM dms_role_permissions");
                $mappings = $stmt->fetchColumn();
                if ($mappings >= 30) {
                    echo '<div class="test-result test-pass">✅ Role-permission mappings complete (' . $mappings . ' mappings)</div>';
                    $health_score++;
                } else {
                    echo '<div class="test-result test-fail">❌ Role-permission mappings incomplete (only ' . $mappings . ' mappings)</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ Role-permission mapping check failed</div>';
            }

            // Check 4: AccessControl integration
            $total_checks++;
            try {
                $accessControl = new AccessControl($db);
                echo '<div class="test-result test-pass">✅ AccessControl integration working</div>';
                $health_score++;
            } catch (Exception $e) {
                echo '<div class="test-result test-fail">❌ AccessControl integration failed</div>';
            }

            // Calculate health percentage
            $health_percentage = $total_checks > 0 ? round(($health_score / $total_checks) * 100) : 0;

            echo '<div style="margin-top: 20px; padding: 15px; border-radius: 5px; ' .
                 ($health_percentage >= 80 ? 'background: #d4edda; color: #155724;' :
                  ($health_percentage >= 60 ? 'background: #fff3cd; color: #856404;' : 'background: #f8d7da; color: #721c24;')) . '">';
            echo '<h4>🎯 RBAC System Health: ' . $health_percentage . '%</h4>';
            echo '<p>Health Score: ' . $health_score . ' / ' . $total_checks . ' checks passed</p>';

            if ($health_percentage >= 80) {
                echo '<p><strong>✅ RBAC system is healthy and ready for production use!</strong></p>';
                echo '<p><strong>Next Steps:</strong></p>';
                echo '<ul>';
                echo '<li>✅ Build admin interfaces for RBAC management</li>';
                echo '<li>✅ Integrate RBAC with document management pages</li>';
                echo '<li>✅ Test with real user scenarios</li>';
                echo '</ul>';
            } elseif ($health_percentage >= 60) {
                echo '<p><strong>⚠️ RBAC system is functional but needs optimization.</strong></p>';
            } else {
                echo '<p><strong>❌ RBAC system needs attention before production use.</strong></p>';
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
            <p><strong>RBAC Integration Test Suite</strong></p>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>