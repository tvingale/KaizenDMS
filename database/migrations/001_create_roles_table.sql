-- MICRO-STEP 1: Create basic roles table for RBAC foundation
-- This is the smallest possible RBAC implementation

CREATE TABLE IF NOT EXISTS dms_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(150),
    description TEXT,
    department_scope JSON COMMENT 'Array of departments this role applies to',
    hierarchy_level ENUM('operator', 'lead', 'supervisor', 'manager', 'director', 'executive'),
    is_system_role BOOLEAN DEFAULT FALSE COMMENT 'Cannot be deleted if true',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by_user_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert minimal test data for verification
INSERT IGNORE INTO dms_roles (role_name, display_name, description, hierarchy_level, is_system_role) VALUES
('operator', 'Production Operator', 'Basic production line operator', 'operator', true),
('quality_manager', 'Quality Manager', 'Quality department manager', 'manager', true);