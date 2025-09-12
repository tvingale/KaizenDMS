<?php
/**
 * KaizenAuth Access Handler
 * Handles JWT token-based access from KaizenAuth
 */

// Start output buffering to prevent header issues
ob_start();

require_once 'config.php';
require_once 'includes/kaizen_sso.php';
require_once 'includes/database.php';

// Debug: Log the access attempt
if (DEBUG_MODE) {
    error_log("KaizenAuth Access: " . json_encode([
        'token' => $_GET['token'] ?? 'not provided',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]));
}

try {
    // Get parameters from KaizenAuth
    $token = $_GET['token'] ?? null;
    $returnUrl = $_GET['return_url'] ?? 'https://auth.kaizenapps.co.in/apps.php';
    
    if (!$token) {
        throw new Exception('No access token provided');
    }
    
    // Store return URL for later use
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['auth_return_url'] = $returnUrl;
    
    // Initialize SSO
    $ssoConfig = [
        'auth_domain' => KAIZEN_AUTH_URL,
        'app_id' => KAIZEN_APP_ID,
        'app_secret' => KAIZEN_APP_SECRET
    ];
    
    $sso = new KaizenSSO($ssoConfig);
    
    // Decode the JWT token from URL
    function decodeJWT($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        
        try {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return $payload;
        } catch (Exception $e) {
            return null;
        }
    }
    
    $payload = decodeJWT($token);
    
    if (!$payload) {
        throw new Exception('Invalid JWT token format');
    }
    
    // Check token expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        throw new Exception('Token has expired');
    }
    
    // Check required fields
    if (!isset($payload['user_id']) || !isset($payload['username'])) {
        throw new Exception('Invalid token payload - missing user information');
    }
    
    // Parse first name and last name from the full name
    $fullName = $payload['name'] ?? $payload['username'];
    $nameParts = explode(' ', trim($fullName), 2);
    $firstName = $nameParts[0] ?? $payload['username'];
    $lastName = $nameParts[1] ?? '';
    
    // Create user info from token
    $user = [
        'id' => $payload['user_id'],
        'username' => $payload['username'],
        'email' => $payload['email'] ?? $payload['username'] . '@' . parse_url(KAIZEN_AUTH_URL, PHP_URL_HOST),
        'name' => $fullName,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'display_name' => trim($firstName . ' ' . $lastName) ?: $payload['username'],
        'role' => $payload['role'] ?? 'user'
    ];
    
    // CRITICAL: Check if user has access to this module using unified AccessControl
    require_once 'includes/database.php';
    require_once 'includes/AccessControl.php';
    
    $db = getDB();
    $accessControl = new AccessControl($db, $user);
    
    if (!$accessControl->hasModuleAccess()) {
        // User not authorized for this module - DENY ACCESS
        if (DEBUG_MODE) {
            error_log("KaizenAuth: Access DENIED for user {$user['id']} ({$user['username']}) - no module access");
        }
        
        // Log the access attempt
        try {
            $stmt = $db->prepare("
                INSERT INTO dms_activity_log 
                (entity_type, entity_id, action, user_id, new_values, created_at)
                VALUES ('security', ?, 'access_denied', ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $user['id'],
                json_encode([
                    'reason' => 'no_module_access',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'entry_point' => 'kaizen_auth_portal'
                ])
            ]);
        } catch (Exception $e) {
            // Continue even if logging fails
        }
        
        // Redirect with error to KaizenAuth using return URL
        $redirectParams = [
            'from' => 'Document Management System',
            'error' => 'access_denied',
            'message' => 'You do not have permission to access this application.'
        ];
        
        $separator = (strpos($returnUrl, '?') !== false) ? '&' : '?';
        $finalUrl = $returnUrl . $separator . http_build_query($redirectParams);
        
        ob_clean();
        header("Location: " . $finalUrl);
        exit;
    }
    
    // Update last access time for authorized user (handled by AccessControl)
    // AccessControl will update last_access automatically when checkAccess() is called
    
    // Set session variables for compatibility
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['kaizen_authenticated'] = true;
    $_SESSION['kaizen_user'] = $user;
    $_SESSION['kaizen_token'] = $token;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    $_SESSION['token'] = $token;
    
    // Handle role assignment if RBAC is enabled
    if (ENABLE_RBAC) {
        try {
            $db = getDB();
            
            // Check if user has role assigned
            $stmt = $db->prepare("
                SELECT r.name FROM dms_user_roles ur
                JOIN dms_roles r ON ur.role_id = r.id
                WHERE ur.user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$user['id']]);
            $role = $stmt->fetchColumn();
            
            // If no role assigned, assign default or auto-detect admin
            if (!$role) {
                $assignedRole = DEFAULT_ROLE;
                $assignedRoleId = null;
                
                // Auto-assign admin if user has admin role in KaizenAuth
                if (isset($user['role']) && in_array($user['role'], ['admin', 'superadmin'])) {
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
                
                // If not admin, assign default role
                if (!$assignedRoleId) {
                    $stmt = $db->prepare("SELECT id FROM dms_roles WHERE name = ?");
                    $stmt->execute([DEFAULT_ROLE]);
                    $assignedRoleId = $stmt->fetchColumn();
                }
                
                if ($assignedRoleId) {
                    // Assign role to user
                    $stmt = $db->prepare("
                        INSERT INTO dms_user_roles (user_id, role_id, assigned_by, assigned_at) 
                        VALUES (?, ?, 'auto_access', NOW())
                        ON DUPLICATE KEY UPDATE assigned_at = NOW()
                    ");
                    $stmt->execute([$user['id'], $assignedRoleId]);
                    $role = $assignedRole;
                }
            }
            
            $_SESSION['user_role'] = $role ?: DEFAULT_ROLE;
            
        } catch (Exception $e) {
            $_SESSION['user_role'] = DEFAULT_ROLE;
            if (DEBUG_MODE) {
                error_log("Error setting user role: " . $e->getMessage());
            }
        }
    } else {
        $_SESSION['user_role'] = DEFAULT_ROLE;
    }
    
    // Log successful access
    if (ENABLE_RBAC) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO dms_activity_log 
                (entity_type, entity_id, action, user_id, ip_address, user_agent, created_at)
                VALUES ('user', ?, 'access_login', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // Logging error shouldn't prevent access
            if (DEBUG_MODE) {
                error_log("Error logging access: " . $e->getMessage());
            }
        }
    }
    
    // Clean output buffer and redirect to dashboard
    ob_clean();
    header('Location: dashboard.php');
    exit;
    
} catch (Exception $e) {
    // Handle errors
    $error = DEBUG_MODE ? $e->getMessage() : 'Access denied';
    
    if (DEBUG_MODE) {
        error_log("KaizenAuth Access Error: " . $e->getMessage());
    }
    
    // Determine error type and redirect to KaizenAuth with proper error
    $errorType = 'session_expired';
    $errorMessage = 'Your session has expired. Please log in again.';
    
    // Check for specific error conditions
    if (strpos($e->getMessage(), 'expired') !== false) {
        $errorType = 'session_expired';
        $errorMessage = 'Your session has expired. Please log in again.';
    } elseif (strpos($e->getMessage(), 'No access token') !== false) {
        $errorType = 'session_expired';
        $errorMessage = 'Invalid or missing access token. Please log in again.';
    } else {
        $errorType = 'access_denied';
        $errorMessage = 'Access denied. Please contact your administrator.';
    }
    
    // Use return URL if available, otherwise use default
    $returnUrl = $_GET['return_url'] ?? 'https://auth.kaizenapps.co.in/apps.php';
    
    $redirectParams = [
        'from' => 'Document Management System',
        'error' => $errorType,
        'message' => $errorMessage
    ];
    
    $separator = (strpos($returnUrl, '?') !== false) ? '&' : '?';
    $finalUrl = $returnUrl . $separator . http_build_query($redirectParams);
    
    ob_clean();
    header("Location: " . $finalUrl);
    exit;
}
?>