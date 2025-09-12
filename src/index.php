<?php
require_once 'config.php';
require_once 'includes/kaizen_sso.php';

// Initialize SSO
$ssoConfig = [
    'auth_domain' => KAIZEN_AUTH_URL,
    'app_id' => KAIZEN_APP_ID,
    'app_secret' => KAIZEN_APP_SECRET
];

$sso = new KaizenSSO($ssoConfig);

// Check if user is already logged in
if ($sso->isAuthenticated()) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaizenFlow - Dms Management | DMS is Kaizen's single source-of-truth for every work instruction, form, and drawing—auto-controlled, QR-driven, and IATF-ready. It automates approval, training, and shop-floor delivery so the right revision is always at the station and Safety-critical changes cannot slip through.</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #C53A3A 0%, #A72E2E 100%);
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
        
        h1 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            font-size: 32px;
        }
        
        .tagline {
            color: #666;
            margin-bottom: 40px;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .features {
            text-align: left;
            margin: 30px 0;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .feature {
            margin: 15px 0;
            display: flex;
            align-items: center;
            color: #555;
        }
        
        .feature-icon {
            width: 24px;
            margin-right: 15px;
            color: #D9534F;
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
            margin-top: 20px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(217, 83, 79, 0.5);
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="assets/images/kaizenflowlogo.png" alt="KaizenFlow" class="logo-image">
            <h2 class="logo-text">Dms Management</h2>
        </div>
        <h1>Welcome to KaizenFlow - Dms Management</h1>
        <p class="tagline">DMS is Kaizen's single source-of-truth for every work instruction, form, and drawing—auto-controlled, QR-driven, and IATF-ready. It automates approval, training, and shop-floor delivery so the right revision is always at the station and Safety-critical changes cannot slip through.</p>
        
        <div class="features">
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>Complete documents management system</span>
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>Role-based access control with admin panel</span>
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>Audit trail for all operations</span>
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>REST API for integrations</span>
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>Export and import functionality</span>
            </div>
            <div class="feature">
                <span class="feature-icon">✓</span>
                <span>Secure single sign-on with KaizenAuth</span>
            </div>
        </div>
        
        <a href="sso.php" class="login-btn">Login with KaizenAuth</a>
        
        <div class="secure-badge">
            <span>🔒</span>
            <span>Secure Authentication</span>
        </div>
        
        <div class="footer">
            <p>Powered by KaizenAuth SSO</p>
            <p>&copy; <?php echo date('Y'); ?> KaizenFlow Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>