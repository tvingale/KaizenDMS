-- ================================================
-- KaizenDMS - Master Tables SQL Schema
-- Database: kaizenap_flow_db (Existing KaizenFlow Database)
-- Table Prefix: dms_ (Already configured in environment)
-- Phase 1: Universal DMS Implementation
-- ================================================

-- Execute on existing kaizenap_flow_db database
-- USE kaizenap_flow_db; -- Uncomment if running directly in MySQL
-- 
-- Tables use dms_ prefix as configured in .env (MODULE_PREFIX=dms_)
-- This integrates with existing KaizenAuth and KaizenTasks infrastructure

-- Set MySQL options
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ================================================
-- 1. DMS SITES - Site/Location Management
-- ================================================
CREATE TABLE dms_sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Short site identifier (e.g., B75, G44)',
    site_name VARCHAR(100) NOT NULL COMMENT 'Full site name',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'DMS-managed site/location master data';

-- ================================================
-- 2. DMS DEPARTMENTS - Department/Division Management
-- ================================================
CREATE TABLE dms_departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dept_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Department identifier (e.g., QA, MFG, ENG)',
    dept_name VARCHAR(100) NOT NULL COMMENT 'Department full name',
    description TEXT COMMENT 'Department description and responsibilities',
    parent_dept_id INT NULL COMMENT 'For hierarchical department structure',
    manager_name VARCHAR(100) COMMENT 'Department manager name',
    manager_email VARCHAR(100) COMMENT 'Department manager email for escalations',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Display order in lists',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_dept_id) REFERENCES dms_departments(id) ON DELETE SET NULL,
    INDEX idx_dept_code (dept_code),
    INDEX idx_parent (parent_dept_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'DMS-managed department master data';

-- ================================================
-- 3. DMS CUSTOMERS - Customer Management
-- ================================================
CREATE TABLE dms_customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Customer identifier (e.g., GSRTC, MSRTC)',
    customer_name VARCHAR(200) NOT NULL COMMENT 'Customer full name',
    customer_type ENUM('government','private','oem','distributor') NOT NULL DEFAULT 'private',
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(50),
    country VARCHAR(50) DEFAULT 'India',
    postal_code VARCHAR(20),
    contact_person VARCHAR(100) COMMENT 'Primary contact person name',
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    tax_id VARCHAR(50) COMMENT 'GST/Tax identification number',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_customer_code (customer_code),
    INDEX idx_customer_type (customer_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'DMS-managed customer master data';

-- ================================================
-- 4. DMS SUPPLIERS - Supplier Management
-- ================================================
CREATE TABLE dms_suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Supplier identifier',
    supplier_name VARCHAR(200) NOT NULL COMMENT 'Supplier full name',
    supplier_type ENUM('raw_material','component','service','tooling') NOT NULL DEFAULT 'component',
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(50),
    country VARCHAR(50) DEFAULT 'India',
    postal_code VARCHAR(20),
    contact_person VARCHAR(100) COMMENT 'Primary contact person name',
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    tax_id VARCHAR(50) COMMENT 'GST/Tax identification number',
    iso_certified BOOLEAN DEFAULT FALSE COMMENT 'ISO 9001 certification status',
    iatf_certified BOOLEAN DEFAULT FALSE COMMENT 'IATF 16949 certification status',
    approval_status ENUM('approved','conditional','rejected','under_review') DEFAULT 'under_review',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_supplier_code (supplier_code),
    INDEX idx_supplier_type (supplier_type),
    INDEX idx_approval_status (approval_status),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'DMS-managed supplier master data';

-- ================================================
-- 5. DMS PROCESS AREAS - Functional Process Classification
-- ================================================
CREATE TABLE dms_process_areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    area_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Process area identifier (e.g., WELD, ASSY, QC)',
    area_name VARCHAR(100) NOT NULL COMMENT 'Process area display name',
    description TEXT COMMENT 'Process area description and scope',
    parent_area_id INT NULL COMMENT 'For hierarchical process structure',
    safety_critical_default BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Default safety criticality',
    requires_special_training BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Special training requirement',
    department_id INT COMMENT 'Primary responsible department',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Display order in lists',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_area_id) REFERENCES dms_process_areas(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES dms_departments(id) ON DELETE SET NULL,
    INDEX idx_area_code (area_code),
    INDEX idx_parent (parent_area_id),
    INDEX idx_department (department_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'Universal process area classification (site-independent)';

-- ================================================
-- 6. DMS DOCUMENT TYPES - Document Classification
-- ================================================
CREATE TABLE dms_document_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Document type identifier (e.g., POL, SOP, WI)',
    type_name VARCHAR(100) NOT NULL COMMENT 'Document type display name',
    description TEXT COMMENT 'Document type description and usage',
    numbering_format VARCHAR(100) NOT NULL COMMENT 'Auto-numbering template (e.g., POL-{YYYY}-{####})',
    requires_approval BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Whether approval is required',
    min_approval_levels INT NOT NULL DEFAULT 1 COMMENT 'Minimum approval levels required',
    requires_training BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether training is required',
    retention_years INT NOT NULL DEFAULT 3 COMMENT 'Retention period in years',
    is_controlled BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Whether controlled copy management applies',
    default_language_id INT COMMENT 'Default language for this document type',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Display order in lists',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_code (type_code),
    INDEX idx_active (is_active),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'Document type classification and auto-numbering rules';

-- ================================================
-- 7. DMS LANGUAGES - Multi-language Support
-- ================================================
CREATE TABLE dms_languages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lang_code VARCHAR(5) UNIQUE NOT NULL COMMENT 'ISO language code (e.g., en, mr, hi)',
    lang_name VARCHAR(50) NOT NULL COMMENT 'Language name in English',
    native_name VARCHAR(50) COMMENT 'Language name in native script',
    rtl_flag BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Right-to-left text direction',
    is_default BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Default language flag',
    date_format VARCHAR(20) DEFAULT 'Y-m-d' COMMENT 'Date format for this language',
    decimal_separator VARCHAR(1) DEFAULT '.' COMMENT 'Decimal separator character',
    thousands_separator VARCHAR(1) DEFAULT ',' COMMENT 'Thousands separator character',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Display order in lists',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_lang_code (lang_code),
    INDEX idx_is_default (is_default),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'Multi-language support configuration';

-- ================================================
-- 8. DMS REVIEW CYCLES - Periodic Review Scheduling
-- ================================================
CREATE TABLE dms_review_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cycle_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Review cycle identifier (e.g., ANNUAL, BIENNIAL)',
    cycle_name VARCHAR(100) NOT NULL COMMENT 'Review cycle display name',
    description TEXT COMMENT 'Review cycle description and criteria',
    months INT NOT NULL COMMENT 'Review frequency in months',
    reminder_days JSON NOT NULL COMMENT 'Days before due date to send reminders [30, 7, 1]',
    escalation_days INT DEFAULT 7 COMMENT 'Days after due date to escalate',
    applicable_doc_types JSON COMMENT 'Document type codes this cycle applies to',
    mandatory BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Whether review is mandatory',
    escalation_chain JSON COMMENT 'Roles for escalation [document_owner, manager, qa_head]',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'Display order in lists',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_cycle_code (cycle_code),
    INDEX idx_months (months),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'Periodic review cycle definitions';

-- ================================================
-- 9. DMS NOTIFICATION TEMPLATES - Message Templates
-- ================================================
CREATE TABLE dms_notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Template identifier',
    template_name VARCHAR(100) NOT NULL COMMENT 'Template display name',
    scenario ENUM(
        'approval_request',
        'approval_reminder', 
        'document_released',
        'training_required',
        'review_due',
        'account_created',
        'password_reset',
        'document_updated',
        'document_obsoleted'
    ) NOT NULL COMMENT 'Notification scenario',
    channel ENUM('whatsapp','email','sms') NOT NULL COMMENT 'Communication channel',
    language_id INT NOT NULL COMMENT 'Template language',
    subject_template VARCHAR(200) COMMENT 'Subject line template (for email)',
    message_template TEXT NOT NULL COMMENT 'Message body with {{variables}}',
    template_variables JSON NOT NULL COMMENT 'Available variable placeholders',
    meta_approved BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'WhatsApp Meta approval status',
    meta_template_id VARCHAR(100) COMMENT 'Meta approved template ID',
    priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (language_id) REFERENCES dms_languages(id) ON DELETE RESTRICT,
    INDEX idx_template_code (template_code),
    INDEX idx_scenario_channel (scenario, channel),
    INDEX idx_language (language_id),
    INDEX idx_meta_approved (meta_approved),
    INDEX idx_active (is_active),
    UNIQUE KEY unique_scenario_channel_lang (scenario, channel, language_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'WhatsApp and email notification message templates';

-- ================================================
-- 10. DMS NOTIFICATION CHANNELS - Communication Channels
-- ================================================
CREATE TABLE dms_notification_channels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    channel_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Channel identifier',
    channel_name VARCHAR(50) NOT NULL COMMENT 'Channel display name',
    channel_type ENUM('whatsapp','email','sms','push') NOT NULL COMMENT 'Channel type',
    api_endpoint VARCHAR(255) COMMENT 'API endpoint URL for external services',
    configuration JSON NOT NULL COMMENT 'Channel-specific configuration',
    rate_limit_per_minute INT COMMENT 'Maximum messages per minute',
    cost_per_message DECIMAL(10,4) COMMENT 'Cost per message for analytics',
    priority_order INT NOT NULL DEFAULT 1 COMMENT 'Priority for fallback (1=highest)',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_health_check TIMESTAMP NULL COMMENT 'Last successful health check',
    health_status ENUM('healthy','degraded','down') DEFAULT 'healthy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_channel_code (channel_code),
    INDEX idx_channel_type (channel_type),
    INDEX idx_priority (priority_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'Communication channel configuration and status';

-- ================================================
-- SAMPLE DATA INSERTION
-- ================================================

-- Insert default sites
INSERT INTO dms_sites (site_code, site_name, address_line1, city, state, postal_code, is_main_site) VALUES
('B75', 'Main Manufacturing Unit', 'Plot B-75, MIDC', 'Ahmednagar', 'Maharashtra', '414111', TRUE),
('G44', 'Unit-1 Manufacturing', 'G-44, MIDC', 'Ahmednagar', 'Maharashtra', '414111', FALSE);

-- Insert default departments  
INSERT INTO dms_departments (dept_code, dept_name, description, manager_email) VALUES
('QA', 'Quality Assurance', 'Quality control and assurance activities', 'qa.manager@kaizen.com'),
('MFG', 'Manufacturing', 'Production and manufacturing operations', 'mfg.manager@kaizen.com'),
('ENG', 'Engineering', 'Design and engineering services', 'eng.manager@kaizen.com'),
('MAINT', 'Maintenance', 'Equipment and facility maintenance', 'maint.manager@kaizen.com'),
('SAFETY', 'Safety', 'Occupational health and safety', 'safety.manager@kaizen.com');

-- Insert default process areas
INSERT INTO dms_process_areas (area_code, area_name, description, safety_critical_default, department_id) VALUES
('WELD', 'Welding', 'Welding and joining operations', TRUE, (SELECT id FROM dms_departments WHERE dept_code = 'MFG')),
('STITCH', 'Stitching', 'Fabric and upholstery operations', FALSE, (SELECT id FROM dms_departments WHERE dept_code = 'MFG')),
('ASSY', 'Assembly', 'Final assembly operations', TRUE, (SELECT id FROM dms_departments WHERE dept_code = 'MFG')),
('QC', 'Quality Control', 'Inspection and testing', TRUE, (SELECT id FROM dms_departments WHERE dept_code = 'QA')),
('INSP', 'Incoming Inspection', 'Incoming material inspection', TRUE, (SELECT id FROM dms_departments WHERE dept_code = 'QA')),
('PAINT', 'Painting', 'Surface coating operations', TRUE, (SELECT id FROM dms_departments WHERE dept_code = 'MFG')),
('MAINT', 'Maintenance', 'Preventive and corrective maintenance', FALSE, (SELECT id FROM dms_departments WHERE dept_code = 'MAINT'));

-- Insert default languages
INSERT INTO dms_languages (lang_code, lang_name, native_name, is_default, sort_order) VALUES
('en', 'English', 'English', TRUE, 1),
('mr', 'Marathi', 'मराठी', FALSE, 2),
('hi', 'Hindi', 'हिन्दी', FALSE, 3),
('gu', 'Gujarati', 'ગુજરાતી', FALSE, 4);

-- Insert default document types
INSERT INTO dms_document_types (type_code, type_name, description, numbering_format, retention_years, sort_order) VALUES
('POL', 'Policy', 'Company policies and strategic documents', 'POL-{YYYY}-{####}', 7, 1),
('SOP', 'Standard Operating Procedure', 'Standardized work procedures', 'SOP-{SITE}-{PROCESS}-{YYYY}-{####}', 3, 2),
('WI', 'Work Instruction', 'Detailed work instructions', 'WI-{SITE}-{PROCESS}-{YYYY}-{####}', 3, 3),
('FORM', 'Form', 'Data collection and record forms', 'FORM-{DEPT}-{YYYY}-{####}', 3, 4),
('DWG', 'Drawing', 'Technical drawings and specifications', 'DWG-{PART}-{REV}', 10, 5),
('PFMEA', 'Process FMEA', 'Process Failure Mode and Effects Analysis', 'PFMEA-{PROCESS}-{YYYY}-{####}', 10, 6),
('CP', 'Control Plan', 'Process control plans', 'CP-{PROCESS}-{YYYY}-{####}', 10, 7);

-- Insert default review cycles
INSERT INTO dms_review_cycles (cycle_code, cycle_name, description, months, reminder_days, applicable_doc_types) VALUES
('ANNUAL', 'Annual Review', 'Annual document review for policies and procedures', 12, '[30, 7, 1]', '["POL", "SOP"]'),
('BIENNIAL', 'Biennial Review', 'Two-year review cycle for stable documents', 24, '[60, 30, 7]', '["FORM", "DWG"]'),
('QUARTERLY', 'Quarterly Review', 'Quarterly review for dynamic documents', 3, '[7, 1]', '["PFMEA", "CP"]');

-- Insert notification channel
INSERT INTO dms_notification_channels (channel_code, channel_name, channel_type, configuration, rate_limit_per_minute, priority_order) VALUES
('EMAIL_SMTP', 'Email SMTP Server', 'email', '{"smtp_host": "smtp.gmail.com", "smtp_port": 587, "encryption": "tls"}', 100, 2),
('WHATSAPP_MAIN', 'WhatsApp Business API', 'whatsapp', '{"phone_number_id": "", "access_token": "", "webhook_verify_token": ""}', 1000, 1);

-- Insert sample notification templates
INSERT INTO dms_notification_templates (template_code, template_name, scenario, channel, language_id, subject_template, message_template, template_variables) VALUES
('DOC_APPROVAL_REQ_EN_EMAIL', 'Document Approval Request (Email)', 'approval_request', 'email', 1, 'Document Approval Required: {{doc_number}}', 
'Dear {{user_name}},\n\nA document has been assigned to you for approval:\n\n📄 Document: {{doc_number}} - {{doc_title}}\n🏭 Site: {{site_name}}\n⏰ Due Date: {{due_date}}\n👤 Submitted by: {{submitter_name}}\n\nPlease review and approve at: {{approval_link}}\n\nBest regards,\nKaizenDMS Team', 
'["user_name", "doc_number", "doc_title", "site_name", "due_date", "submitter_name", "approval_link"]'),

('DOC_APPROVAL_REQ_EN_WA', 'Document Approval Request (WhatsApp)', 'approval_request', 'whatsapp', 1, NULL,
'Hello {{user_name}},\n\nA document has been assigned to you for approval:\n\n📄 *{{doc_number}}* - {{doc_title}}\n🏭 Site: {{site_name}}\n⏰ Due: {{due_date}}\n👤 Submitted by: {{submitter_name}}\n\nPlease review and approve at: {{approval_link}}\n\n- KaizenDMS Team',
'["user_name", "doc_number", "doc_title", "site_name", "due_date", "submitter_name", "approval_link"]');

-- Add foreign key constraint after languages table is populated
ALTER TABLE dms_document_types ADD FOREIGN KEY (default_language_id) REFERENCES dms_languages(id) ON DELETE SET NULL;

COMMIT;

-- ================================================
-- VERIFICATION QUERIES
-- ================================================

-- Verify DMS table creation in kaizenap_flow_db
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'kaizenap_flow_db'
AND TABLE_NAME LIKE 'dms_%'
ORDER BY TABLE_NAME;

-- Verify DMS table relationships
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'kaizenap_flow_db'
AND REFERENCED_TABLE_NAME IS NOT NULL
AND TABLE_NAME LIKE 'dms_%'
ORDER BY TABLE_NAME, COLUMN_NAME;

-- Show all tables in kaizenap_flow_db (DMS + existing KaizenFlow modules)
SELECT 
    TABLE_NAME,
    CASE 
        WHEN TABLE_NAME LIKE 'dms_%' THEN 'DMS Module'
        WHEN TABLE_NAME LIKE 'auth_%' THEN 'KaizenAuth'
        WHEN TABLE_NAME LIKE 'task_%' THEN 'KaizenTasks'
        WHEN TABLE_NAME LIKE 'kaizen_%' THEN 'KaizenCore'
        ELSE 'Other'
    END as MODULE,
    TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'kaizenap_flow_db'
ORDER BY MODULE, TABLE_NAME;