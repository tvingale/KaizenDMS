-- MICRO-STEP 2: Create basic permissions table for RBAC foundation
-- This extends MICRO-STEP 1 with granular permissions

CREATE TABLE IF NOT EXISTS dms_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(150),
    description TEXT,
    module_scope VARCHAR(50) COMMENT 'documents, admin, reports, etc.',
    permission_type ENUM('create', 'read', 'update', 'delete', 'approve', 'review', 'admin') NOT NULL,
    is_system_permission BOOLEAN DEFAULT FALSE COMMENT 'Cannot be deleted if true',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by_user_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert minimal test permissions for verification
INSERT IGNORE INTO dms_permissions (permission_name, display_name, description, module_scope, permission_type, is_system_permission) VALUES
('documents_read', 'Read Documents', 'View and read documents', 'documents', 'read', true),
('documents_create', 'Create Documents', 'Create new documents', 'documents', 'create', true),
('documents_approve', 'Approve Documents', 'Approve documents for release', 'documents', 'approve', true),
('admin_access', 'Admin Access', 'Access administrative functions', 'admin', 'admin', true);