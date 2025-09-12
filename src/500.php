<?php
/**
 * 500 Internal Server Error Page
 */

require_once 'config.php';

// Send 500 header
http_response_code(500);

// Get error details if available
$error_message = $_GET['error'] ?? 'An unexpected error occurred';
$error_code = $_GET['code'] ?? 'UNKNOWN';
$show_details = DEBUG_MODE && isset($_GET['details']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Dms Management</title>
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
        
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #D9534F;
            margin-bottom: 10px;
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            line-height: 1;
        }
        
        h1 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .message {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .error-details {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            text-align: left;
        }
        
        .error-id {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            color: #495057;
            margin: 20px 0;
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
        }
        
        .suggestions ul {
            margin-left: 20px;
        }
        
        .suggestions li {
            margin: 8px 0;
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
        
        .btn-secondary {
            background: #6c757d;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.5);
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
        
        <div class="error-code">500</div>
        
        <h1>Server Error</h1>
        
        <p class="message">
            Something went wrong on our end. We're sorry for the inconvenience and are working to fix the issue.
        </p>
        
        <?php if (!DEBUG_MODE): ?>
        <div class="error-id">
            Error ID: <?php echo $error_code; ?> - <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        <?php endif; ?>
        
        <?php if (DEBUG_MODE): ?>
        <div class="error-details">
            <strong>Debug Information:</strong><br>
            <?php echo htmlspecialchars($error_message); ?>
            <?php if ($show_details): ?>
                <br><br>
                <strong>Details:</strong><br>
                <?php echo htmlspecialchars($_GET['details']); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="suggestions">
            <h3>⚠️ What to do:</h3>
            <ul>
                <li>Try refreshing the page</li>
                <li>Go back and try again</li>
                <li>Contact support if the problem persists</li>
                <?php if (DEBUG_MODE): ?>
                <li>Check the error logs for more details</li>
                <li>Verify database connectivity</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <a href="dashboard.php" class="back-btn">
            🏠 Go to Dashboard
        </a>
        
        <a href="javascript:history.back()" class="back-btn btn-secondary">
            ← Go Back
        </a>
        
        <a href="javascript:location.reload()" class="back-btn btn-secondary">
            🔄 Refresh Page
        </a>
        
        <div class="footer">
            <p>Powered by KaizenFlow Platform</p>
            <p>&copy; <?php echo date('Y'); ?> KaizenFlow Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>