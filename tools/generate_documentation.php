<?php
/**
 * Database Documentation Generator
 * Generates updated database_structure.md based on actual database schema
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for web browser
header('Content-Type: text/html; charset=UTF-8');

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
            $configLoaded = true;
            break;
        } catch (Exception $e) {
            continue;
        }
    }
}

if (!$configLoaded) {
    die("❌ Configuration file not found in any expected location");
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
            $dbLoaded = true;
            break;
        } catch (Exception $e) {
            continue;
        }
    }
}

if (!$dbLoaded) {
    die("❌ Database connection failed or database.php not found");
}

// Functions for documentation generation
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
        return 0;
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

function generateCreateTableSQL($pdo, $tableName) {
    $query = "SHOW CREATE TABLE $tableName";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch();
    return $result['Create Table'];
}

function categorizeTable($tableName) {
    if (strpos($tableName, 'role') !== false || strpos($tableName, 'permission') !== false || strpos($tableName, 'user_') !== false) {
        return 'RBAC System';
    } elseif (strpos($tableName, 'document') !== false) {
        return 'Document Management';
    } elseif (strpos($tableName, 'activity') !== false || strpos($tableName, 'audit') !== false) {
        return 'Audit Trail';
    } elseif (strpos($tableName, 'setting') !== false) {
        return 'System Configuration';
    } elseif (strpos($tableName, 'categor') !== false) {
        return 'Organization';
    } else {
        return 'Master Data';
    }
}

function getTablePurpose($tableName) {
    $purposes = [
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
        'dms_user_roles' => 'User Role Assignments',
        'dms_documents' => 'Document Repository',
        'dms_categories' => 'Document Categorization',
        'dms_activity_log' => 'System Activity Audit Trail',
        'dms_settings' => 'Application Configuration Storage',
        'dms_user_effective_permissions' => 'RBAC Permission Caching'
    ];

    return $purposes[$tableName] ?? ucfirst(str_replace(['dms_', '_'], ['', ' '], $tableName));
}

// Get all tables and categorize them
$tables = getDMSTables($pdo);
$categorizedTables = [];
foreach ($tables as $table) {
    $category = categorizeTable($table);
    $categorizedTables[$category][] = $table;
}

// Start generating markdown
if (isset($_GET['download'])) {
    header('Content-Type: text/markdown');
    header('Content-Disposition: attachment; filename="database_structure_updated.md"');
} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Generate Database Documentation</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; min-height: 100vh; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); max-width: 1200px; margin: 0 auto; }
            h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 15px; margin-bottom: 25px; }
            .actions { text-align: center; margin: 20px 0; }
            .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; font-weight: bold; }
            .btn.success { background: #28a745; }
            .btn.warning { background: #ffc107; color: #212529; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; max-height: 70vh; }
            .preview { border: 1px solid #dee2e6; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📖 Database Documentation Generator</h1>

            <div class="actions">
                <a href="?download=1" class="btn success">📥 Download Updated Documentation</a>
                <a href="index.php" class="btn">← Back to Tools</a>
                <a href="web_schema_analyzer.php" class="btn warning">🔍 View Schema Analysis</a>
            </div>

            <div class="preview">
                <h3>📄 Generated Documentation Preview</h3>
                <pre><?php
    }

// Generate the markdown content
echo "# KaizenDMS Database Structure - Updated\n\n";
echo "**Automatically generated from actual database schema**\n\n";
echo "**Generated:** " . date('Y-m-d H:i:s') . "\n";
echo "**Database:** " . DB_NAME . "\n";
echo "**Host:** " . DB_HOST . "\n";
echo "**Total Tables:** " . count($tables) . "\n\n";

echo "---\n\n";

echo "## 📊 **Database Overview**\n\n";
echo "**Database Name:** `" . DB_NAME . "`\n";
echo "**Prefix:** `dms_` (all tables)\n";
echo "**Total Tables:** " . count($tables) . " actual tables\n";
echo "**Storage Engine:** InnoDB with foreign key constraints\n";
echo "**Character Set:** utf8mb4_unicode_ci for full Unicode support\n\n";

echo "---\n\n";

echo "## 🏗️ **Table Categories**\n\n";

foreach ($categorizedTables as $category => $categoryTables) {
    echo "### **📋 $category Tables (" . count($categoryTables) . ")**\n";
    foreach ($categoryTables as $table) {
        $rowCount = getTableRowCount($pdo, $table);
        $purpose = getTablePurpose($table);
        echo "- `$table` - $purpose ($rowCount rows)\n";
    }
    echo "\n";
}

echo "---\n\n";

echo "## 📋 **Detailed Table Structures**\n\n";

foreach ($categorizedTables as $category => $categoryTables) {
    echo "### **$category Tables**\n\n";

    foreach ($categoryTables as $table) {
        $structure = getTableStructure($pdo, $table);
        $rowCount = getTableRowCount($pdo, $table);
        $purpose = getTablePurpose($table);
        $foreignKeys = getForeignKeys($pdo, $table);

        echo "#### **$table** - $purpose\n";

        try {
            $createSQL = generateCreateTableSQL($pdo, $table);
            echo "```sql\n";
            echo $createSQL . ";\n";
            echo "```\n\n";
        } catch (Exception $e) {
            echo "**Columns:**\n";
            foreach ($structure as $column) {
                $line = "- `{$column['Field']}` {$column['Type']}";
                if ($column['Null'] === 'NO') $line .= ' NOT NULL';
                if ($column['Key']) $line .= " [{$column['Key']}]";
                if ($column['Default'] !== null) $line .= " DEFAULT: {$column['Default']}";
                if ($column['Extra']) $line .= " {$column['Extra']}";
                echo "$line\n";
            }
            echo "\n";
        }

        if (!empty($foreignKeys)) {
            echo "**Foreign Keys:**\n";
            foreach ($foreignKeys as $fk) {
                echo "- `{$fk['COLUMN_NAME']}` → `{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}`\n";
            }
            echo "\n";
        }

        echo "**Status:** Currently implemented with $rowCount rows\n\n";
    }
}

echo "---\n\n";

echo "## ⚙️ **Implementation Status**\n\n";

echo "### ✅ **Implemented Tables (" . count($tables) . ")**\n";
foreach ($tables as $table) {
    $rowCount = getTableRowCount($pdo, $table);
    $purpose = getTablePurpose($table);
    echo "- **$table** - $purpose ($rowCount rows)\n";
}
echo "\n";

// Check for commonly expected but missing tables
$expectedTables = [
    'dms_sites', 'dms_departments', 'dms_customers', 'dms_suppliers',
    'dms_process_areas', 'dms_document_types', 'dms_languages',
    'dms_review_cycles', 'dms_notification_templates', 'dms_notification_channels'
];

$missingTables = array_diff($expectedTables, $tables);
if (!empty($missingTables)) {
    echo "### ❌ **Missing Expected Tables (" . count($missingTables) . ")**\n";
    foreach ($missingTables as $table) {
        $purpose = getTablePurpose($table);
        echo "- **$table** - $purpose (Not implemented)\n";
    }
    echo "\n";
}

echo "### 🔧 **RBAC System Status**\n";
$rbacTables = $categorizedTables['RBAC System'] ?? [];
if (!empty($rbacTables)) {
    echo "**RBAC Enabled:** " . (ENABLE_RBAC ? 'Yes' : 'No') . "\n";
    echo "**RBAC Tables:** " . count($rbacTables) . " implemented\n";
    foreach ($rbacTables as $table) {
        $rowCount = getTableRowCount($pdo, $table);
        echo "- `$table` ($rowCount rows)\n";
    }
} else {
    echo "**RBAC Status:** Not implemented\n";
}
echo "\n";

echo "---\n\n";

echo "## 📈 **Database Statistics**\n\n";

$totalRows = 0;
foreach ($tables as $table) {
    $totalRows += getTableRowCount($pdo, $table);
}

echo "- **Total Tables:** " . count($tables) . "\n";
echo "- **Total Records:** $totalRows\n";
echo "- **Master Data Tables:** " . count($categorizedTables['Master Data'] ?? []) . "\n";
echo "- **RBAC Tables:** " . count($categorizedTables['RBAC System'] ?? []) . "\n";
echo "- **Document Management Tables:** " . count($categorizedTables['Document Management'] ?? []) . "\n";
echo "- **Audit Trail Tables:** " . count($categorizedTables['Audit Trail'] ?? []) . "\n";

echo "\n---\n\n";

echo "## 🔄 **Migration Notes**\n\n";
echo "This documentation reflects the **actual current state** of the database as of " . date('Y-m-d H:i:s') . ".\n\n";

echo "**Key Changes from Original Documentation:**\n";
if (!empty($categorizedTables['Document Management'])) {
    echo "- ✅ Document management system is **implemented** with " . count($categorizedTables['Document Management']) . " tables\n";
}
if (!empty($categorizedTables['Audit Trail'])) {
    echo "- ✅ Audit trail system is **implemented** with " . count($categorizedTables['Audit Trail']) . " tables\n";
}
if (!empty($rbacTables)) {
    echo "- ✅ RBAC system is **partially implemented** with " . count($rbacTables) . " tables\n";
}
if (!empty($missingTables)) {
    echo "- ⚠️ " . count($missingTables) . " documented master data tables are **not yet implemented**\n";
}

echo "\n**Recommendations:**\n";
echo "1. Update main `database_structure.md` file with this current schema\n";
echo "2. Mark unimplemented tables as 'Future' in documentation\n";
echo "3. Document the current RBAC implementation status\n";
echo "4. Add migration plan for remaining master data tables\n\n";

echo "---\n\n";
echo "**Generated by KaizenDMS Database Documentation Generator**\n";
echo "**Timestamp:** " . date('Y-m-d H:i:s') . "\n";
echo "**Database:** " . DB_NAME . " @ " . DB_HOST . "\n";

if (!isset($_GET['download'])) {
    ?>
                </pre>
            </div>

            <div class="actions">
                <a href="?download=1" class="btn success">📥 Download This Documentation</a>
                <a href="web_schema_analyzer.php" class="btn warning">🔍 Detailed Schema Analysis</a>
                <a href="index.php" class="btn">← Back to Database Tools</a>
            </div>

            <div style="background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3>📋 How to Use This Documentation</h3>
                <ol>
                    <li><strong>Download:</strong> Click "Download" to get the markdown file</li>
                    <li><strong>Replace:</strong> Replace your current database_structure.md with this version</li>
                    <li><strong>Review:</strong> Review the changes and add any missing context</li>
                    <li><strong>Update:</strong> Commit the updated documentation to your repository</li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>