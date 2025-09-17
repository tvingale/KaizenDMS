<?php
/**
 * KaizenDMS Database Tools Launcher
 * Central hub for all database management and analysis tools
 */

// Set content type for web browser
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaizenDMS Database Tools</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .tool-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        .tool-card h3 {
            color: #007bff;
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .tool-card p {
            color: #666;
            margin: 0 0 15px 0;
            line-height: 1.5;
        }
        .tool-card .features {
            list-style: none;
            padding: 0;
            margin: 0 0 15px 0;
        }
        .tool-card .features li {
            color: #555;
            font-size: 14px;
            margin: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        .tool-card .features li:before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        .tool-button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .tool-button:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }
        .tool-button.primary { background: #007bff; }
        .tool-button.success { background: #28a745; }
        .tool-button.warning { background: #ffc107; color: #212529; }
        .tool-button.info { background: #17a2b8; }
        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-indicator.available { background: #d4edda; color: #155724; }
        .status-indicator.recommended { background: #fff3cd; color: #856404; }
        .status-indicator.advanced { background: #d1ecf1; color: #0c5460; }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        .intro {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .intro h2 {
            color: #495057;
            margin: 0 0 10px 0;
        }
        .intro p {
            color: #6c757d;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ KaizenDMS Database Tools</h1>

        <div class="intro">
            <h2>Database Management & Analysis Suite</h2>
            <p>Comprehensive tools for database setup, analysis, and maintenance. Choose the tool that matches your current needs.</p>
        </div>

        <div class="tools-grid">
            <!-- Schema Analyzer -->
            <div class="tool-card" onclick="window.open('web_schema_analyzer.php', '_blank')">
                <h3>🔍 Database Schema Analyzer <span class="status-indicator recommended">Recommended</span></h3>
                <p>Comprehensive analysis of your actual database structure compared to documentation. Perfect for understanding what's really in your database.</p>
                <ul class="features">
                    <li>Complete table structure analysis</li>
                    <li>Documentation vs reality comparison</li>
                    <li>Foreign key and index mapping</li>
                    <li>Sample data preview</li>
                    <li>Missing table identification</li>
                    <li>Actionable recommendations</li>
                </ul>
                <a href="web_schema_analyzer.php" class="tool-button primary" target="_blank">Analyze Schema</a>
            </div>

            <!-- Basic Database Check -->
            <div class="tool-card" onclick="window.open('web_db_check.php', '_blank')">
                <h3>🔗 Database Connection Check <span class="status-indicator available">Available</span></h3>
                <p>Quick health check for database connectivity and basic table verification. Start here if you're having connection issues.</p>
                <ul class="features">
                    <li>Test database connection</li>
                    <li>Verify configuration files</li>
                    <li>Basic table presence check</li>
                    <li>Environment validation</li>
                    <li>Quick troubleshooting</li>
                </ul>
                <a href="web_db_check.php" class="tool-button success" target="_blank">Check Connection</a>
            </div>

            <!-- Table Deployment -->
            <div class="tool-card" onclick="window.open('web_deploy_tables.php', '_blank')">
                <h3>🚀 Table Deployment Tool <span class="status-indicator available">Available</span></h3>
                <p>Deploy missing database tables and initial data. Use this to set up your database structure from scratch.</p>
                <ul class="features">
                    <li>Deploy master tables</li>
                    <li>Insert sample data</li>
                    <li>RBAC system setup</li>
                    <li>Safe deployment process</li>
                    <li>Rollback capabilities</li>
                </ul>
                <a href="web_deploy_tables.php" class="tool-button warning" target="_blank">Deploy Tables</a>
            </div>

            <!-- Command Line Analyzer -->
            <div class="tool-card">
                <h3>💻 Command Line Analyzer <span class="status-indicator advanced">Advanced</span></h3>
                <p>Advanced command-line database analysis tool for developers. Provides detailed output for technical analysis.</p>
                <ul class="features">
                    <li>Detailed console output</li>
                    <li>Export capabilities</li>
                    <li>Batch processing</li>
                    <li>Advanced SQL analysis</li>
                    <li>Performance metrics</li>
                </ul>
                <div style="margin-top: 10px;">
                    <code style="background: #f1f3f4; padding: 5px 8px; border-radius: 4px; font-size: 12px;">
                        php database_schema_analyzer.php
                    </code>
                </div>
            </div>

            <!-- Direct Database Access -->
            <div class="tool-card">
                <h3>🗄️ Direct Database Access <span class="status-indicator advanced">Advanced</span></h3>
                <p>For experienced users who need direct database access. Use your preferred database management tool.</p>
                <ul class="features">
                    <li>phpMyAdmin interface</li>
                    <li>MySQL Workbench</li>
                    <li>Command line mysql client</li>
                    <li>Custom SQL queries</li>
                    <li>Advanced administration</li>
                </ul>
                <div style="margin-top: 10px;">
                    <strong>Connection Details:</strong><br>
                    <small style="color: #666;">
                        Host: <?= defined('DB_HOST') ? DB_HOST : 'Check config.php' ?><br>
                        Database: <?= defined('DB_NAME') ? DB_NAME : 'Check config.php' ?><br>
                        Port: <?= defined('DB_PORT') ? DB_PORT : '3306' ?>
                    </small>
                </div>
            </div>

            <!-- Documentation -->
            <div class="tool-card" onclick="window.open('../database_structure.md', '_blank')">
                <h3>📖 Database Documentation <span class="status-indicator available">Reference</span></h3>
                <p>Current database structure documentation. Compare this with actual schema using the analyzer tool above.</p>
                <ul class="features">
                    <li>Documented table structures</li>
                    <li>RBAC system overview</li>
                    <li>Master data definitions</li>
                    <li>Implementation status</li>
                    <li>Future expansion plans</li>
                </ul>
                <a href="../database_structure.md" class="tool-button info" target="_blank">View Documentation</a>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 30px 0;">
            <h3 style="color: #495057; margin: 0 0 15px 0;">🎯 Quick Start Guide</h3>
            <ol style="color: #6c757d; line-height: 1.6;">
                <li><strong>First Time Setup:</strong> Use "Database Connection Check" to verify your setup</li>
                <li><strong>Understand Your Database:</strong> Run "Database Schema Analyzer" to see what you actually have</li>
                <li><strong>Missing Tables:</strong> Use "Table Deployment Tool" to create missing structures</li>
                <li><strong>Regular Maintenance:</strong> Periodically run the Schema Analyzer to track changes</li>
                <li><strong>Documentation:</strong> Update database_structure.md based on analyzer recommendations</li>
            </ol>
        </div>

        <?php
        // Try to load config and show current status
        $configLoaded = false;
        $possibleConfigPaths = [
            __DIR__ . '/../config.php',      // Server structure (tools in root/tools, config in root)
            __DIR__ . '/../src/config.php',  // Development structure
            __DIR__ . '/config.php',         // Same directory
            dirname(__DIR__) . '/config.php' // Parent directory
        ];

        foreach ($possibleConfigPaths as $configFile) {
            if (file_exists($configFile)) {
                try {
                    require_once $configFile;
                    $configLoaded = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        ?>

        <div style="background: <?= $configLoaded ? '#d4edda' : '#f8d7da' ?>; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <strong><?= $configLoaded ? '✅ Configuration Status: Loaded' : '❌ Configuration Status: Failed' ?></strong><br>
            <?php if ($configLoaded): ?>
                <small style="color: #155724;">
                    Database: <?= DB_NAME ?> | Host: <?= DB_HOST ?> | RBAC: <?= ENABLE_RBAC ? 'Enabled' : 'Disabled' ?>
                </small>
            <?php else: ?>
                <small style="color: #721c24;">
                    Cannot load configuration. Please check your config.php and .env files.
                </small>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p><strong>KaizenDMS Database Tools</strong> | Generated at <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>Need help? Check the documentation or contact your system administrator.</p>
        </div>
    </div>

    <script>
        // Auto-refresh configuration status every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>