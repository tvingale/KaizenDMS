<?php
/**
 * Assign admin user to existing departments as manager
 * One-time setup script for MICRO-STEP 11 enhancement
 */

// Path resolution for different environments
$config_paths = [
    __DIR__ . '/../config.php',      // Server structure
    __DIR__ . '/../src/config.php',  // Development structure
    __DIR__ . '/config.php',         // Same directory
    dirname(__DIR__) . '/config.php' // Parent directory
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
    __DIR__ . '/../includes/database.php',      // Server structure
    __DIR__ . '/../src/includes/database.php',  // Development structure
    __DIR__ . '/includes/database.php',         // Same directory
    dirname(__DIR__) . '/includes/database.php' // Parent directory
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
    <title>Assign Admin to Departments</title>
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
        .header {
            background: linear-gradient(135deg, #C53A3A 0%, #8B0000 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .result-box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        .update-log {
            background: #f1f3f4;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Assign Admin to Departments</h1>
            <p>One-time setup: Assign admin user as manager to existing departments</p>
        </div>

        <?php
        try {
            $db = getDB();

            echo "<div class='result-box'>";
            echo "<h3>Step 1: Checking Current Departments</h3>";

            // Get current departments
            $stmt = $db->query("SELECT id, dept_code, dept_name, manager_name, manager_email FROM dms_departments ORDER BY sort_order, dept_name");
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($departments)) {
                echo "<p class='error'>No departments found in database.</p>";
                echo "</div></div></body></html>";
                exit;
            }

            echo "<p class='info'>Found " . count($departments) . " departments:</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Current Manager</th><th>Manager Email</th></tr>";

            $needsUpdate = [];
            foreach ($departments as $dept) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($dept['id']) . "</td>";
                echo "<td>" . htmlspecialchars($dept['dept_code']) . "</td>";
                echo "<td>" . htmlspecialchars($dept['dept_name']) . "</td>";
                echo "<td>" . htmlspecialchars($dept['manager_name'] ?: 'Not assigned') . "</td>";
                echo "<td>" . htmlspecialchars($dept['manager_email'] ?: 'No email') . "</td>";
                echo "</tr>";

                // Track departments that need admin assignment
                if (empty($dept['manager_name'])) {
                    $needsUpdate[] = $dept;
                }
            }
            echo "</table>";
            echo "</div>";

            // Step 2: Get admin user info
            echo "<div class='result-box'>";
            echo "<h3>Step 2: Getting Admin User Information</h3>";

            // Get admin user from user_roles table (user_id = 1 based on analysis)
            $adminUserId = 1;
            $adminName = 'Kaizen Admin';
            $adminEmail = '';

            // Try to get more info about admin user from user_roles
            $stmt = $db->prepare("
                SELECT ur.user_id, ur.notes, ur.granted_at
                FROM dms_user_roles ur
                WHERE ur.user_id = ? AND ur.role_id = 1 AND ur.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$adminUserId]);
            $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($adminInfo) {
                echo "<p class='success'>✅ Admin user found: ID = $adminUserId</p>";
                echo "<p class='info'>Admin user has been active since: " . ($adminInfo['granted_at'] ?: 'Unknown') . "</p>";
            } else {
                echo "<p class='error'>❌ Could not find admin user information</p>";
                echo "</div></div></body></html>";
                exit;
            }
            echo "</div>";

            // Step 3: Update departments
            echo "<div class='result-box'>";
            echo "<h3>Step 3: Assigning Admin to Departments</h3>";

            if (empty($needsUpdate)) {
                echo "<p class='info'>All departments already have managers assigned. No updates needed.</p>";
            } else {
                echo "<p class='info'>Updating " . count($needsUpdate) . " departments that need manager assignment:</p>";
                echo "<div class='update-log'>";

                $updateCount = 0;
                foreach ($needsUpdate as $dept) {
                    try {
                        $stmt = $db->prepare("
                            UPDATE dms_departments
                            SET manager_name = ?, updated_at = NOW()
                            WHERE id = ? AND (manager_name IS NULL OR manager_name = '')
                        ");

                        $stmt->execute([$adminName, $dept['id']]);

                        if ($stmt->rowCount() > 0) {
                            echo "✅ Updated department: " . htmlspecialchars($dept['dept_name']) . " (ID: {$dept['id']}) -> Admin: $adminName\n";
                            $updateCount++;
                        } else {
                            echo "⚠️  No update needed for: " . htmlspecialchars($dept['dept_name']) . " (already has manager)\n";
                        }

                    } catch (Exception $e) {
                        echo "❌ Error updating " . htmlspecialchars($dept['dept_name']) . ": " . $e->getMessage() . "\n";
                    }
                }

                echo "</div>";
                echo "<p class='success'>Successfully updated $updateCount departments.</p>";
            }
            echo "</div>";

            // Step 4: Final verification
            echo "<div class='result-box'>";
            echo "<h3>Step 4: Final Verification</h3>";

            $stmt = $db->query("SELECT id, dept_code, dept_name, manager_name, manager_email, updated_at FROM dms_departments ORDER BY sort_order, dept_name");
            $updatedDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<p class='info'>Current department status after updates:</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Manager</th><th>Email</th><th>Last Updated</th></tr>";

            $managedCount = 0;
            foreach ($updatedDepartments as $dept) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($dept['id']) . "</td>";
                echo "<td>" . htmlspecialchars($dept['dept_code']) . "</td>";
                echo "<td>" . htmlspecialchars($dept['dept_name']) . "</td>";
                echo "<td>" . htmlspecialchars($dept['manager_name'] ?: 'Not assigned') . "</td>";
                echo "<td>" . htmlspecialchars($dept['manager_email'] ?: 'No email') . "</td>";
                echo "<td>" . htmlspecialchars($dept['updated_at']) . "</td>";
                echo "</tr>";

                if (!empty($dept['manager_name'])) {
                    $managedCount++;
                }
            }
            echo "</table>";

            echo "<p class='success'>Summary: $managedCount out of " . count($updatedDepartments) . " departments now have managers assigned.</p>";
            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='result-box'>";
            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>

        <div class="result-box">
            <h3>✅ Setup Complete</h3>
            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>✅ Admin user assigned to departments without managers</li>
                <li>✅ Departments interface now supports user selection dropdown</li>
                <li>✅ Email auto-fills when selecting users as managers</li>
                <li>🚀 Ready to continue with MICRO-STEP 12: Sites Management UI</li>
            </ul>
        </div>
    </div>
</body>
</html>