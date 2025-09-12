<?php
/**
 * Access Denied Page
 * Shown when KaizenAuth user doesn't have access to this module
 */

require_once 'config.php';
require_once 'includes/database.php';
require_once 'includes/kaizen_sso.php';

// Get the reason for access denial
$reason = $_GET['reason'] ?? 'no_access';

// Initialize SSO to get user info (they should be authenticated)
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);
$user = null;
$username = $_GET['user'] ?? 'Unknown User';

if ($sso->isAuthenticated()) {
    $user = $sso->getUserInfo();
    $username = $user['name'] ?? $user['username'] ?? 'Unknown User';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Dms Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #D9534F 0%, #c9302c 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
            width: 100%;
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 0 auto 30px;
        }
        
        .logo-image {
            height: 50px;
            width: auto;
            filter: drop-shadow(0 4px 8px rgba(217, 83, 79, 0.2));
        }
        
        .logo-text {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        
        .access-denied-icon {
            font-size: 80px;
            color: #D9534F;
            margin-bottom: 20px;
        }
        
        h1 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            color: #D9534F;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .username {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            color: #495057;
            margin: 20px 0;
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
        }
        
        .message {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .instructions {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #0c4a6e;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
        }
        
        .instructions h3 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 15px;
        }
        
        .instructions ol {
            margin-left: 20px;
        }
        
        .instructions li {
            margin: 8px 0;
        }
        
        .contact-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, #D9534F, #c9302c);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(217, 83, 79, 0.4);
            margin: 10px;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(217, 83, 79, 0.5);
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="assets/images/kaizenflowlogo.png" alt="KaizenFlow" class="logo-image">
            <h2 class="logo-text">Dms Management</h2>
        </div>
        
        <div class="access-denied-icon">🚫</div>
        
        <h1>Access Denied</h1>
        
        <div class="username">User: <?php echo htmlspecialchars($username); ?></div>
        
        <?php if ($reason === 'no_module_access'): ?>
            <p class="message">
                Your KaizenAuth account doesn't have access to the <strong>Dms Management</strong> module. 
                You need to request access from your system administrator.
            </p>
        <?php elseif ($reason === 'insufficient_role'): ?>
            <p class="message">
                You have access to Dms Management, but your current role doesn't have permission 
                to view this page. Contact your administrator if you need elevated permissions.
            </p>
        <?php else: ?>
            <p class="message">
                You don't have permission to access the Dms Management module. 
                Only authorized users who have been granted access by the module administrator can use this application.
            </p>
        <?php endif; ?>
        
        <div class="instructions">
            <h3>📋 To Request Access:</h3>
            <ol>
                <li><strong>Contact the module administrator</strong> who can grant you access to Dms Management</li>
                <li><strong>Provide your username:</strong> <code><?php echo htmlspecialchars($username); ?></code></li>
                <li><strong>Explain why you need access</strong> to this module</li>
                <li><strong>Wait for approval</strong> - you'll be notified when access is granted</li>
            </ol>
        </div>
        
        <div class="contact-info">
            <strong>💡 Note:</strong> This is a security feature. Each KaizenFlow module controls its own user access independently from the main KaizenAuth system.
        </div>
        
        <a href="<?php echo KAIZEN_AUTH_URL; ?>/apps" class="back-btn">
            ← Back to KaizenAuth Apps
        </a>
        
        <?php if ($user): ?>
            <a href="logout.php" class="back-btn">
                Logout
            </a>
        <?php endif; ?>
        
        <div class="footer">
            <p>Powered by KaizenAuth SSO</p>
            <p>&copy; <?php echo date('Y'); ?> KaizenFlow Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>