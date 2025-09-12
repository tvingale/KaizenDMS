<?php
require_once 'config.php';
require_once 'includes/kaizen_sso.php';
require_once 'includes/database.php';

$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

try {
    $sso = new KaizenSSO($ssoConfig);
    
    // Debug: Show what's happening
    if (DEBUG_MODE) {
        error_log("KaizenAuth SSO - Request URI: " . $_SERVER['REQUEST_URI']);
        error_log("KaizenAuth SSO - GET params: " . print_r($_GET, true));
        error_log("KaizenAuth SSO - Session: " . print_r($_SESSION, true));
    }
    
    // Check if user is already authenticated (session-based)
    if ($sso->isAuthenticated()) {
        $result = $sso->handleAuthCallback();
        
        if ($result['success']) {
            $user = $result['user'];
            
            // Set user role from database or default
            if (ENABLE_RBAC) {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("
                        SELECT r.name FROM dms_user_roles ur
                        JOIN dms_roles r ON ur.role_id = r.id
                        WHERE ur.user_id = ? AND ur.status = 'active'
                        LIMIT 1
                    ");
                    $stmt->execute([$user['id']]);
                    $role = $stmt->fetchColumn();
                    
                    // If no role assigned, check if user is KaizenAuth superadmin or assign default role
                    if (!$role) {
                        // Check if user is superadmin in KaizenAuth
                        $assignedRole = DEFAULT_ROLE;
                        $assignedRoleId = null;
                        
                        // Auto-assign superadmin if user has superadmin role in KaizenAuth
                        if (isset($user['role']) && $user['role'] === 'superadmin') {
                            $stmt = $db->prepare("SELECT id FROM dms_roles WHERE name = 'admin'");
                            $stmt->execute();
                            $adminRoleId = $stmt->fetchColumn();
                            
                            if ($adminRoleId) {
                                $assignedRole = 'admin';
                                $assignedRoleId = $adminRoleId;
                                
                                if (DEBUG_MODE) {
                                    error_log("KaizenAuth: Auto-assigning admin role to user " . $user['id']);
                                }
                            }
                        }
                        
                        // If not superadmin, assign default role
                        if (!$assignedRoleId) {
                            $stmt = $db->prepare("SELECT id FROM dms_roles WHERE name = ?");
                            $stmt->execute([DEFAULT_ROLE]);
                            $assignedRoleId = $stmt->fetchColumn();
                        }
                        
                        if ($assignedRoleId) {
                            // Assign role to user
                            $stmt = $db->prepare("
                                INSERT INTO dms_user_roles (user_id, role_id, status, granted_by, granted_at) 
                                VALUES (?, ?, 'active', 0, NOW())
                            ");
                            $stmt->execute([$user['id'], $assignedRoleId]);
                            $role = $assignedRole;
                            
                            // Log the role assignment
                            $stmt = $db->prepare("
                                INSERT INTO dms_activity_log 
                                (entity_type, entity_id, action, user_id, details, created_at)
                                VALUES ('user', ?, 'role_assigned', ?, ?, NOW())
                            ");
                            $stmt->execute([
                                $user['id'],
                                $user['id'],
                                json_encode(['role' => $assignedRole, 'source' => 'auto_sso'])
                            ]);
                        }
                    }
                    
                    $_SESSION['user_role'] = $role ?: DEFAULT_ROLE;
                } catch (Exception $e) {
                    // If database error, use default role
                    $_SESSION['user_role'] = DEFAULT_ROLE;
                    error_log("Error setting user role: " . $e->getMessage());
                }
            } else {
                $_SESSION['user_role'] = DEFAULT_ROLE;
            }
            
            // Log successful login
            if (ENABLE_RBAC) {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("
                        INSERT INTO dms_activity_log 
                        (entity_type, entity_id, action, user_id, ip_address, user_agent, created_at)
                        VALUES ('user', ?, 'login', ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $user['id'],
                        $user['id'],
                        $_SERVER['REMOTE_ADDR'] ?? '',
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                } catch (Exception $e) {
                    // Logging error shouldn't prevent login
                    error_log("Error logging login: " . $e->getMessage());
                }
            }
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            // Authentication session exists but validation failed
            $error = $result['error'];
        }
    } else {
        // No authentication session, redirect to KaizenAuth login
        $sso->redirectToLogin();
    }
} catch (Exception $e) {
    $error = "Configuration error: " . $e->getMessage();
    if (DEBUG_MODE) {
        error_log("SSO Exception: " . $e->getMessage());
        error_log("SSO Stack trace: " . $e->getTraceAsString());
    } else {
        $error = "An error occurred. Please contact administrator.";
    }
}

// If we reach here, there was an error
if (DEBUG_MODE && !isset($error)) {
    $error = "SSO flow completed but no redirect occurred. Check logs for details.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Error - Dms Management</title>
    <style>
        body {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        h1 {
            color: #e53e3e;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin: 10px;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Login Error</h1>
        <div class="error-message">
            <?php echo htmlspecialchars($error ?? 'Unknown error occurred'); ?>
        </div>
        <a href="index.php" class="btn">Back to Home</a>
        <a href="sso.php" class="btn">Try Again</a>
    </div>
</body>
</html>