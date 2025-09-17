-- Add manager_user_id column to dms_departments table
-- This links departments to users who have DMS access (dms_user_roles.user_id)

-- Add the column
ALTER TABLE dms_departments
ADD COLUMN manager_user_id INT NULL AFTER manager_email;

-- Add index for performance
ALTER TABLE dms_departments
ADD INDEX idx_manager_user_id (manager_user_id);

-- Update existing admin assignments (assuming admin user_id = 1)
UPDATE dms_departments
SET manager_user_id = 1,
    manager_name = 'Kaizen Admin',
    updated_at = NOW()
WHERE manager_name IS NULL OR manager_name = '';

-- Verify the changes
SELECT
    id,
    dept_code,
    dept_name,
    manager_user_id,
    manager_name,
    manager_email,
    is_active,
    updated_at
FROM dms_departments
ORDER BY sort_order, dept_name;