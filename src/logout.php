<?php
/**
 * Logout Page
 * Handles user logout from the module
 */

require_once 'config.php';
require_once 'includes/kaizen_sso.php';

// Initialize SSO
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);

// Get user info if available
$user = null;
$username = 'User';
if ($sso->isAuthenticated()) {
    $user = $sso->getUserInfo();
    $username = $user['name'] ?? $user['username'] ?? 'User';
}

// Clear session
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Dms Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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
            max-width: 500px;
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
            height: 60px;
            width: auto;
            filter: drop-shadow(0 4px 8px rgba(217, 83, 79, 0.2));
        }
        
        .logo-text {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            font-size: 28px;
            color: #333;
            margin: 0;
        }
        
        .logout-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        h1 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            font-size: 32px;
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
            font-size: 18px;
            line-height: 1.6;
        }
        
        .next-steps {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #0c4a6e;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
        }
        
        .next-steps h3 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .next-steps ul {
            margin-left: 20px;
        }
        
        .next-steps li {
            margin: 8px 0;
        }
        
        .login-btn {
            display: inline-block;
            background: linear-gradient(135deg, #D9534F, #c9302c);
            color: white;
            padding: 18px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(217, 83, 79, 0.4);
            margin: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(217, 83, 79, 0.5);
        }
        
        .btn-secondary {
            background: #6c757d;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.5);
        }
        
        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 20px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 14px;
        }
    </style>
    <!-- Auto-redirect after 10 seconds -->
    <script>
        let countdown = 10;
        function updateCountdown() {
            document.getElementById('countdown').textContent = countdown;
            countdown--;
            if (countdown < 0) {
                window.location.href = 'index.php';
            }
        }
        setInterval(updateCountdown, 1000);
        window.onload = updateCountdown;
    </script>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="assets/images/kaizenflowlogo.png" alt="KaizenFlow" class="logo-image">
            <h2 class="logo-text">Dms Management</h2>
        </div>
        
        <div class="logout-icon">👋</div>
        
        <h1>Goodbye!</h1>
        
        <div class="username">Logged out: <?php echo htmlspecialchars($username); ?></div>
        
        <p class="message">
            You have been successfully logged out of Dms Management. 
            Thank you for using KaizenFlow!
        </p>
        
        <div class="next-steps">
            <h3>🔄 What's next?</h3>
            <ul>
                <li>You'll be redirected to the home page in <strong><span id="countdown">10</span></strong> seconds</li>
                <li>Your session data has been cleared for security</li>
                <li>You can login again anytime with KaizenAuth</li>
            </ul>
        </div>
        
        <a href="index.php" class="login-btn">
            🏠 Back to Home
        </a>
        
        <a href="sso.php" class="login-btn btn-secondary">
            🔑 Login Again
        </a>
        
        <div class="secure-badge">
            <span>🔒</span>
            <span>Secure Logout</span>
        </div>
        
        <div class="footer">
            <p>Powered by KaizenAuth SSO</p>
            <p>&copy; <?php echo date('Y'); ?> KaizenFlow Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>