<?php
/**
 * 404 Not Found Page
 */

require_once 'config.php';

// Send 404 header
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Dms Management</title>
    <style>
        :root {
            /* Kaizen Design Tokens */
            --brand-primary: #C53A3A;
            --brand-primary-dark: #A72E2E;
            --neutral-100: #F6F7F8;
            --neutral-300: #E6E9EC;
            --neutral-600: #6B7280;
            --text-default: #111827;
            --white: #FFFFFF;
            --radius-lg: 12px;
            --shadow-soft: 0 6px 18px rgba(16,24,40,0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: var(--white);
            padding: 40px;
            border-radius: 16px;
            box-shadow: var(--shadow-soft);
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
            font-weight: 600;
            font-size: 20px;
            color: var(--text-default);
            margin: 0;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: var(--brand-primary);
            margin-bottom: 10px;
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            line-height: 1;
        }
        
        h1 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            color: var(--text-default);
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .message {
            color: var(--neutral-600);
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .url-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            color: #495057;
            margin: 20px 0;
            word-break: break-all;
        }
        
        .suggestions {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #0c4a6e;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
        }
        
        .suggestions h3 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .suggestions ul {
            margin-left: 20px;
        }
        
        .suggestions li {
            margin: 8px 0;
        }
        
        /* Kaizen Button Design */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--brand-primary);
            color: var(--white);
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-soft);
            margin: 8px;
            border: none;
            cursor: pointer;
        }
        
        .back-btn:hover {
            background: var(--brand-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(197, 58, 58, 0.3);
            text-decoration: none;
            color: var(--white);
        }
        
        .btn-secondary {
            background: var(--white);
            color: var(--text-default);
            border: 1px solid var(--neutral-300);
        }
        
        .btn-secondary:hover {
            background: var(--neutral-100);
            color: var(--text-default);
            text-decoration: none;
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
        
        <div class="error-code">404</div>
        
        <h1>Page Not Found</h1>
        
        <p class="message">
            Sorry, the page you're looking for doesn't exist. It may have been moved, deleted, or you entered the wrong URL.
        </p>
        
        <div class="url-info">
            Requested URL: <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/'); ?>
        </div>
        
        <div class="suggestions">
            <h3>💡 What you can try:</h3>
            <ul>
                <li>Check the URL for typos</li>
                <li>Go back to the previous page</li>
                <li>Visit the dashboard to find what you're looking for</li>
                <li>Contact support if you think this is an error</li>
            </ul>
        </div>
        
        <a href="dashboard.php" class="back-btn">
            🏠 Go to Dashboard
        </a>
        
        <a href="javascript:history.back()" class="back-btn btn-secondary">
            ← Go Back
        </a>
        
        <div class="footer">
            <p>Powered by KaizenFlow Platform</p>
            <p>&copy; <?php echo date('Y'); ?> KaizenFlow Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>