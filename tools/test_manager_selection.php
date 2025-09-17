<?php
/**
 * Test Manager Selection Functionality
 * Verifies DMS user search and manager assignment works correctly
 */

// Path resolution for different environments
$config_paths = [
    __DIR__ . '/../config.php',
    __DIR__ . '/../src/config.php',
    dirname(__DIR__) . '/config.php',
    dirname(__DIR__) . '/src/config.php'
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die('❌ CONFIG ERROR: Could not locate config.php file');
}

// Database include path resolution
$db_paths = [
    __DIR__ . '/../includes/database.php',
    __DIR__ . '/../src/includes/database.php',
    dirname(__DIR__) . '/includes/database.php',
    dirname(__DIR__) . '/src/includes/database.php'
];

$db_loaded = false;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

if (!$db_loaded) {
    die('❌ DATABASE ERROR: Could not locate database.php file');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Manager Selection</title>
    <style>
        body {
            font-family: "Segoe UI", system-ui, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-header {
            background: linear-gradient(135deg, #C53A3A 0%, #8B0000 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #C53A3A;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #C53A3A;
            color: white;
        }
        .test-link {
            display: inline-block;
            margin: 10px 10px 10px 0;
            padding: 8px 16px;
            background-color: #C53A3A;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .test-link:hover {
            background-color: #8B0000;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-header">
            <h1>🧪 Manager Selection Test</h1>
            <p>Testing DMS user search and manager assignment functionality</p>
        </div>

        <?php
        try {
            $db = getDB();

            echo "<div class='test-section'>";
            echo "<h2>📊 Test 1: Check DMS Users Available</h2>";

            // Get users from dms_user_roles
            $stmt = $db->query("
                SELECT DISTINCT
                    ur.user_id,
                    ur.status,
                    ur.department,
                    ur.notes,
                    r.display_name as role_name,
                    ur.granted_at
                FROM dms_user_roles ur
                LEFT JOIN dms_roles r ON ur.role_id = r.id
                WHERE ur.status = 'active'
                ORDER BY ur.user_id
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                echo "<p class='error'>❌ No active users found in dms_user_roles table</p>";
            } else {
                echo "<p class='success'>✅ Found " . count($users) . " active DMS users</p>";

                echo "<table>";
                echo "<tr><th>User ID</th><th>Role</th><th>Department</th><th>Notes</th><th>Status</th></tr>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['role_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['department'] ?? 'Not set') . "</td>";
                    echo "<td>" . htmlspecialchars(substr($user['notes'] ?? '', 0, 50)) . "</td>";
                    echo "<td>" . htmlspecialchars($user['status']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            echo "</div>";

            echo "<div class='test-section'>";
            echo "<h2>🔍 Test 2: Check DMS User Search API</h2>";

            $apiPath = dirname(__DIR__) . '/src/api/dms_user_search.php';
            if (file_exists($apiPath)) {
                echo "<p class='success'>✅ DMS User Search API exists</p>";
                echo "<p class='info'>API Location: " . htmlspecialchars($apiPath) . "</p>";
            } else {
                echo "<p class='error'>❌ DMS User Search API missing</p>";
                echo "<p>Expected at: " . htmlspecialchars($apiPath) . "</p>";
            }
            echo "</div>";

            echo "<div class='test-section'>";
            echo "<h2>🏢 Test 3: Check Department Table Structure</h2>";

            try {
                $stmt = $db->query("DESCRIBE dms_departments");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $hasManagerUserId = false;
                $hasManagerName = false;
                $hasManagerEmail = false;

                echo "<table>";
                echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                    echo "</tr>";

                    if ($column['Field'] === 'manager_user_id') $hasManagerUserId = true;
                    if ($column['Field'] === 'manager_name') $hasManagerName = true;
                    if ($column['Field'] === 'manager_email') $hasManagerEmail = true;
                }
                echo "</table>";

                echo "<h4>Manager Field Status:</h4>";
                echo "<p class='" . ($hasManagerUserId ? 'success' : 'error') . "'>";
                echo ($hasManagerUserId ? '✅' : '❌') . " manager_user_id column";
                echo "</p>";
                echo "<p class='" . ($hasManagerName ? 'success' : 'error') . "'>";
                echo ($hasManagerName ? '✅' : '❌') . " manager_name column";
                echo "</p>";
                echo "<p class='" . ($hasManagerEmail ? 'success' : 'error') . "'>";
                echo ($hasManagerEmail ? '✅' : '❌') . " manager_email column";
                echo "</p>";

                if (!$hasManagerUserId) {
                    echo "<p class='info'>💡 Run the SQL update: <code>database/update_departments_manager.sql</code></p>";
                }

            } catch (Exception $e) {
                echo "<p class='error'>❌ Error checking table structure: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            echo "</div>";

            echo "<div class='test-section'>";
            echo "<h2>🔧 Test 4: Manual Integration Test</h2>";

            // Check if departments admin page exists
            $deptsPath = dirname(__DIR__) . '/src/admin/departments.php';
            if (file_exists($deptsPath)) {
                echo "<p class='success'>✅ Departments admin page exists</p>";

                // Generate link (adjust for server structure)
                if (strpos($_SERVER['REQUEST_URI'], '/tools/') !== false) {
                    $baseUrl = str_replace('/tools/' . basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']);
                    $deptsUrl = $baseUrl . '/admin/departments.php';
                } else {
                    $deptsUrl = '../admin/departments.php';
                }

                echo "<p class='info'>🚀 <strong>Manual Test Steps:</strong></p>";
                echo "<ol>";
                echo "<li>Open the departments admin page</li>";
                echo "<li>Click 'Add Department' to test manager selection</li>";
                echo "<li>In the manager field, type a search term</li>";
                echo "<li>Verify that only DMS users appear in dropdown</li>";
                echo "<li>Select a user and verify email auto-fills</li>";
                echo "<li>Save department and check database</li>";
                echo "</ol>";

                echo "<a href='" . htmlspecialchars($deptsUrl) . "' class='test-link' target='_blank'>🏢 Open Departments Admin</a>";
            } else {
                echo "<p class='error'>❌ Departments admin page not found</p>";
            }

            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='test-section'>";
            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>

        <div class="test-section">
            <h2>📝 Implementation Summary</h2>
            <p><strong>✅ Completed:</strong></p>
            <ul>
                <li>Created DMS User Search API (searches dms_user_roles only)</li>
                <li>Updated departments.php to use DMS user search</li>
                <li>Enhanced form processing to store manager_user_id</li>
                <li>Created database update script</li>
                <li>Added role and department display in dropdown</li>
            </ul>

            <p><strong>⚠️ Manual Steps Required:</strong></p>
            <ul>
                <li>Execute <code>database/update_departments_manager.sql</code> on database</li>
                <li>Test manager selection through admin interface</li>
                <li>Verify data storage includes user_id reference</li>
            </ul>
        </div>
    </div>
</body>
</html>