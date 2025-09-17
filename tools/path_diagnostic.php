<?php
/**
 * Path Diagnostic Tool
 * Helps identify the correct file structure on the server
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaizenDMS Path Diagnostic</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .path-check { margin: 15px 0; padding: 15px; border-radius: 8px; }
        .path-check.found { background: #d4edda; border-left: 5px solid #28a745; }
        .path-check.not-found { background: #f8d7da; border-left: 5px solid #dc3545; }
        .path-check.dir { background: #e2e3e5; border-left: 5px solid #6c757d; }
        code { background: #f1f3f4; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 KaizenDMS Path Diagnostic Tool</h1>

        <h2>📍 Current Location Information</h2>
        <table>
            <tr><th>Item</th><th>Value</th></tr>
            <tr><td><strong>Current Directory</strong></td><td><code><?= __DIR__ ?></code></td></tr>
            <tr><td><strong>Parent Directory</strong></td><td><code><?= dirname(__DIR__) ?></code></td></tr>
            <tr><td><strong>Document Root</strong></td><td><code><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Not available' ?></code></td></tr>
            <tr><td><strong>Script Name</strong></td><td><code><?= $_SERVER['SCRIPT_NAME'] ?? 'Not available' ?></code></td></tr>
            <tr><td><strong>Request URI</strong></td><td><code><?= $_SERVER['REQUEST_URI'] ?? 'Not available' ?></code></td></tr>
            <tr><td><strong>Server Name</strong></td><td><code><?= $_SERVER['SERVER_NAME'] ?? 'Not available' ?></code></td></tr>
        </table>

        <h2>🗂️ Directory Structure Analysis</h2>

        <?php
        function checkPath($path, $label) {
            $isDir = is_dir($path);
            $exists = file_exists($path);

            if ($isDir) {
                echo "<div class='path-check dir'>📁 <strong>$label:</strong> <code>$path</code> - <span class='info'>Directory exists</span></div>";

                // List contents if it's a directory
                $contents = scandir($path);
                $filteredContents = array_filter($contents, function($item) {
                    return !in_array($item, ['.', '..']) && !startsWith($item, '.');
                });

                if (!empty($filteredContents)) {
                    echo "<div style='margin-left: 20px; font-size: 13px; color: #666;'>";
                    echo "Contents: " . implode(', ', array_slice($filteredContents, 0, 10));
                    if (count($filteredContents) > 10) echo " ... (" . count($filteredContents) . " total)";
                    echo "</div>";
                }

            } elseif ($exists) {
                echo "<div class='path-check found'>✅ <strong>$label:</strong> <code>$path</code> - <span class='success'>File exists</span></div>";
            } else {
                echo "<div class='path-check not-found'>❌ <strong>$label:</strong> <code>$path</code> - <span class='error'>Not found</span></div>";
            }
        }

        function startsWith($string, $startString) {
            $len = strlen($startString);
            return (substr($string, 0, $len) === $startString);
        }

        // Check current directory structure
        checkPath(__DIR__, 'Current tools directory');
        checkPath(dirname(__DIR__), 'Parent directory');

        echo "<h3>📄 Looking for config.php</h3>";
        $configPaths = [
            __DIR__ . '/../src/config.php' => 'Development structure (../src/config.php)',
            __DIR__ . '/../config.php' => 'Production structure (../config.php)',
            __DIR__ . '/config.php' => 'Same directory (./config.php)',
            dirname(__DIR__) . '/config.php' => 'Parent directory',
            $_SERVER['DOCUMENT_ROOT'] . '/config.php' => 'Document root',
            dirname(__DIR__) . '/src/config.php' => 'Parent/src directory'
        ];

        foreach ($configPaths as $path => $label) {
            checkPath($path, $label);
        }

        echo "<h3>🗄️ Looking for database.php</h3>";
        $dbPaths = [
            __DIR__ . '/../src/includes/database.php' => 'Development structure (../src/includes/database.php)',
            __DIR__ . '/../includes/database.php' => 'Production structure (../includes/database.php)',
            __DIR__ . '/includes/database.php' => 'Same directory (./includes/database.php)',
            dirname(__DIR__) . '/includes/database.php' => 'Parent directory includes',
            $_SERVER['DOCUMENT_ROOT'] . '/includes/database.php' => 'Document root includes'
        ];

        foreach ($dbPaths as $path => $label) {
            checkPath($path, $label);
        }

        echo "<h3>📁 Key Directories Check</h3>";
        $directories = [
            __DIR__ . '/../src' => 'src directory',
            __DIR__ . '/../includes' => 'includes directory',
            __DIR__ . '/../admin' => 'admin directory',
            __DIR__ . '/../api' => 'api directory',
            dirname(__DIR__) . '/src' => 'Parent/src directory',
            dirname(__DIR__) . '/includes' => 'Parent/includes directory'
        ];

        foreach ($directories as $path => $label) {
            checkPath($path, $label);
        }
        ?>

        <h2>🔧 Recommended Fix</h2>

        <?php
        // Try to find the actual config file
        $foundConfig = null;
        foreach ($configPaths as $path => $label) {
            if (file_exists($path)) {
                $foundConfig = $path;
                break;
            }
        }

        if ($foundConfig) {
            echo "<div class='path-check found'>";
            echo "<strong>✅ Config file found at:</strong> <code>$foundConfig</code><br>";
            echo "<strong>Recommended solution:</strong> Update the path resolution in your tools to use this path.";
            echo "</div>";

            // Try to load it and show some info
            try {
                $configContent = file_get_contents($foundConfig);
                if (strpos($configContent, 'DB_HOST') !== false) {
                    echo "<div style='margin-top: 10px;'>";
                    echo "<strong>✅ Config file appears valid</strong> (contains DB_HOST)";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div style='margin-top: 10px; color: #ffc107;'>";
                echo "<strong>⚠️ Config file found but couldn't read contents</strong>";
                echo "</div>";
            }
        } else {
            echo "<div class='path-check not-found'>";
            echo "<strong>❌ No config file found in any expected location</strong><br>";
            echo "<strong>Possible solutions:</strong><br>";
            echo "1. Upload config.php to one of the expected locations<br>";
            echo "2. Check if the file has a different name<br>";
            echo "3. Verify file permissions";
            echo "</div>";
        }
        ?>

        <h2>📝 Summary</h2>
        <div style="background: #e9ecef; padding: 20px; border-radius: 8px;">
            <p><strong>Based on this analysis:</strong></p>
            <ol>
                <li>Your current tools directory is: <code><?= __DIR__ ?></code></li>
                <li>Your server appears to be using a
                    <?php
                    if (file_exists(__DIR__ . '/../src')) {
                        echo "<strong>development-style structure</strong> (separate src folder)";
                    } elseif (file_exists(__DIR__ . '/../config.php')) {
                        echo "<strong>production-style structure</strong> (files in root)";
                    } else {
                        echo "<strong>custom structure</strong>";
                    }
                    ?>
                </li>
                <li>The path resolution in the tools should work now with the updated multiple-path checking</li>
            </ol>

            <p><strong>If you're still getting errors:</strong></p>
            <ul>
                <li>Check file permissions (should be readable by web server)</li>
                <li>Verify the .env file exists and is readable</li>
                <li>Make sure the database credentials are correct</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">← Back to Database Tools</a>
        </div>

        <div style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
            <p>Path Diagnostic Tool | Generated at <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>