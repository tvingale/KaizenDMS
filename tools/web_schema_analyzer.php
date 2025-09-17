<?php
/**
 * Web-based Database Schema Analyzer
 * Comprehensive analysis of actual database structure vs documentation
 */

// Set content type for web browser FIRST
header('Content-Type: text/html; charset=UTF-8');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaizenDMS Schema Analyzer</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; min-height: 100vh; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); max-width: 1200px; margin: 0 auto; }
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
        h3 { color: #666; margin: 20px 0 10px 0; }
        pre { background: #f1f3f4; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 14px; }
        table th, table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; }
        .table-detail { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .column-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; }
        .column-item { background: white; padding: 10px; border-radius: 4px; border-left: 3px solid #007bff; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        .toggle-detail { background: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .detail-section { display: none; margin-top: 15px; }
        .comparison-table { margin: 20px 0; }
        .comparison-table th { background: #e9ecef; }
        .nav-buttons { margin: 20px 0; text-align: center; }
        .nav-buttons a { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 0 5px; }
    </style>
    <script>
        function toggleDetail(id) {
            var element = document.getElementById(id);
            if (element.style.display === 'none' || element.style.display === '') {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>🔍 KaizenDMS Database Schema Analyzer</h1>

        <div class="nav-buttons">
            <a href="#overview">📊 Overview</a>
            <a href="#tables">📋 Tables</a>
            <a href="#comparison">🔍 Comparison</a>
            <a href="#recommendations">💡 Recommendations</a>
        </div>

        <?php

        // Load configuration - server structure: root/tools
        $possibleConfigPaths = [
            __DIR__ . '/../config.php',      // Server structure (tools in root/tools, config in root)
            __DIR__ . '/../src/config.php',  // Development structure
            __DIR__ . '/config.php',         // Same directory
            dirname(__DIR__) . '/config.php' // Parent directory
        ];

        $configLoaded = false;
        foreach ($possibleConfigPaths as $configFile) {
            if (file_exists($configFile)) {
                try {
                    require_once $configFile;
                    echo "<div class='status-line success'>✅ Configuration loaded from: " . basename($configFile) . "</div>";
                    $configLoaded = true;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        if (!$configLoaded) {
            echo "<div class='status-line error'>❌ Configuration file not found. Tried paths:</div>";
            foreach ($possibleConfigPaths as $path) {
                $exists = file_exists($path) ? 'EXISTS' : 'NOT FOUND';
                echo "<div class='status-line warning'>- " . htmlspecialchars($path) . " ($exists)</div>";
            }
            echo '</div></body></html>';
            exit;
        }

        // Load database class - server structure: root/tools
        $possibleDbPaths = [
            __DIR__ . '/../includes/database.php',      // Server structure (tools in root/tools, includes in root/includes)
            __DIR__ . '/../src/includes/database.php',  // Development structure
            __DIR__ . '/includes/database.php',         // Same directory
            dirname(__DIR__) . '/includes/database.php' // Parent directory
        ];

        $dbLoaded = false;
        foreach ($possibleDbPaths as $dbFile) {
            if (file_exists($dbFile)) {
                try {
                    require_once $dbFile;
                    $pdo = getDB();
                    echo "<div class='status-line success'>✅ Database connection established via: " . basename(dirname($dbFile)) . "/" . basename($dbFile) . "</div>";
                    $dbLoaded = true;
                    break;
                } catch (Exception $e) {
                    echo "<div class='status-line warning'>⚠️ Database file found but connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                    continue;
                }
            }
        }

        if (!$dbLoaded) {
            echo "<div class='status-line error'>❌ Database file not found or connection failed. Tried paths:</div>";
            foreach ($possibleDbPaths as $path) {
                $exists = file_exists($path) ? 'EXISTS' : 'NOT FOUND';
                echo "<div class='status-line warning'>- " . htmlspecialchars($path) . " ($exists)</div>";
            }
            echo '</div></body></html>';
            exit;
        }

        // Functions for database analysis
        function getDMSTables($pdo) {
            $query = "SHOW TABLES LIKE 'dms_%'";
            $stmt = $pdo->query($query);
            $tables = [];
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            sort($tables);
            return $tables;
        }

        function getTableStructure($pdo, $tableName) {
            $query = "DESCRIBE $tableName";
            $stmt = $pdo->query($query);
            return $stmt->fetchAll();
        }

        function getTableRowCount($pdo, $tableName) {
            try {
                $query = "SELECT COUNT(*) as count FROM $tableName";
                $stmt = $pdo->query($query);
                $result = $stmt->fetch();
                return $result['count'];
            } catch (Exception $e) {
                return 'Error';
            }
        }

        function getForeignKeys($pdo, $tableName) {
            $query = "
                SELECT
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([DB_NAME, $tableName]);
            return $stmt->fetchAll();
        }

        function getTableIndexes($pdo, $tableName) {
            try {
                $query = "SHOW INDEX FROM $tableName";
                $stmt = $pdo->query($query);
                return $stmt->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        }

        function getSampleData($pdo, $tableName, $limit = 3) {
            try {
                $query = "SELECT * FROM $tableName LIMIT $limit";
                $stmt = $pdo->query($query);
                return $stmt->fetchAll();
            } catch (Exception $e) {
                return [];
            }
        }

        // Get all DMS tables
        $actualTables = getDMSTables($pdo);

        // Read documented tables from database_structure.md
        $documentedTables = [];
        $dbStructurePath = __DIR__ . '/../database_structure.md';

        if (file_exists($dbStructurePath)) {
            $dbStructureContent = file_get_contents($dbStructurePath);

            // Parse table names and descriptions from the markdown
            if (preg_match_all('/- \*\*`(dms_[^`]+)`\*\* - ([^(]+)/', $dbStructureContent, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $tableName = $match[1];
                    $description = trim($match[2]);
                    $documentedTables[$tableName] = $description;
                }
            }
        } else {
            // Fallback to hardcoded list if file doesn't exist
            $documentedTables = [
                'dms_sites' => 'Site/Location Management',
                'dms_departments' => 'Department Structure',
                'dms_customers' => 'Customer Data Management',
                'dms_suppliers' => 'Supplier Qualification',
                'dms_process_areas' => 'Process Classification',
                'dms_document_types' => 'Document Type Catalog',
                'dms_languages' => 'Multi-language Support',
                'dms_review_cycles' => 'Review Scheduling',
                'dms_notification_templates' => 'Message Templates',
                'dms_notification_channels' => 'Communication Channels',
                'dms_roles' => 'Role Definitions',
                'dms_permissions' => 'Permission Catalog',
                'dms_role_permissions' => 'Role-Permission Mapping',
                'dms_user_roles' => 'User Role Assignments'
            ];
        }

        ?>

        <section id="overview">
            <h2>📊 Overview</h2>
            <table class="comparison-table">
                <tr>
                    <th>Metric</th>
                    <th>Count</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td><strong>Database Name</strong></td>
                    <td><?= DB_NAME ?></td>
                    <td><span class="info">Connected</span></td>
                </tr>
                <tr>
                    <td><strong>Total DMS Tables Found</strong></td>
                    <td><?= count($actualTables) ?></td>
                    <td><span class="success"><?= count($actualTables) > 0 ? 'Tables exist' : 'No tables' ?></span></td>
                </tr>
                <tr>
                    <td><strong>Documented Tables</strong></td>
                    <td><?= count($documentedTables) ?></td>
                    <td><span class="info">In documentation</span></td>
                </tr>
                <tr>
                    <td><strong>Missing from DB</strong></td>
                    <td><?= count(array_diff(array_keys($documentedTables), $actualTables)) ?></td>
                    <td><span class="warning">Need creation</span></td>
                </tr>
                <tr>
                    <td><strong>Extra in DB</strong></td>
                    <td><?= count(array_diff($actualTables, array_keys($documentedTables))) ?></td>
                    <td><span class="warning">Not documented</span></td>
                </tr>
            </table>
        </section>

        <section id="tables">
            <h2>📋 Actual Database Tables (<?= count($actualTables) ?>)</h2>

            <?php if (empty($actualTables)): ?>
                <div class="status-line warning">⚠️ No DMS tables found in database</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Rows</th>
                            <th>Columns</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actualTables as $table): ?>
                            <?php
                            $rowCount = getTableRowCount($pdo, $table);
                            $structure = getTableStructure($pdo, $table);
                            $columnCount = count($structure);
                            $isDocumented = isset($documentedTables[$table]);
                            $status = $isDocumented ? 'Documented' : 'Not documented';
                            $statusClass = $isDocumented ? 'success' : 'warning';
                            ?>
                            <tr>
                                <td><strong><?= $table ?></strong></td>
                                <td><?= $rowCount ?></td>
                                <td><?= $columnCount ?></td>
                                <td><span class="<?= $statusClass ?>"><?= $status ?></span></td>
                                <td>
                                    <button class="toggle-detail" onclick="toggleDetail('detail_<?= $table ?>')">
                                        View Structure
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5">
                                    <div id="detail_<?= $table ?>" class="detail-section">
                                        <div class="table-detail">
                                            <h3>📋 Table Structure: <?= $table ?></h3>

                                            <h4>🏗️ Columns (<?= $columnCount ?>)</h4>
                                            <div class="column-list">
                                                <?php foreach ($structure as $column): ?>
                                                    <div class="column-item">
                                                        <strong><?= $column['Field'] ?></strong><br>
                                                        <small>
                                                            Type: <?= $column['Type'] ?><br>
                                                            Null: <?= $column['Null'] ?><br>
                                                            Key: <?= $column['Key'] ?: 'None' ?><br>
                                                            Default: <?= $column['Default'] ?: 'None' ?><br>
                                                            Extra: <?= $column['Extra'] ?: 'None' ?>
                                                        </small>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php
                                            $foreignKeys = getForeignKeys($pdo, $table);
                                            if (!empty($foreignKeys)):
                                            ?>
                                            <h4>🔗 Foreign Keys (<?= count($foreignKeys) ?>)</h4>
                                            <ul>
                                                <?php foreach ($foreignKeys as $fk): ?>
                                                    <li><?= $fk['COLUMN_NAME'] ?> → <?= $fk['REFERENCED_TABLE_NAME'] ?>.<?= $fk['REFERENCED_COLUMN_NAME'] ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>

                                            <?php
                                            $indexes = getTableIndexes($pdo, $table);
                                            if (!empty($indexes)):
                                                $indexGroups = [];
                                                foreach ($indexes as $index) {
                                                    $indexGroups[$index['Key_name']][] = $index['Column_name'];
                                                }
                                            ?>
                                            <h4>📇 Indexes (<?= count($indexGroups) ?>)</h4>
                                            <ul>
                                                <?php foreach ($indexGroups as $indexName => $columns): ?>
                                                    <li><strong><?= $indexName ?></strong>: <?= implode(', ', $columns) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>

                                            <?php if ($rowCount > 0 && $rowCount < 20): ?>
                                                <?php $sampleData = getSampleData($pdo, $table, 3); ?>
                                                <?php if (!empty($sampleData)): ?>
                                                <h4>📄 Sample Data (first 3 rows)</h4>
                                                <table style="font-size: 11px;">
                                                    <thead>
                                                        <tr>
                                                            <?php foreach (array_keys($sampleData[0]) as $column): ?>
                                                                <th><?= $column ?></th>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($sampleData as $row): ?>
                                                            <tr>
                                                                <?php foreach ($row as $value): ?>
                                                                    <td><?= htmlspecialchars(substr((string)$value, 0, 50)) ?><?= strlen((string)$value) > 50 ? '...' : '' ?></td>
                                                                <?php endforeach; ?>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section id="comparison">
            <h2>🔍 Documentation vs Reality Comparison</h2>

            <?php
            $missingFromDB = array_diff(array_keys($documentedTables), $actualTables);
            $extraInDB = array_diff($actualTables, array_keys($documentedTables));
            ?>

            <?php if (!empty($missingFromDB)): ?>
            <h3>❌ Documented but Missing from Database (<?= count($missingFromDB) ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Purpose</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missingFromDB as $table): ?>
                        <tr>
                            <td><strong><?= $table ?></strong></td>
                            <td><?= $documentedTables[$table] ?></td>
                            <td>
                                <?php if (strpos($table, 'role') !== false || strpos($table, 'permission') !== false): ?>
                                    <span class="error">High - RBAC Core</span>
                                <?php elseif (strpos($table, 'document') !== false): ?>
                                    <span class="warning">Medium - Core Feature</span>
                                <?php else: ?>
                                    <span class="info">Low - Master Data</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if (!empty($extraInDB)): ?>
            <h3>✅ In Database but Not Documented (<?= count($extraInDB) ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Rows</th>
                        <th>Likely Purpose</th>
                        <th>Documentation Needed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($extraInDB as $table): ?>
                        <?php
                        $rowCount = getTableRowCount($pdo, $table);
                        $purpose = 'Unknown';
                        $priority = 'Low';

                        if (strpos($table, 'document') !== false) {
                            $purpose = 'Document Management - Core functionality';
                            $priority = 'High';
                        } elseif (strpos($table, 'activity') !== false || strpos($table, 'audit') !== false) {
                            $purpose = 'Audit Trail - Compliance logging';
                            $priority = 'High';
                        } elseif (strpos($table, 'category') !== false || strpos($table, 'categor') !== false) {
                            $purpose = 'Category Management - Organization';
                            $priority = 'Medium';
                        } elseif (strpos($table, 'setting') !== false) {
                            $purpose = 'System Configuration - Settings storage';
                            $priority = 'Medium';
                        } elseif (strpos($table, 'user') !== false && strpos($table, 'effective') !== false) {
                            $purpose = 'RBAC Performance - Permission caching';
                            $priority = 'Medium';
                        }
                        ?>
                        <tr>
                            <td><strong><?= $table ?></strong></td>
                            <td><?= $rowCount ?></td>
                            <td><?= $purpose ?></td>
                            <td><span class="<?= $priority === 'High' ? 'error' : ($priority === 'Medium' ? 'warning' : 'info') ?>"><?= $priority ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <section id="recommendations">
            <h2>💡 Recommendations</h2>

            <div class="status-line info">
                <strong>📄 Update database_structure.md file with the following changes:</strong>
            </div>

            <h3>1. Add Missing Tables to Documentation</h3>
            <pre><?php
            foreach ($extraInDB as $table) {
                $structure = getTableStructure($pdo, $table);
                echo "### **{$table}** - " . ucfirst(str_replace(['dms_', '_'], ['', ' '], $table)) . "\n";
                echo "```sql\n";
                echo "CREATE TABLE `{$table}` (\n";
                foreach ($structure as $i => $column) {
                    $line = "    `{$column['Field']}` {$column['Type']}";
                    if ($column['Null'] === 'NO') $line .= ' NOT NULL';
                    if ($column['Default'] !== null) $line .= " DEFAULT {$column['Default']}";
                    if ($column['Extra']) $line .= " {$column['Extra']}";
                    if ($i < count($structure) - 1) $line .= ',';
                    echo "$line\n";
                }
                echo ");\n```\n\n";
            }
            ?></pre>

            <h3>2. Mark Unimplemented Tables as "Future"</h3>
            <div class="status-line warning">
                The following documented tables don't exist and should be marked as "Future Implementation":
                <ul>
                    <?php foreach ($missingFromDB as $table): ?>
                        <li><?= $table ?> - <?= $documentedTables[$table] ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <h3>3. Update RBAC Implementation Status</h3>
            <div class="status-line info">
                Document the current dual-system approach with both legacy roles (admin/manager/user)
                and new RBAC system coexisting during migration phase.
            </div>

            <h3>4. Priority Actions</h3>
            <ol>
                <li><strong>Document actual working tables</strong> (<?= count($extraInDB) ?> tables missing from docs)</li>
                <li><strong>Create missing RBAC tables</strong> if RBAC is enabled</li>
                <li><strong>Update schema documentation</strong> to reflect current reality</li>
                <li><strong>Add migration status</strong> documentation for transition period</li>
            </ol>
        </section>

        <div class="footer">
            <p>KaizenDMS Database Schema Analysis | Generated at <?php echo date('Y-m-d H:i:s'); ?></p>
            <p>Database: <?= DB_NAME ?> | Host: <?= DB_HOST ?></p>
        </div>
    </div>
</body>
</html>