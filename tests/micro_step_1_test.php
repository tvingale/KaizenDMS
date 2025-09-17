<?php
/**
 * MICRO-STEP 1 TEST: Database Connection & Roles Table
 * Tests the smallest possible RBAC implementation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔄 MICRO-STEP 1 TEST: Database Connection & Roles Table\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Step 1: Test database connection
echo "📋 PLAN: Test database connection\n";
echo "⚡ DO: Loading database configuration...\n";

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/database.php';
    
    $db = getDB();
    echo "✅ CHECK: Database connection successful\n\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: Database connection failed - " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Create roles table
echo "📋 PLAN: Create dms_roles table\n";
echo "⚡ DO: Executing migration script...\n";

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/001_create_roles_table.sql');
    $db->exec($sql);
    echo "✅ CHECK: Roles table created successfully\n\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: Table creation failed - " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Verify table structure
echo "📋 PLAN: Verify table structure\n";
echo "⚡ DO: Checking table schema...\n";

try {
    $stmt = $db->query("DESCRIBE dms_roles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expected_columns = ['id', 'role_name', 'display_name', 'description', 'department_scope', 'hierarchy_level'];
    $actual_columns = array_column($columns, 'Field');
    
    foreach ($expected_columns as $col) {
        if (!in_array($col, $actual_columns)) {
            throw new Exception("Missing column: $col");
        }
    }
    echo "✅ CHECK: Table structure verified\n\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: Table structure verification failed - " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Verify test data
echo "📋 PLAN: Verify test data insertion\n";
echo "⚡ DO: Checking inserted roles...\n";

try {
    $stmt = $db->query("SELECT role_name, display_name FROM dms_roles WHERE is_system_role = true");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($roles) < 2) {
        throw new Exception("Expected at least 2 system roles, found " . count($roles));
    }
    
    echo "✅ CHECK: Test data verified - Found " . count($roles) . " system roles:\n";
    foreach ($roles as $role) {
        echo "   - {$role['role_name']}: {$role['display_name']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: Test data verification failed - " . $e->getMessage() . "\n";
    exit(1);
}

// Step 5: Test basic CRUD operations
echo "📋 PLAN: Test basic CRUD operations\n";
echo "⚡ DO: Testing INSERT, SELECT, UPDATE...\n";

try {
    // Test INSERT
    $stmt = $db->prepare("INSERT INTO dms_roles (role_name, display_name, hierarchy_level) VALUES (?, ?, ?)");
    $stmt->execute(['test_role', 'Test Role', 'operator']);
    $test_id = $db->lastInsertId();
    
    // Test SELECT
    $stmt = $db->prepare("SELECT * FROM dms_roles WHERE id = ?");
    $stmt->execute([$test_id]);
    $test_role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test_role || $test_role['role_name'] !== 'test_role') {
        throw new Exception("SELECT test failed");
    }
    
    // Test UPDATE
    $stmt = $db->prepare("UPDATE dms_roles SET description = ? WHERE id = ?");
    $stmt->execute(['Updated test description', $test_id]);
    
    // Test DELETE (cleanup)
    $stmt = $db->prepare("DELETE FROM dms_roles WHERE id = ?");
    $stmt->execute([$test_id]);
    
    echo "✅ CHECK: CRUD operations successful\n\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: CRUD operations failed - " . $e->getMessage() . "\n";
    exit(1);
}

echo "🎉 MICRO-STEP 1 COMPLETE: All checks passed!\n";
echo "🔧 ACT: Ready for next micro-step\n\n";

echo "📊 SUMMARY:\n";
echo "✅ Database connection working\n";
echo "✅ dms_roles table created\n";
echo "✅ Table structure verified\n";
echo "✅ Test data inserted\n";
echo "✅ CRUD operations functional\n";
echo "\n🚀 Ready for MICRO-STEP 2: Permissions Table\n";
?>