<?php
/**
 * Simple Path Test Tool
 * Quick verification that paths are working correctly
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Path Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .test-result.pass { background: #d4edda; border-left: 4px solid #28a745; }
        .test-result.fail { background: #f8d7da; border-left: 4px solid #dc3545; }
        code { background: #f1f3f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Quick Path Test</h1>

        <?php
        echo "<h2>📍 Current Location</h2>";
        echo "<p><strong>Tools directory:</strong> <code>" . __DIR__ . "</code></p>";
        echo "<p><strong>Parent directory:</strong> <code>" . dirname(__DIR__) . "</code></p>";

        echo "<h2>🔧 Configuration Test</h2>";

        // Test config path (server structure: root/tools)
        $configPath = __DIR__ . '/../config.php';
        if (file_exists($configPath)) {
            echo "<div class='test-result pass'>✅ <strong>Config found:</strong> <code>$configPath</code></div>";

            try {
                require_once $configPath;
                echo "<div class='test-result pass'>✅ <strong>Config loaded successfully</strong></div>";
                echo "<div class='test-result pass'>✅ <strong>Database:</strong> " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "</div>";
                echo "<div class='test-result pass'>✅ <strong>Host:</strong> " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "</div>";
            } catch (Exception $e) {
                echo "<div class='test-result fail'>❌ <strong>Config load failed:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div class='test-result fail'>❌ <strong>Config not found:</strong> <code>$configPath</code></div>";
        }

        echo "<h2>🗄️ Database Class Test</h2>";

        // Test database path (server structure: root/tools)
        $dbPath = __DIR__ . '/../includes/database.php';
        if (file_exists($dbPath)) {
            echo "<div class='test-result pass'>✅ <strong>Database file found:</strong> <code>$dbPath</code></div>";

            if (defined('DB_NAME')) {
                try {
                    require_once $dbPath;
                    $pdo = getDB();
                    echo "<div class='test-result pass'>✅ <strong>Database connection successful</strong></div>";

                    // Test a simple query
                    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' AND table_name LIKE 'dms_%'");
                    $result = $stmt->fetch();
                    echo "<div class='test-result pass'>✅ <strong>DMS Tables found:</strong> " . $result['table_count'] . "</div>";

                } catch (Exception $e) {
                    echo "<div class='test-result fail'>❌ <strong>Database connection failed:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            } else {
                echo "<div class='test-result fail'>❌ <strong>Cannot test database - config not loaded</strong></div>";
            }
        } else {
            echo "<div class='test-result fail'>❌ <strong>Database file not found:</strong> <code>$dbPath</code></div>";
        }

        echo "<h2>🎯 Status Summary</h2>";

        $configWorks = file_exists(__DIR__ . '/../config.php');
        $dbWorks = file_exists(__DIR__ . '/../includes/database.php');

        if ($configWorks && $dbWorks) {
            echo "<div class='test-result pass'>";
            echo "<strong>✅ All paths working correctly!</strong><br>";
            echo "Your database tools should now work properly.";
            echo "</div>";
            echo "<p><a href='web_schema_analyzer.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>🔍 Try Schema Analyzer</a></p>";
        } else {
            echo "<div class='test-result fail'>";
            echo "<strong>❌ Path issues detected</strong><br>";
            echo "Some files are missing or paths need adjustment.";
            echo "</div>";
        }
        ?>

        <hr style="margin: 30px 0;">
        <p style="text-align: center;">
            <a href="index.php">← Back to Database Tools</a> |
            <a href="path_diagnostic.php">🔍 Full Diagnostic</a>
        </p>
    </div>
</body>
</html>