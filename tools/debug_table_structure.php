<?php
/**
 * DEBUG: Check existing table structures
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG: Table Structure Check</title>
    <style>
        body { font-family: "Segoe UI", system-ui, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        pre { background: #f1f3f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 DEBUG: Table Structure Check</h1>
        
        <?php
        
        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../includes/database.php';
            
            $db = getDB();
            echo '<p class="success">✅ Database connection successful</p>';
            
            // Check if tables exist
            echo '<h2>📋 Existing DMS Tables</h2>';
            $stmt = $db->query("SHOW TABLES LIKE 'dms_%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo '<p class="info">No DMS tables found</p>';
            } else {
                echo '<ul>';
                foreach ($tables as $table) {
                    echo "<li><strong>$table</strong></li>";
                }
                echo '</ul>';
                
                // Show structure of each table
                foreach ($tables as $table) {
                    echo "<h3>🗄️ Structure of $table</h3>";
                    try {
                        $stmt = $db->query("DESCRIBE $table");
                        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo '<table>';
                        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
                        foreach ($columns as $col) {
                            echo "<tr>";
                            echo "<td>{$col['Field']}</td>";
                            echo "<td>{$col['Type']}</td>";
                            echo "<td>{$col['Null']}</td>";
                            echo "<td>{$col['Key']}</td>";
                            echo "<td>{$col['Default']}</td>";
                            echo "<td>{$col['Extra']}</td>";
                            echo "</tr>";
                        }
                        echo '</table>';
                        
                        // Show sample data
                        echo "<h4>📊 Sample Data (first 3 rows)</h4>";
                        $stmt = $db->query("SELECT * FROM $table LIMIT 3");
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($rows)) {
                            echo '<table>';
                            // Header
                            echo '<tr>';
                            foreach (array_keys($rows[0]) as $key) {
                                echo "<th>$key</th>";
                            }
                            echo '</tr>';
                            // Data rows
                            foreach ($rows as $row) {
                                echo '<tr>';
                                foreach ($row as $value) {
                                    echo '<td>' . htmlspecialchars($value ?? 'NULL') . '</td>';
                                }
                                echo '</tr>';
                            }
                            echo '</table>';
                        } else {
                            echo '<p class="info">No data in table</p>';
                        }
                        
                    } catch (Exception $e) {
                        echo '<p class="error">Error checking table structure: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                }
            }
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Database connection failed - ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        ?>
        
    </div>
</body>
</html>