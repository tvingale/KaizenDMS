<?php
/**
 * Web-based DMS Database Status Checker
 * Access via browser: http://your-domain.com/web_db_check.php
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
    <title>KaizenDMS Database Check</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; min-height: 100vh; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); max-width: 900px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .status-line { margin: 8px 0; padding: 8px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; }
        .status-line.success { border-left-color: #28a745; background: #d4edda; }
        .status-line.error { border-left-color: #dc3545; background: #f8d7da; }
        .status-line.warning { border-left-color: #ffc107; background: #fff3cd; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 15px; margin-bottom: 25px; }
        h2 { color: #555; margin: 25px 0 15px 0; padding: 10px; background: #e9ecef; border-radius: 6px; }
        .next-steps { background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 2px solid #28a745; padding: 20px; border-radius: 8px; margin-top: 25px; }
        .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; font-weight: bold; transition: background 0.3s; }
        .button:hover { background: #0056b3; }
        .button.deploy { background: #28a745; }
        .button.deploy:hover { background: #1e7e34; }
        pre { background: #f1f3f4; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 KaizenDMS Database Status Check</h1>
        
        <?php
        
        // Step 1: Check if files exist
        echo '<h2>📁 File Check</h2>';
        $configFile = __DIR__ . '/config.php';
        $dbFile = __DIR__ . '/includes/database.php';
        $envFile = __DIR__ . '/.env';
        
        $files = [
            'config.php' => $configFile,
            'database.php' => $dbFile,
            '.env file' => $envFile
        ];
        
        $filesOk = true;
        foreach ($files as $name => $path) {
            $exists = file_exists($path);
            $class = $exists ? 'success' : 'error';
            $icon = $exists ? '✅' : '❌';
            echo "<div class='status-line $class'>$icon $name: " . ($exists ? 'Found' : 'Missing') . "</div>";
            if (!$exists) $filesOk = false;
        }
        
        if (!$filesOk) {
            echo '<div class="next-steps"><strong>❌ Missing required files.</strong><br>Please ensure all required files are uploaded to the server.</div>';
            echo '</div></body></html>';
            exit;
        }
        
        // Step 2: Load configuration
        echo '<h2>⚙️ Configuration Loading</h2>';
        try {
            require_once $configFile;
            echo "<div class='status-line success'>✅ Configuration loaded successfully</div>";
            
            // Check constants
            $constants = [
                'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'Not defined',
                'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'Not defined', 
                'DB_USER' => defined('DB_USER') ? DB_USER : 'Not defined',
                'DB_PASS' => defined('DB_PASS') ? str_repeat('*', strlen(DB_PASS)) : 'Not defined',
                'DB_PORT' => defined('DB_PORT') ? DB_PORT : 'Not defined'
            ];
            
            foreach ($constants as $name => $value) {
                $class = ($value !== 'Not defined') ? 'success' : 'error';
                $icon = ($value !== 'Not defined') ? '✅' : '❌';
                echo "<div class='status-line $class'>$icon $name: $value</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='status-line error'>❌ Configuration loading failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo '</div></body></html>';
            exit;
        }
        
        // Step 3: Load database class
        echo '<h2>🗄️ Database Class Loading</h2>';
        try {
            require_once $dbFile;
            echo "<div class='status-line success'>✅ Database class loaded</div>";
        } catch (Exception $e) {
            echo "<div class='status-line error'>❌ Database class loading failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo '</div></body></html>';
            exit;
        }
        
        // Step 4: Test database connection
        echo '<h2>🔗 Database Connection Test</h2>';
        try {
            $pdo = getDB();
            echo "<div class='status-line success'>✅ Database connection successful</div>";
            
            // Test a simple query
            $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
            $result = $stmt->fetch();
            echo "<div class='status-line success'>✅ Current database: " . htmlspecialchars($result['current_db']) . "</div>";
            echo "<div class='status-line info'>ℹ️ MySQL version: " . htmlspecialchars($result['mysql_version']) . "</div>";
            
        } catch (Exception $e) {
            echo "<div class='status-line error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            
            // Provide specific error guidance
            $message = $e->getMessage();
            if (strpos($message, 'Connection refused') !== false) {
                echo "<div class='status-line warning'>💡 Possible causes: Database server not running, wrong host/port</div>";
            } elseif (strpos($message, 'Access denied') !== false) {
                echo "<div class='status-line warning'>💡 Possible causes: Wrong username/password, user doesn't have access</div>";
            } elseif (strpos($message, 'Unknown database') !== false) {
                echo "<div class='status-line warning'>💡 Possible causes: Database doesn't exist, wrong database name</div>";
            }
            
            echo '</div></body></html>';
            exit;
        }
        
        // Step 5: Check for DMS tables
        echo '<h2>📋 DMS Tables Check</h2>';
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'dms_%'");
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo "<div class='status-line warning'>⚠️ No DMS tables found</div>";
                echo "<div class='status-line info'>💡 Master tables need to be created</div>";
            } else {
                echo "<div class='status-line success'>✅ Found " . count($tables) . " DMS tables:</div>";
                echo "<table>";
                echo "<tr><th>Table Name</th><th>Rows</th><th>Purpose</th></tr>";
                
                foreach ($tables as $table) {
                    try {
                        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                        $count = $countStmt->fetch()['count'];
                        
                        // Determine table purpose
                        $purpose = 'Unknown';
                        if (strpos($table, 'site') !== false) $purpose = 'Site Management';
                        elseif (strpos($table, 'department') !== false) $purpose = 'Department Structure';
                        elseif (strpos($table, 'customer') !== false) $purpose = 'Customer Data';
                        elseif (strpos($table, 'supplier') !== false) $purpose = 'Supplier Management';
                        elseif (strpos($table, 'process') !== false) $purpose = 'Process Classification';
                        elseif (strpos($table, 'document') !== false) $purpose = 'Document Management';
                        elseif (strpos($table, 'language') !== false) $purpose = 'Multi-language Support';
                        elseif (strpos($table, 'review') !== false) $purpose = 'Review Scheduling';
                        elseif (strpos($table, 'notification') !== false) $purpose = 'Notification System';
                        elseif (strpos($table, 'role') !== false) $purpose = 'Access Control';
                        elseif (strpos($table, 'permission') !== false) $purpose = 'Permission Management';
                        elseif (strpos($table, 'activity') !== false) $purpose = 'Activity Logging';
                        elseif (strpos($table, 'setting') !== false) $purpose = 'System Settings';
                        elseif (strpos($table, 'categor') !== false) $purpose = 'Category Management';
                        
                        echo "<tr><td><strong>$table</strong></td><td>$count</td><td>$purpose</td></tr>";
                    } catch (Exception $e) {
                        echo "<tr><td><strong>$table</strong></td><td>Error</td><td>Cannot access table</td></tr>";
                    }
                }
                echo "</table>";
            }
            
            // Check for required master tables
            echo '<h2>🎯 Required Master Tables Check</h2>';
            $requiredTables = [
                'dms_sites' => 'Site/location management',
                'dms_departments' => 'Department structure',
                'dms_customers' => 'Customer data management',
                'dms_suppliers' => 'Supplier qualification',
                'dms_process_areas' => 'Universal process classification',
                'dms_document_types' => 'Document classification & numbering',
                'dms_languages' => 'Multi-language support',
                'dms_review_cycles' => 'Periodic review scheduling',
                'dms_notification_templates' => 'Message templates',
                'dms_notification_channels' => 'Communication channels'
            ];
            
            $missing = [];
            echo "<table>";
            echo "<tr><th>Required Table</th><th>Status</th><th>Purpose</th></tr>";
            
            foreach ($requiredTables as $tableName => $description) {
                if (in_array($tableName, $tables)) {
                    echo "<tr><td><strong>$tableName</strong></td><td><span class='success'>✅ Exists</span></td><td>$description</td></tr>";
                } else {
                    echo "<tr><td><strong>$tableName</strong></td><td><span class='error'>❌ Missing</span></td><td>$description</td></tr>";
                    $missing[] = $tableName;
                }
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<div class='status-line error'>❌ Failed to check tables: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Step 6: Summary and next steps
        echo '<h2>📊 Summary</h2>';
        echo "<div class='status-line info'>🗄️ Database connection: " . (isset($pdo) ? '<span class="success">✅ Working</span>' : '<span class="error">❌ Failed</span>') . "</div>";
        echo "<div class='status-line info'>📋 Total DMS tables: " . (isset($tables) ? count($tables) : 'Unknown') . "</div>";
        echo "<div class='status-line info'>❌ Missing master tables: " . (isset($missing) ? count($missing) : 'Unknown') . "</div>";
        
        if (isset($pdo) && isset($missing)) {
            if (count($missing) > 0) {
                echo '<div class="next-steps">';
                echo '<h3>💡 Next Steps Required</h3>';
                echo '<p><strong>You need to create ' . count($missing) . ' missing master tables.</strong></p>';
                echo '<p>Click the button below to deploy the missing master tables:</p>';
                echo '<a href="web_deploy_tables.php" class="button deploy">🚀 Deploy Master Tables</a>';
                echo '<a href="#" onclick="location.reload()" class="button">🔄 Refresh Status</a>';
                echo '</div>';
            } else {
                echo '<div class="next-steps">';
                echo '<h3>🎉 All Required Tables Present!</h3>';
                echo '<p>Your DMS database is fully set up and ready for use.</p>';
                echo '<p>You can now proceed with DMS application development.</p>';
                echo '<a href="web_table_manager.php" class="button">📋 Manage Tables</a>';
                echo '<a href="#" onclick="location.reload()" class="button">🔄 Refresh Status</a>';
                echo '</div>';
            }
        }
        
        ?>
        
        <div class="footer">
            <p>KaizenDMS Database Status Check | Generated at <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><a href="web_deploy_tables.php">Deploy Tables</a> | <a href="web_table_manager.php">Table Manager</a></p>
        </div>
    </div>
</body>
</html>