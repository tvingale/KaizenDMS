<?php
/**
 * Database Schema Analyzer
 * Connects to the actual database and retrieves current schema information
 */

// Include configuration - server structure: root/tools
$possibleConfigPaths = [
    __DIR__ . '/../config.php',      // Server structure (tools in root/tools, config in root)
    __DIR__ . '/../src/config.php',  // Development structure
];

$configLoaded = false;
foreach ($possibleConfigPaths as $configFile) {
    if (file_exists($configFile)) {
        require_once $configFile;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    die("❌ Configuration file not found. Expected at: " . implode(' or ', $possibleConfigPaths) . "\n");
}

// Database connection
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "✅ Connected to database: " . DB_NAME . "\n";
    echo "🔗 Host: " . DB_HOST . ":" . DB_PORT . "\n";
    echo "👤 User: " . DB_USER . "\n\n";

} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Function to get all tables with DMS prefix
function getDMSTables($pdo) {
    $query = "SHOW TABLES LIKE 'dms_%'";
    $stmt = $pdo->query($query);
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Function to get table structure
function getTableStructure($pdo, $tableName) {
    $query = "DESCRIBE $tableName";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll();
}

// Function to get table row count
function getTableRowCount($pdo, $tableName) {
    try {
        $query = "SELECT COUNT(*) as count FROM $tableName";
        $stmt = $pdo->query($query);
        $result = $stmt->fetch();
        return $result['count'];
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// Function to get foreign key constraints
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

// Function to get table indexes
function getTableIndexes($pdo, $tableName) {
    $query = "SHOW INDEX FROM $tableName";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll();
}

// Get all DMS tables
$tables = getDMSTables($pdo);

echo "📊 DATABASE SCHEMA ANALYSIS\n";
echo "=================================\n\n";

echo "📋 TABLES FOUND (" . count($tables) . " total):\n";
foreach ($tables as $table) {
    $rowCount = getTableRowCount($pdo, $table);
    echo "  ├── $table ($rowCount rows)\n";
}
echo "\n";

// Detailed analysis for each table
foreach ($tables as $table) {
    echo "🔍 TABLE: $table\n";
    echo str_repeat("-", 50) . "\n";

    // Table structure
    $structure = getTableStructure($pdo, $table);
    echo "📝 COLUMNS:\n";
    foreach ($structure as $column) {
        $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $key = $column['Key'] ? " [{$column['Key']}]" : '';
        $default = $column['Default'] !== null ? " DEFAULT: {$column['Default']}" : '';
        $extra = $column['Extra'] ? " {$column['Extra']}" : '';

        echo sprintf("  ├── %-20s %-20s %s%s%s%s\n",
            $column['Field'],
            $column['Type'],
            $null,
            $key,
            $default,
            $extra
        );
    }

    // Foreign Keys
    $foreignKeys = getForeignKeys($pdo, $table);
    if (!empty($foreignKeys)) {
        echo "\n🔗 FOREIGN KEYS:\n";
        foreach ($foreignKeys as $fk) {
            echo "  ├── {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
    }

    // Indexes
    $indexes = getTableIndexes($pdo, $table);
    if (!empty($indexes)) {
        echo "\n📇 INDEXES:\n";
        $indexGroups = [];
        foreach ($indexes as $index) {
            $indexGroups[$index['Key_name']][] = $index['Column_name'];
        }
        foreach ($indexGroups as $indexName => $columns) {
            $type = $indexName === 'PRIMARY' ? 'PRIMARY KEY' : 'INDEX';
            echo "  ├── $type: $indexName (" . implode(', ', $columns) . ")\n";
        }
    }

    // Row count
    $rowCount = getTableRowCount($pdo, $table);
    echo "\n📊 DATA: $rowCount rows\n";

    echo "\n" . str_repeat("=", 80) . "\n\n";
}

// Summary of missing documented tables
$documentedTables = [
    'dms_sites', 'dms_departments', 'dms_customers', 'dms_suppliers',
    'dms_process_areas', 'dms_document_types', 'dms_languages',
    'dms_review_cycles', 'dms_notification_templates', 'dms_notification_channels',
    'dms_roles', 'dms_permissions', 'dms_role_permissions', 'dms_user_roles'
];

$actualTables = $tables;
$missingFromDB = array_diff($documentedTables, $actualTables);
$extraInDB = array_diff($actualTables, $documentedTables);

echo "📋 DOCUMENTATION COMPARISON\n";
echo "============================\n\n";

if (!empty($missingFromDB)) {
    echo "❌ DOCUMENTED BUT NOT IN DATABASE:\n";
    foreach ($missingFromDB as $table) {
        echo "  ├── $table\n";
    }
    echo "\n";
}

if (!empty($extraInDB)) {
    echo "✅ IN DATABASE BUT NOT DOCUMENTED:\n";
    foreach ($extraInDB as $table) {
        echo "  ├── $table\n";
    }
    echo "\n";
}

// Sample data from key tables
echo "📄 SAMPLE DATA FROM KEY TABLES\n";
echo "================================\n\n";

foreach ($tables as $table) {
    $rowCount = getTableRowCount($pdo, $table);
    if ($rowCount > 0 && $rowCount < 100) { // Only show sample data for small tables
        try {
            $query = "SELECT * FROM $table LIMIT 5";
            $stmt = $pdo->query($query);
            $data = $stmt->fetchAll();

            if (!empty($data)) {
                echo "📋 $table (first 5 rows):\n";
                foreach ($data as $row) {
                    $values = [];
                    foreach ($row as $key => $value) {
                        if (strlen($value) > 30) {
                            $value = substr($value, 0, 27) . '...';
                        }
                        $values[] = "$key: $value";
                    }
                    echo "  ├── " . implode(' | ', $values) . "\n";
                }
                echo "\n";
            }
        } catch (Exception $e) {
            echo "  ├── Error reading $table: " . $e->getMessage() . "\n\n";
        }
    }
}

echo "🎯 ANALYSIS COMPLETE\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
?>