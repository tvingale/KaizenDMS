<?php
/**
 * Simple DMS Database Status Checker
 * Minimal version to diagnose issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for web browser
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS Database Check</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .status-line { margin: 5px 0; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 25px; }
        .next-steps { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Simple DMS Database Check</h1>
        <div style="border-bottom: 2px solid #ccc; margin-bottom: 20px;"></div>
        
        <?php
        
echo '<h2>📁 File Check:</h2>';<?php

// Step 1: Check if files exist
echo "\n📁 File Check:\n";
$configFile = __DIR__ . '/config.php';
$dbFile = __DIR__ . '/includes/database.php';
$envFile = __DIR__ . '/.env';

echo "   config.php: " . (file_exists($configFile) ? "✅ Found" : "❌ Missing") . "\n";
echo "   database.php: " . (file_exists($dbFile) ? "✅ Found" : "❌ Missing") . "\n";
echo "   .env file: " . (file_exists($envFile) ? "✅ Found" : "❌ Missing") . "\n";

if (!file_exists($configFile) || !file_exists($dbFile) || !file_exists($envFile)) {
    echo "\n❌ Missing required files. Please check file paths.\n";
    exit(1);
}

// Step 2: Load configuration
echo "\n⚙️ Configuration Loading:\n";
try {
    require_once $configFile;
    echo "   ✅ Config loaded successfully\n";
    
    // Check constants
    $requiredConstants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
    foreach ($requiredConstants as $const) {
        if (defined($const)) {
            $value = constant($const);
            if ($const === 'DB_PASS') {
                echo "   ✅ $const: " . str_repeat('*', strlen($value)) . "\n";
            } else {
                echo "   ✅ $const: $value\n";
            }
        } else {
            echo "   ❌ $const: Not defined\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Config loading failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Load database class
echo "\n🗄️ Database Class Loading:\n";
try {
    require_once $dbFile;
    echo "   ✅ Database class loaded\n";
} catch (Exception $e) {
    echo "   ❌ Database class loading failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Test database connection
echo "\n🔗 Database Connection Test:\n";
try {
    $pdo = getDB();
    echo "   ✅ Database connection successful\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "   ✅ Current database: " . $result['current_db'] . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    
    // Try to provide more specific error info
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "   💡 Possible causes: Database server not running, wrong host/port\n";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "   💡 Possible causes: Wrong username/password, user doesn't have access\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "   💡 Possible causes: Database doesn't exist, wrong database name\n";
    }
    
    exit(1);
}

// Step 5: Check for DMS tables
echo "\n📋 DMS Tables Check:\n";
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'dms_%'");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "   ❌ No DMS tables found\n";
        echo "   💡 Run deployment script to create tables\n";
    } else {
        echo "   ✅ Found " . count($tables) . " DMS tables:\n";
        foreach ($tables as $table) {
            // Get row count for each table
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countStmt->fetch()['count'];
                echo "      - $table ($count rows)\n";
            } catch (Exception $e) {
                echo "      - $table (error getting count)\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Failed to check tables: " . $e->getMessage() . "\n";
}

// Step 6: Summary
echo "\n📊 Summary:\n";
echo "   Database connection: " . (isset($pdo) ? "✅ Working" : "❌ Failed") . "\n";
echo "   DMS tables: " . (isset($tables) ? count($tables) . " found" : "Unknown") . "\n";

if (isset($pdo) && isset($tables) && count($tables) < 10) {
    echo "\n💡 Next steps:\n";
    echo "   1. Run: php deploy_dms_tables.php\n";
    echo "   2. This will create the missing master tables\n";
} elseif (isset($pdo) && isset($tables) && count($tables) >= 10) {
    echo "\n🎉 All looks good! Your DMS database setup is ready.\n";
}

echo "\n✨ Check completed.\n";
?>