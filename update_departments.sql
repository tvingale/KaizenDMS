UPDATE dms_departments SET manager_name = 'Kaizen Admin', updated_at = NOW() WHERE manager_name IS NULL OR manager_name = '';
