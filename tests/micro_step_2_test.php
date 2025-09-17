<?php
/**
 * MICRO-STEP 2 TEST: Permissions Table
 * Tests permissions table creation and basic operations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔄 MICRO-STEP 2 TEST: Permissions Table\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Step 1: Test database connection (dependency check)
echo "📋 PLAN: Verify database connection\n";
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

// Step 2: Verify roles table exists (dependency check)
echo "📋 PLAN: Verify dms_roles table exists\n";
echo "⚡ DO: Checking for dependency table...\n";

try {
    $stmt = $db->query("SHOW TABLES LIKE 'dms_roles'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("dms_roles table does not exist - run MICRO-STEP 1 first");
    }
    echo "✅ CHECK: dms_roles dependency verified\n\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: Dependency check failed - " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Create permissions table
echo "📋 PLAN: Create dms_permissions table\n";
echo "⚡ DO: Executing migration script...\n";

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/002_create_permissions_table.sql');
    $db->exec($sql);
    echo "✅ CHECK: Permissions table created successfully\n\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: Table creation failed - " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Verify table structure
echo "📋 PLAN: Verify table structure\n";
echo "⚡ DO: Checking table schema...\n";

try {
    $stmt = $db->query("DESCRIBE dms_permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expected_columns = ['id', 'permission_name', 'display_name', 'description', 'module_scope', 'permission_type'];
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

// Step 5: Verify test data
echo "📋 PLAN: Verify test data insertion\n";
echo "⚡ DO: Checking inserted permissions...\n";

try {
    $stmt = $db->query("SELECT permission_name, display_name, permission_type FROM dms_permissions WHERE is_system_permission = true");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($permissions) < 4) {
        throw new Exception("Expected at least 4 system permissions, found " . count($permissions));
    }
    
    echo "✅ CHECK: Test data verified - Found " . count($permissions) . " system permissions:\n";
    foreach ($permissions as $perm) {
        echo "   - {$perm['permission_name']}: {$perm['display_name']} ({$perm['permission_type']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: Test data verification failed - " . $e->getMessage() . "\n";
    exit(1);
}

// Step 6: Test basic CRUD operations
echo "📋 PLAN: Test basic CRUD operations\n";
echo "⚡ DO: Testing INSERT, SELECT, UPDATE...\n";

try {
    // Test INSERT
    $stmt = $db->prepare("INSERT INTO dms_permissions (permission_name, display_name, module_scope, permission_type) VALUES (?, ?, ?, ?)");
    $stmt->execute(['test_permission', 'Test Permission', 'test', 'read']);
    $test_id = $db->lastInsertId();
    
    // Test SELECT
    $stmt = $db->prepare("SELECT * FROM dms_permissions WHERE id = ?");
    $stmt->execute([$test_id]);
    $test_perm = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test_perm || $test_perm['permission_name'] !== 'test_permission') {
        throw new Exception("SELECT test failed");
    }
    
    // Test UPDATE
    $stmt = $db->prepare("UPDATE dms_permissions SET description = ? WHERE id = ?");
    $stmt->execute(['Updated test description', $test_id]);
    
    // Test DELETE (cleanup)
    $stmt = $db->prepare("DELETE FROM dms_permissions WHERE id = ?");
    $stmt->execute([$test_id]);
    
    echo "✅ CHECK: CRUD operations successful\n\n";
} catch (Exception $e) {
    echo "❌ CHECK FAILED: CRUD operations failed - " . $e->getMessage() . "\n";
    exit(1);
}

echo "🎉 MICRO-STEP 2 COMPLETE: All checks passed!\n";
echo "🔧 ACT: Ready for next micro-step\n\n";

echo "📊 SUMMARY:\n";
echo "✅ Database connection working\n";
echo "✅ dms_roles dependency verified\n";
echo "✅ dms_permissions table created\n";
echo "✅ Table structure verified\n";
echo "✅ Test data inserted\n";
echo "✅ CRUD operations functional\n";
echo "\n🚀 Ready for MICRO-STEP 3: Role-Permission Mapping Table\n";
?>