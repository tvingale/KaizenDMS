<?php
/**
 * Web-based DMS Master Tables Deployment
 * Access via browser: http://your-domain.com/web_deploy_tables.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for web browser
header('Content-Type: text/html; charset=UTF-8');

$deployed = false;
$deploymentResults = [];

// Handle deployment request
if (isset($_POST['deploy']) && $_POST['deploy'] === 'true') {
    $deployed = true;
    
    try {
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/includes/database.php';
        
        $pdo = getDB();
        $deploymentResults['connection'] = '✅ Database connected successfully';
        
        // Embedded SQL schema (no external file needed)
        $sql = "
-- KaizenDMS Master Tables Schema
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = '+00:00';

-- 1. DMS SITES
CREATE TABLE IF NOT EXISTS dms_sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_code VARCHAR(10) UNIQUE NOT NULL,
    site_name VARCHAR(100) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL DEFAULT 'Ahmednagar',
    state VARCHAR(50) NOT NULL DEFAULT 'Maharashtra',
    country VARCHAR(50) NOT NULL DEFAULT 'India',
    postal_code VARCHAR(20) NOT NULL,
    timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Kolkata',
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_main_site BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_site_code (site_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. DMS DEPARTMENTS
CREATE TABLE IF NOT EXISTS dms_departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dept_code VARCHAR(20) UNIQUE NOT NULL,
    dept_name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_dept_id INT NULL,
    manager_name VARCHAR(100),
    manager_email VARCHAR(100),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_dept_id) REFERENCES dms_departments(id) ON DELETE SET NULL,
    INDEX idx_dept_code (dept_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. DMS CUSTOMERS
CREATE TABLE IF NOT EXISTS dms_customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(200) NOT NULL,
    customer_type ENUM('government','private','oem','distributor') NOT NULL DEFAULT 'private',
    address_line1 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(50),
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_customer_code (customer_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. DMS SUPPLIERS
CREATE TABLE IF NOT EXISTS dms_suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    supplier_name VARCHAR(200) NOT NULL,
    supplier_type ENUM('raw_material','component','service','tooling') NOT NULL DEFAULT 'component',
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    iso_certified BOOLEAN DEFAULT FALSE,
    iatf_certified BOOLEAN DEFAULT FALSE,
    approval_status ENUM('approved','conditional','rejected','under_review') DEFAULT 'under_review',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_supplier_code (supplier_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. DMS PROCESS AREAS
CREATE TABLE IF NOT EXISTS dms_process_areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    area_code VARCHAR(20) UNIQUE NOT NULL,
    area_name VARCHAR(100) NOT NULL,
    description TEXT,
    safety_critical_default BOOLEAN NOT NULL DEFAULT FALSE,
    requires_special_training BOOLEAN NOT NULL DEFAULT FALSE,
    department_id INT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES dms_departments(id) ON DELETE SET NULL,
    INDEX idx_area_code (area_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. DMS DOCUMENT TYPES
CREATE TABLE IF NOT EXISTS dms_document_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_code VARCHAR(20) UNIQUE NOT NULL,
    type_name VARCHAR(100) NOT NULL,
    numbering_format VARCHAR(100) NOT NULL,
    requires_approval BOOLEAN NOT NULL DEFAULT TRUE,
    min_approval_levels INT NOT NULL DEFAULT 1,
    retention_years INT NOT NULL DEFAULT 3,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_code (type_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. DMS LANGUAGES
CREATE TABLE IF NOT EXISTS dms_languages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lang_code VARCHAR(5) UNIQUE NOT NULL,
    lang_name VARCHAR(50) NOT NULL,
    native_name VARCHAR(50),
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_lang_code (lang_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. DMS REVIEW CYCLES
CREATE TABLE IF NOT EXISTS dms_review_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cycle_code VARCHAR(20) UNIQUE NOT NULL,
    cycle_name VARCHAR(100) NOT NULL,
    months INT NOT NULL,
    reminder_days JSON NOT NULL,
    mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_cycle_code (cycle_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. DMS NOTIFICATION TEMPLATES
CREATE TABLE IF NOT EXISTS dms_notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_code VARCHAR(50) UNIQUE NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    scenario ENUM('approval_request','approval_reminder','document_released','training_required','review_due','account_created','password_reset') NOT NULL,
    channel ENUM('whatsapp','email','sms') NOT NULL,
    language_id INT NOT NULL,
    message_template TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_template_code (template_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. DMS NOTIFICATION CHANNELS
CREATE TABLE IF NOT EXISTS dms_notification_channels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    channel_code VARCHAR(20) UNIQUE NOT NULL,
    channel_name VARCHAR(50) NOT NULL,
    channel_type ENUM('whatsapp','email','sms') NOT NULL,
    configuration JSON NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_channel_code (channel_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data Inserts
INSERT IGNORE INTO dms_sites (site_code, site_name, address_line1, city, state, postal_code, is_main_site) VALUES
('B75', 'Main Manufacturing Unit', 'Plot B-75, MIDC', 'Ahmednagar', 'Maharashtra', '414111', TRUE),
('G44', 'Unit-1 Manufacturing', 'G-44, MIDC', 'Ahmednagar', 'Maharashtra', '414111', FALSE);

INSERT IGNORE INTO dms_departments (dept_code, dept_name, description) VALUES
('QA', 'Quality Assurance', 'Quality control and assurance activities'),
('MFG', 'Manufacturing', 'Production and manufacturing operations'),
('ENG', 'Engineering', 'Design and engineering services'),
('MAINT', 'Maintenance', 'Equipment and facility maintenance'),
('SAFETY', 'Safety', 'Occupational health and safety');

INSERT IGNORE INTO dms_process_areas (area_code, area_name, description, safety_critical_default, department_id) VALUES
('WELD', 'Welding', 'Welding and joining operations', TRUE, 2),
('STITCH', 'Stitching', 'Fabric and upholstery operations', FALSE, 2),
('ASSY', 'Assembly', 'Final assembly operations', TRUE, 2),
('QC', 'Quality Control', 'Inspection and testing', TRUE, 1),
('INSP', 'Incoming Inspection', 'Incoming material inspection', TRUE, 1),
('PAINT', 'Painting', 'Surface coating operations', TRUE, 2),
('MAINT', 'Maintenance', 'Preventive and corrective maintenance', FALSE, 4);

INSERT IGNORE INTO dms_document_types (type_code, type_name, numbering_format, retention_years) VALUES
('POL', 'Policy', 'POL-{YYYY}-{####}', 7),
('SOP', 'Standard Operating Procedure', 'SOP-{SITE}-{PROCESS}-{YYYY}-{####}', 3),
('WI', 'Work Instruction', 'WI-{SITE}-{PROCESS}-{YYYY}-{####}', 3),
('FORM', 'Form', 'FORM-{DEPT}-{YYYY}-{####}', 3),
('DWG', 'Drawing', 'DWG-{PART}-{REV}', 10),
('PFMEA', 'Process FMEA', 'PFMEA-{PROCESS}-{YYYY}-{####}', 10),
('CP', 'Control Plan', 'CP-{PROCESS}-{YYYY}-{####}', 10);

INSERT IGNORE INTO dms_languages (lang_code, lang_name, native_name, is_default) VALUES
('en', 'English', 'English', TRUE),
('mr', 'Marathi', 'मराठी', FALSE),
('hi', 'Hindi', 'हिन्दी', FALSE),
('gu', 'Gujarati', 'ગુજરાતી', FALSE);

INSERT IGNORE INTO dms_review_cycles (cycle_code, cycle_name, months, reminder_days) VALUES
('ANNUAL', 'Annual Review', 12, '[30, 7, 1]'),
('BIENNIAL', 'Biennial Review', 24, '[60, 30, 7]'),
('QUARTERLY', 'Quarterly Review', 3, '[7, 1]');

INSERT IGNORE INTO dms_notification_channels (channel_code, channel_name, channel_type, configuration) VALUES
('EMAIL_SMTP', 'Email SMTP Server', 'email', '{\"smtp_host\": \"smtp.gmail.com\", \"smtp_port\": 587}'),
('WHATSAPP_MAIN', 'WhatsApp Business API', 'whatsapp', '{\"phone_number_id\": \"\", \"access_token\": \"\"}');

INSERT IGNORE INTO dms_notification_templates (template_code, template_name, scenario, channel, language_id, message_template) VALUES
('DOC_APPROVAL_REQ_EN_EMAIL', 'Document Approval Request', 'approval_request', 'email', 1, 'Document {{doc_number}} requires your approval. Due: {{due_date}}'),
('DOC_APPROVAL_REQ_EN_WA', 'Document Approval Request', 'approval_request', 'whatsapp', 1, 'Hello {{user_name}}, Document {{doc_number}} assigned for approval. Due: {{due_date}}');

COMMIT;
";
        
        // Clean up SQL and split into statements
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $executed = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, 'SELECT') === 0) continue;
            
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $skipped++;
                } else {
                    $errors++;
                    $deploymentResults['errors'][] = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        $deploymentResults['executed'] = $executed;
        $deploymentResults['skipped'] = $skipped;
        $deploymentResults['errors_count'] = $errors;
        
        if ($errors === 0) {
            $deploymentResults['status'] = 'success';
        } else {
            $deploymentResults['status'] = 'partial';
        }
        
    } catch (Exception $e) {
        $deploymentResults['status'] = 'failed';
        $deploymentResults['error'] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KaizenDMS Table Deployment</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; min-height: 100vh; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); max-width: 900px; margin: 0 auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .status-line { margin: 8px 0; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #007bff; }
        .status-line.success { border-left-color: #28a745; background: #d4edda; }
        .status-line.error { border-left-color: #dc3545; background: #f8d7da; }
        .status-line.warning { border-left-color: #ffc107; background: #fff3cd; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 15px; margin-bottom: 25px; }
        h2 { color: #555; margin: 25px 0 15px 0; padding: 10px; background: #e9ecef; border-radius: 6px; }
        .deploy-form { background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 20px 0; border: 2px solid #007bff; }
        .button { display: inline-block; padding: 15px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; font-weight: bold; transition: all 0.3s; cursor: pointer; border: none; font-size: 16px; }
        .button:hover { background: #1e7e34; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .button.secondary { background: #007bff; }
        .button.secondary:hover { background: #0056b3; }
        .button.danger { background: #dc3545; }
        .button.danger:hover { background: #c82333; }
        .results { background: #f1f3f4; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .results.success { background: #d4edda; border: 2px solid #28a745; }
        .results.error { background: #f8d7da; border: 2px solid #dc3545; }
        .results.partial { background: #fff3cd; border: 2px solid #ffc107; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; }
        .progress { background: #e9ecef; border-radius: 10px; height: 20px; margin: 10px 0; }
        .progress-bar { background: #28a745; height: 100%; border-radius: 10px; transition: width 0.3s; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        ul { padding-left: 20px; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 KaizenDMS Master Tables Deployment</h1>
        
        <?php if (!$deployed): ?>
        
        <div class="deploy-form">
            <h2>📋 Pre-Deployment Check</h2>
            <p>This tool will create the required DMS master tables in your database.</p>
            
            <h3>Tables to be created:</h3>
            <table>
                <tr><th>Table Name</th><th>Purpose</th><th>Sample Records</th></tr>
                <tr><td><strong>dms_sites</strong></td><td>Site/location management</td><td>B-75, G-44</td></tr>
                <tr><td><strong>dms_departments</strong></td><td>Department structure</td><td>QA, MFG, ENG, MAINT, SAFETY</td></tr>
                <tr><td><strong>dms_customers</strong></td><td>Customer data</td><td>Ready for your data</td></tr>
                <tr><td><strong>dms_suppliers</strong></td><td>Supplier qualification</td><td>Ready for your data</td></tr>
                <tr><td><strong>dms_process_areas</strong></td><td>Process classification</td><td>WELD, ASSY, QC, etc.</td></tr>
                <tr><td><strong>dms_document_types</strong></td><td>Document types</td><td>POL, SOP, WI, PFMEA, etc.</td></tr>
                <tr><td><strong>dms_languages</strong></td><td>Language support</td><td>English, Marathi, Hindi, Gujarati</td></tr>
                <tr><td><strong>dms_review_cycles</strong></td><td>Review scheduling</td><td>Annual, Biennial, Quarterly</td></tr>
                <tr><td><strong>dms_notification_templates</strong></td><td>Message templates</td><td>WhatsApp/Email templates</td></tr>
                <tr><td><strong>dms_notification_channels</strong></td><td>Communication channels</td><td>Email, WhatsApp settings</td></tr>
            </table>
            
            <h3>⚠️ Important Notes:</h3>
            <ul>
                <li>Existing tables will <strong>NOT</strong> be affected</li>
                <li>Only missing tables will be created</li>
                <li>Sample data will be inserted for immediate use</li>
                <li>This operation is safe and reversible</li>
            </ul>
            
            <form method="POST">
                <input type="hidden" name="deploy" value="true">
                <button type="submit" class="button" onclick="return confirm('Are you ready to deploy the master tables?')">
                    🚀 Deploy Master Tables Now
                </button>
            </form>
            
            <a href="web_db_check.php" class="button secondary">🔍 Check Database Status First</a>
        </div>
        
        <?php else: ?>
        
        <h2>📊 Deployment Results</h2>
        
        <?php if ($deploymentResults['status'] === 'success'): ?>
        
        <div class="results success">
            <div class="status-line success">🎉 Deployment completed successfully!</div>
            <?php if (isset($deploymentResults['connection'])): ?>
            <div class="status-line success"><?php echo $deploymentResults['connection']; ?></div>
            <?php endif; ?>
            <div class="status-line info">📊 Statements executed: <?php echo $deploymentResults['executed']; ?></div>
            <div class="status-line warning">⏭️ Statements skipped (already exist): <?php echo $deploymentResults['skipped']; ?></div>
            <div class="status-line success">❌ Errors: <?php echo $deploymentResults['errors_count']; ?></div>
        </div>
        
        <?php elseif ($deploymentResults['status'] === 'partial'): ?>
        
        <div class="results partial">
            <div class="status-line warning">⚠️ Deployment completed with some errors</div>
            <div class="status-line info">📊 Statements executed: <?php echo $deploymentResults['executed']; ?></div>
            <div class="status-line warning">⏭️ Statements skipped: <?php echo $deploymentResults['skipped']; ?></div>
            <div class="status-line error">❌ Errors: <?php echo $deploymentResults['errors_count']; ?></div>
            
            <?php if (isset($deploymentResults['errors'])): ?>
            <h3>Error Details:</h3>
            <ul>
                <?php foreach ($deploymentResults['errors'] as $error): ?>
                <li class="error"><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        
        <div class="results error">
            <div class="status-line error">❌ Deployment failed</div>
            <?php if (isset($deploymentResults['error'])): ?>
            <div class="status-line error">Error: <?php echo htmlspecialchars($deploymentResults['error']); ?></div>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
        <h2>🔍 Next Steps</h2>
        <div class="deploy-form">
            <?php if ($deploymentResults['status'] === 'success'): ?>
            <p><strong>✅ Deployment successful!</strong> Your DMS master tables are now ready.</p>
            <p>Recommended next steps:</p>
            <ul>
                <li>Verify all tables were created correctly</li>
                <li>Review the sample data and customize as needed</li>
                <li>Test your DMS application connectivity</li>
                <li>Begin Phase 1 DMS development</li>
            </ul>
            <?php else: ?>
            <p><strong>⚠️ Please review the errors above.</strong></p>
            <p>You may want to:</p>
            <ul>
                <li>Check database permissions</li>
                <li>Verify schema file integrity</li>
                <li>Try deployment again</li>
                <li>Contact technical support if issues persist</li>
            </ul>
            <?php endif; ?>
            
            <a href="web_db_check.php" class="button secondary">🔍 Check Database Status</a>
            <a href="web_deploy_tables.php" class="button">🔄 Deploy Again</a>
        </div>
        
        <?php endif; ?>
        
        <div class="footer">
            <p>KaizenDMS Master Tables Deployment | Generated at <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><a href="web_db_check.php">Database Status</a> | <a href="web_deploy_tables.php">Deploy Tables</a></p>
        </div>
    </div>
</body>
</html>