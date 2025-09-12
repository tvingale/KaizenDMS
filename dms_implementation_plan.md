# KaizenDMS Implementation Plan

## Executive Summary
This plan implements a comprehensive Document Management System (DMS) compliant with ISO 9001:2015 and IATF 16949:2016 standards for bus seat manufacturing operations. The system follows a **phased implementation approach** to ensure rapid deployment of core functionality followed by specialized business rules in later phases.

**Scope:** Design, manufacture and supply of bus seats and pressed/fabricated sheet-metal components  
**Sites:** Main – Plot B-75, MIDC, Ahmednagar 414111, MH, India. Unit-1 – G-44, MIDC, Ahmednagar 414111, MH, India.  

## Implementation Philosophy

**Phase 1 (Immediate):** Universal DMS functionality that applies to all organizations and document types
**Phase 2+ (Later):** Specialized business rules and industry-specific requirements managed by dedicated modules

This approach ensures:
- ✅ **Rapid Deployment:** Core system functional in 12 weeks
- ✅ **Reduced Complexity:** No industry-specific rules in core DMS
- ✅ **Future-Proof:** Specialized modules can be added without disrupting core system
- ✅ **Clean Architecture:** Universal document management vs specialized business logic separation

---

## 🎯 **Phase 1: Universal DMS (Immediate - 12 weeks)**

### **Requirements Included:**
- **B-1 to B-8:** All basic requirements (Single Repository, Lifecycle, Version Control, etc.)
- **E-1:** Basic Approval Workflows  
- **E-3:** Document Templates
- **E-7:** Periodic Review Reminders
- **E-8:** Controlled Copy Management ✅
- **E-9:** Read & Understood Training ✅  
- **E-10:** Document Retirement
- **G-2:** Multi-language Support
- **G-6:** Automated Backups
- **G-7:** Email Notifications

### **DMS Master Tables (Phase 1 - Self-Managed):**
- `dms_sites` (DMS-managed - Complete control over site data)
- `dms_departments` (DMS-managed - Complete control over department structure)
- `dms_customers` (DMS-managed - Complete control over customer data)
- `dms_suppliers` (DMS-managed - Complete control over supplier data)
- `dms_process_areas` (DMS-managed - Universal process classification)
- `dms_document_types` (DMS-managed - Document classification & numbering)
- `dms_languages` (DMS-managed - Multi-language support)
- `dms_review_cycles` (DMS-managed - Periodic review scheduling)
- `dms_notification_templates` (DMS-managed - WhatsApp/Email templates)
- `dms_notification_channels` (DMS-managed - Communication channels)

**✅ Self-Managed Benefits:**
- **No External Dependencies:** Complete control over data structure and seeding
- **Faster Implementation:** No coordination with KaizenMasters team required
- **Custom Optimization:** Tables designed specifically for DMS requirements
- **Easier Maintenance:** Direct access to modify and extend tables as needed

---

## 🔄 **Phase 2: Specialized Modules Integration (Later - 8 weeks)**

### **Requirements Moved to Phase 2:**
- **E-2:** Safety Gates (PSO Approval) → **Safety Module**
  - Reason: Industry-specific safety rules, not universal DMS functionality
  - Implementation: Safety Module will monitor DMS documents and apply PSO rules
  
- **E-5:** KaizenTasks Integration → **External System Integration**  
  - Reason: External system dependency, not core DMS functionality
  - Implementation: API integration layer after core DMS is stable
  
- **E-6:** PFMEA ↔ Control Plan ↔ Work Instruction Linking → **Quality Module**
  - Reason: Quality-specific business logic and relationships
  - Implementation: Quality Module will manage document linkages and validations
  
- **G-3:** Mobile Access → **UI/UX Enhancement**
  - Reason: Interface enhancement, not core functionality
  - Implementation: Mobile app after web system is established
  
- **G-5:** Digital Signatures → **Security Enhancement**
  - Reason: Advanced security feature requiring specialized infrastructure
  - Implementation: Security module with PKI integration

### **Master Tables (Phase 2):**
- `master_safety_characteristics` → **Safety Module** (not core DMS)
- `master_psa_rules` → **Safety Module** (not core DMS)
- Customer requirements → **Customer Requirements Module** (not core DMS)

---

## 🚀 **Phase 3: Advanced Features (Future - 6 weeks)**

### **Requirements for Future Implementation:**
- **E-4:** Bulk Operations → Advanced efficiency features
- **G-1:** Advanced Analytics → Dedicated reporting and analytics module  
- **G-4:** OCR Integration → AI and document processing features

### **Benefits of Phased Approach:**
1. **Quick Time-to-Market:** Phase 1 delivers functional DMS in 12 weeks
2. **Risk Mitigation:** Core system proven before adding complexity
3. **User Adoption:** Users learn basic system before advanced features
4. **Maintenance Simplicity:** Core DMS remains stable while specialized modules evolve
5. **Cost Control:** Immediate value delivery with optional advanced features

---

## 📋 **Phase 0: Database Foundation (Priority 1 - Critical)**

### Existing Tables (To Be Leveraged)
These tables already exist in the KaizenAuth/Tasks infrastructure and will be used by DMS:

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `auth_users` | User authentication | `id`, `username`, `email`, `name` |
| `auth_roles` | Role management | `id`, `name`, `permissions` |
| `auth_user_roles` | User-role mapping | `user_id`, `role_id` |
| `kaizen_tasks` | Workflow automation | `id`, `type`, `assignee_id`, `status`, `data` |
| `task_types` | Task categorization | `id`, `name`, `priority` |

### Master Tables (To Be Created by KaizenMasters Module)
These master data tables will be centrally managed:

```sql
-- KaizenAuth User Tables (via API):
users (id, username, email, name, mobile)
user_roles 
user_permissions

-- KaizenTasks Tables (for integration):
tasks
task_assignments
task_escalations

-- Existing Access Control Tables:
task_user_roles (user_id, role_id, status)
task_roles (id, name, description)  
task_permissions (id, name, description)
task_role_permissions (role_id, permission_id)
```


#### 1.2.1 Document Types
```sql
CREATE TABLE master_doc_types (
    doc_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_code VARCHAR(20) UNIQUE NOT NULL, -- POL, SOP, WI, PFMEA, CP, DWG, FORM, PPAP
    type_name VARCHAR(100) NOT NULL,
    template_path VARCHAR(500),
    numbering_format VARCHAR(100), -- e.g., "POL-{YYYY}-{####}"
    requires_pso BOOLEAN DEFAULT FALSE,
    retention_years INT DEFAULT 3,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Owner: QA Doc Control
-- Sample Data:
-- ('POL', 'Policy', 'POL-{YYYY}-{####}', FALSE, 3)
-- ('PFMEA', 'Process FMEA', 'PFMEA-{YYYY}-{####}', TRUE, 10)
-- ('WI', 'Work Instruction', 'WI-{YYYY}-{####}', FALSE, 3)
```

#### 1.2.2 Process Areas
```sql
CREATE TABLE master_process_areas (
    area_id INT PRIMARY KEY AUTO_INCREMENT,
    area_code VARCHAR(20) UNIQUE NOT NULL, -- WELD, STITCH, ASSY, PAINT, QC, INSP
    area_name VARCHAR(100) NOT NULL, -- Welding, Stitching, Assembly, etc.
    department VARCHAR(100),
    safety_critical_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Owner: QA / Manufacturing Engineering
-- Sample Data:
-- ('WELD', 'Welding', 'Manufacturing', TRUE)
-- ('ASSY', 'Assembly', 'Manufacturing', TRUE) 
-- ('QC', 'Quality Control', 'Quality', FALSE)
```

#### 1.2.3 Sites & Lines
```sql
CREATE TABLE master_sites (
    site_id INT PRIMARY KEY AUTO_INCREMENT,
    site_code VARCHAR(20) UNIQUE NOT NULL, -- MAIN, UNIT1
    site_name VARCHAR(100) NOT NULL,
    address TEXT, -- Plot B-75, MIDC, Ahmednagar 414111, MH, India
    is_main BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: Management Representative

CREATE TABLE master_lines (
    line_id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    line_code VARCHAR(20) NOT NULL,
    line_name VARCHAR(100),
    shift_pattern VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (site_id) REFERENCES master_sites(site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: Production Planning
```

#### 1.2.4 Manufacturing Data
```sql
CREATE TABLE master_shifts (
    shift_id INT PRIMARY KEY AUTO_INCREMENT,
    shift_code VARCHAR(10) NOT NULL, -- A, B, C
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: HR/Production

CREATE TABLE master_models (
    model_id INT PRIMARY KEY AUTO_INCREMENT,
    program VARCHAR(100), -- Slim, GSRTC
    model_name VARCHAR(100) NOT NULL,
    customer_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: Design / PPC

CREATE TABLE master_customers (
    cust_id INT PRIMARY KEY AUTO_INCREMENT,
    cust_code VARCHAR(50) UNIQUE NOT NULL,
    cust_name VARCHAR(255) NOT NULL,
    csr_flags JSON, -- Customer specific requirements
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: Sales / QA
```

#### 1.2.5 Configuration Masters
```sql
CREATE TABLE master_languages (
    lang_id INT PRIMARY KEY AUTO_INCREMENT,
    lang_code VARCHAR(5) NOT NULL, -- EN, MR, HI
    lang_name VARCHAR(50) NOT NULL, -- English, Marathi, Hindi
    rtl_flag BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: QA

CREATE TABLE master_retention_classes (
    retention_id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(100) NOT NULL, -- Standard, Safety Critical, PPAP, Legal
    years_to_keep INT NOT NULL, -- 3, 10, 15, 7
    regulatory_requirement TEXT,
    auto_archive BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: QA Doc Control

CREATE TABLE master_safety_characteristics (
    sc_id INT PRIMARY KEY AUTO_INCREMENT,
    sc_code VARCHAR(50) UNIQUE,
    description TEXT, -- Seat belt anchorage, Weld strength
    category VARCHAR(100), -- belts, anchorages, welds
    needs_pso BOOLEAN DEFAULT TRUE,
    regulatory_ref VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: PSO

CREATE TABLE master_review_cycles (
    cycle_id INT PRIMARY KEY AUTO_INCREMENT,
    cycle_name VARCHAR(100),
    months INT NOT NULL, -- 12, 24
    document_types JSON, -- Which doc types use this cycle
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: QA

CREATE TABLE master_notification_channels (
    channel_id INT PRIMARY KEY AUTO_INCREMENT,
    channel_type VARCHAR(50), -- Email, WhatsApp, SMS
    config_json JSON, -- API keys, endpoints
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: IT / Management Representative

CREATE TABLE master_psa_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    trigger_area VARCHAR(100), -- belts, welds, anchorages
    condition_regex VARCHAR(500), -- Pattern to detect
    pso_required BOOLEAN DEFAULT TRUE,
    escalation_hours INT DEFAULT 24
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Owner: PSO (Product Safety Officer)
```

### 1.3 NEW DMS-SPECIFIC TABLES

These tables are specific to the Document Management System:

#### 1.3.1 Core Document Tables
```sql
CREATE TABLE dms_documents (
    doc_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_number VARCHAR(50) UNIQUE NOT NULL, -- Auto-generated DMS-2025-001234
    title VARCHAR(255) NOT NULL,
    doc_type_id INT NOT NULL,
    category_id INT NULL,
    process_area_id INT NOT NULL,
    site_id INT NOT NULL,
    model_id INT NULL,
    customer_id INT NULL,
    current_revision VARCHAR(10) DEFAULT 'A', -- A, B, C...
    status ENUM('draft','review','approved','effective','obsolete') DEFAULT 'draft',
    lifecycle_stage VARCHAR(50),
    owner_user_id INT NOT NULL,
    author_user_id INT NOT NULL,
    safety_critical BOOLEAN DEFAULT FALSE,
    pso_required BOOLEAN DEFAULT FALSE,
    pso_approved_by INT NULL,
    pso_approved_at TIMESTAMP NULL,
    retention_class_id INT NOT NULL,
    effective_date DATE NULL,
    expiry_date DATE NULL,
    review_cycle_id INT NULL,
    next_review_date DATE NULL,
    language_id INT DEFAULT 1,
    parent_doc_id BIGINT NULL, -- For translations/variants
    metadata_json JSON, -- Flexible additional fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_status (status),
    INDEX idx_doc_type (doc_type_id),
    INDEX idx_area (process_area_id),
    INDEX idx_safety (safety_critical),
    INDEX idx_owner (owner_user_id),
    INDEX idx_next_review (next_review_date),
    
    -- Foreign Keys
    FOREIGN KEY (doc_type_id) REFERENCES master_doc_types(doc_type_id),
    FOREIGN KEY (category_id) REFERENCES dms_categories(category_id),
    FOREIGN KEY (process_area_id) REFERENCES master_process_areas(area_id),
    FOREIGN KEY (site_id) REFERENCES master_sites(site_id),
    FOREIGN KEY (model_id) REFERENCES master_models(model_id),
    FOREIGN KEY (customer_id) REFERENCES master_customers(cust_id),
    FOREIGN KEY (retention_class_id) REFERENCES master_retention_classes(retention_id),
    FOREIGN KEY (review_cycle_id) REFERENCES master_review_cycles(cycle_id),
    FOREIGN KEY (language_id) REFERENCES master_languages(lang_id),
    FOREIGN KEY (parent_doc_id) REFERENCES dms_documents(doc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#000000',
    icon VARCHAR(50),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES dms_categories(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.3.2 Revision Control
```sql
CREATE TABLE dms_revisions (
    revision_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    revision_number VARCHAR(10) NOT NULL,
    revision_reason TEXT,
    change_description TEXT,
    safety_changes JSON, -- List of safety-related changes for PSO review
    file_path VARCHAR(500),
    file_hash VARCHAR(64), -- SHA256 for integrity verification
    file_size BIGINT,
    mime_type VARCHAR(100),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_current BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    UNIQUE KEY unique_doc_revision (doc_id, revision_number),
    INDEX idx_doc_rev (doc_id, revision_number),
    INDEX idx_current (is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_lifecycle_history (
    history_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    revision_id BIGINT NULL,
    from_status VARCHAR(50),
    to_status VARCHAR(50) NOT NULL,
    action VARCHAR(100), -- submit_review, approve, reject, obsolete
    comments TEXT,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    signature_data TEXT, -- E-signature JSON data
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (revision_id) REFERENCES dms_revisions(revision_id),
    INDEX idx_doc_lifecycle (doc_id, performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.3.3 Approval & Review System
```sql
CREATE TABLE dms_approval_matrix (
    matrix_id INT PRIMARY KEY AUTO_INCREMENT,
    doc_type_id INT NOT NULL,
    process_area_id INT NULL, -- Specific to area or global
    approval_level INT NOT NULL, -- 1, 2, 3 for sequential approvals
    role_required VARCHAR(50),
    user_id INT NULL, -- Specific user override
    is_optional BOOLEAN DEFAULT FALSE,
    pso_required_level INT NULL, -- At which level PSO is required
    
    FOREIGN KEY (doc_type_id) REFERENCES master_doc_types(doc_type_id),
    FOREIGN KEY (process_area_id) REFERENCES master_process_areas(area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_reviews (
    review_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    revision_id BIGINT NOT NULL,
    reviewer_id INT NOT NULL,
    review_type ENUM('technical','safety','quality','management') DEFAULT 'technical',
    review_status ENUM('pending','approved','rejected','conditional') DEFAULT 'pending',
    review_comments TEXT,
    conditions_to_meet TEXT NULL, -- If conditionally approved
    reviewed_at TIMESTAMP NULL,
    due_date DATE NOT NULL,
    escalation_level INT DEFAULT 0,
    kaizen_task_id VARCHAR(100) NULL, -- Link to KaizenTasks
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (revision_id) REFERENCES dms_revisions(revision_id),
    INDEX idx_pending (review_status, due_date),
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_kaizen_task (kaizen_task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.3.4 Training & Competence
```sql
CREATE TABLE dms_training_records (
    training_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    revision_id BIGINT NOT NULL,
    user_id INT NOT NULL,
    training_type ENUM('read','quiz','practical') DEFAULT 'read',
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    time_spent_seconds INT DEFAULT 0,
    score DECIMAL(5,2) NULL, -- For quiz/practical assessments
    pass_threshold DECIMAL(5,2) DEFAULT 80.00,
    status ENUM('pending','in_progress','completed','failed') DEFAULT 'pending',
    attempts INT DEFAULT 1,
    certificate_number VARCHAR(100) NULL,
    certificate_path VARCHAR(500) NULL,
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (revision_id) REFERENCES dms_revisions(revision_id),
    INDEX idx_user_training (user_id, status),
    INDEX idx_doc_training (doc_id, revision_id),
    UNIQUE KEY unique_user_doc_revision (user_id, doc_id, revision_id, training_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_training_requirements (
    requirement_id INT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    role_id INT NULL, -- Specific role
    department VARCHAR(100) NULL, -- Department requirement
    site_id INT NULL, -- Site-specific requirement
    line_id INT NULL, -- Line-specific requirement
    mandatory BOOLEAN DEFAULT TRUE,
    pass_threshold DECIMAL(5,2) DEFAULT 80.00,
    retraining_months INT DEFAULT 12, -- How often retraining required
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (site_id) REFERENCES master_sites(site_id),
    FOREIGN KEY (line_id) REFERENCES master_lines(line_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_quiz_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('single_choice','multiple_choice','true_false') DEFAULT 'single_choice',
    options_json JSON, -- ["Option A", "Option B", "Option C", "Option D"]
    correct_answer JSON, -- For single: "A", for multiple: ["A","C"]
    points INT DEFAULT 1,
    explanation TEXT, -- Why this is the correct answer
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.3.5 Document Linking & Dependencies
```sql
CREATE TABLE dms_document_links (
    link_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source_doc_id BIGINT NOT NULL,
    target_doc_id BIGINT NOT NULL,
    link_type ENUM('parent','child','reference','supersedes','related') DEFAULT 'reference',
    link_strength ENUM('mandatory','recommended','informational') DEFAULT 'recommended',
    validation_required BOOLEAN DEFAULT FALSE,
    sync_status ENUM('synced','pending','conflict') DEFAULT 'synced',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (source_doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (target_doc_id) REFERENCES dms_documents(doc_id),
    INDEX idx_source (source_doc_id),
    INDEX idx_target (target_doc_id),
    UNIQUE KEY unique_link (source_doc_id, target_doc_id, link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_process_links (
    process_link_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    pfmea_doc_id BIGINT NULL, -- PFMEA document
    control_plan_doc_id BIGINT NULL, -- Control Plan document
    work_instruction_doc_id BIGINT NULL, -- Work Instruction document
    process_step VARCHAR(100),
    characteristic VARCHAR(255),
    characteristic_type ENUM('safety','key','standard') DEFAULT 'standard',
    sync_status ENUM('synced','pending','conflict') DEFAULT 'pending',
    last_sync_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pfmea_doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (control_plan_doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (work_instruction_doc_id) REFERENCES dms_documents(doc_id),
    INDEX idx_pfmea (pfmea_doc_id),
    INDEX idx_cp (control_plan_doc_id),
    INDEX idx_wi (work_instruction_doc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.3.6 Controlled Distribution
```sql
CREATE TABLE dms_controlled_copies (
    copy_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    revision_id BIGINT NOT NULL,
    copy_number VARCHAR(50) UNIQUE NOT NULL, -- CC-001, CC-002
    issued_to_user INT NULL,
    issued_to_location VARCHAR(255),
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    retrieval_status ENUM('active','expired','retrieved','lost') DEFAULT 'active',
    retrieval_date DATE NULL,
    retrieval_task_id VARCHAR(100) NULL, -- KaizenTasks task ID
    qr_code VARCHAR(100) UNIQUE, -- QR code data
    watermark_text VARCHAR(255),
    printed_by INT NULL,
    print_reason TEXT,
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    FOREIGN KEY (revision_id) REFERENCES dms_revisions(revision_id),
    INDEX idx_active_copies (retrieval_status, expiry_date),
    INDEX idx_location (issued_to_location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.3.7 Audit & Monitoring
```sql
CREATE TABLE dms_audit_log (
    audit_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL, -- document, review, training, copy
    entity_id BIGINT NOT NULL,
    action VARCHAR(100) NOT NULL,
    old_value JSON,
    new_value JSON,
    user_id INT NOT NULL,
    user_ip VARCHAR(45),
    user_agent TEXT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user_audit (user_id, performed_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_review_schedule (
    schedule_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    review_cycle_months INT NOT NULL,
    last_review_date DATE,
    next_review_date DATE NOT NULL,
    reviewer_id INT,
    reminder_task_id VARCHAR(100) NULL, -- KaizenTasks reminder task
    status ENUM('pending','in_progress','completed','overdue') DEFAULT 'pending',
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    INDEX idx_due_reviews (next_review_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_effectiveness_verification (
    verification_id INT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    verification_date DATE NOT NULL,
    verification_method TEXT, -- How effectiveness was verified
    verified_by INT NOT NULL,
    result ENUM('effective','ineffective','partial') NOT NULL,
    evidence_path VARCHAR(500), -- Supporting evidence file
    improvement_actions TEXT,
    next_verification_date DATE,
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dms_access_log (
    access_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    doc_id BIGINT NOT NULL,
    user_id INT NOT NULL,
    access_type ENUM('view','download','print','kiosk_view') NOT NULL,
    access_location VARCHAR(255),
    kiosk_id VARCHAR(100) NULL, -- For shop floor access
    device_info JSON, -- Browser, IP, etc.
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (doc_id) REFERENCES dms_documents(doc_id),
    INDEX idx_doc_access (doc_id, accessed_at),
    INDEX idx_user_access (user_id, accessed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 2. FUNCTIONALITY IMPLEMENTATION

### 2.1 BASIC COMPLIANCE FEATURES (B-1 to B-8)

#### B-1: Single Repository for All Controlled Documents
**Requirement:** Core clause 7.5 (ISO 9001/IATF 16949)

**Functions to Implement:**
```php
// Document Management Core Functions
function createDocument($data) {
    // Generate unique document number
    // Validate metadata completeness
    // Create document record in dms_documents
    // Create initial revision (Rev A)
    // Log creation in audit trail
}

function uploadDocumentFile($docId, $file) {
    // Validate file type (PDF, DOC, etc.)
    // Generate secure file path using naming convention
    // Calculate SHA256 hash for integrity
    // Store in revision table with metadata
    // Create controlled copy tracking
}

function getDocumentById($docId) {
    // Retrieve document with all related data
    // Include current revision information
    // Include approval status
    // Include training completion status
}

function listDocuments($filters = []) {
    // Support filtering by type, area, status, date
    // Return paginated results
    // Include security-based visibility
    // Sort by relevance and date
}

function searchDocuments($query, $filters = []) {
    // Full-text search in title, content, metadata
    // Support advanced search operators
    // Search across revisions if required
    // Log search activities for audit
}
```

**File Storage Structure:**
```
storage/dms/
├── 2025/
│   ├── 01/
│   │   ├── DMS-2025-01-15_POL_001_Quality-Policy_RevA.pdf
│   │   ├── DMS-2025-01-15_WI_002_Welding-Process_RevA.pdf
│   │   └── ...
│   ├── 02/
│   └── ...
├── archive/
└── temp/
```

#### B-2: Document Metadata & Auto-numbering
**Requirement:** Traceability and audit readiness

**Functions to Implement:**
```php
function generateDocumentNumber($docTypeId, $year = null) {
    $year = $year ?? date('Y');
    $docType = getDocumentType($docTypeId);
    $format = $docType->numbering_format; // e.g., "POL-{YYYY}-{####}"
    
    $lastNumber = getLastNumberForType($docTypeId, $year);
    $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    
    return str_replace(['{YYYY}', '{####}'], [$year, $newNumber], $format);
}

function updateDocumentMetadata($docId, $metadata) {
    // Validate metadata against document type requirements
    // Update metadata_json field
    // Track changes in audit log
    // Trigger revalidation if safety-critical fields changed
}

function validateMetadata($docType, $metadata) {
    // Check required fields based on document type
    // Validate format and constraints
    // Check safety characteristic requirements
    // Return validation errors if any
}

function getNextRevisionLetter($currentRevision) {
    // Handle revision progression: A -> B -> C ... -> Z -> AA -> AB
    $ascii = ord($currentRevision);
    if ($ascii < ord('Z')) {
        return chr($ascii + 1);
    } else {
        // Handle multi-character revisions
        return incrementMultiCharRevision($currentRevision);
    }
}
```

#### B-3: Document Lifecycle with E-signature
**Requirement:** Ensures only validated information reaches shop floor

**Functions to Implement:**
```php
function submitForReview($docId, $reviewers) {
    // Change status from 'draft' to 'review'
    // Create review records for each reviewer
    // Generate KaizenTasks for reviewers
    // Set due dates based on document urgency
    // Send notifications
    // Log state transition
}

function approveDocument($docId, $approverId, $signature = null) {
    // Validate approver has authority for this document type
    // Record approval with timestamp and signature
    // Check if all required approvals received
    // Progress to next approval level or mark as approved
    // Create KaizenTasks for post-approval activities
    // Log approval action
}

function rejectDocument($docId, $rejectorId, $reason) {
    // Change status back to 'draft'
    // Record rejection reason
    // Notify document owner
    // Create task for addressing rejection comments
    // Clear any pending approvals
    // Log rejection with reasons
}

function makeEffective($docId, $effectiveDate = null) {
    // Validate all prerequisites met:
    //   - All approvals complete
    //   - PSO approval if required
    //   - Training assignments created
    //   - Linked documents validated
    // Set effective date
    // Obsolete previous revision if exists
    // Create controlled copies if required
    // Generate training assignments
    // Send effectiveness notifications
}

function obsoleteDocument($docId, $reason, $replacementDocId = null) {
    // Change status to 'obsolete'
    // Set expiry date
    // Mark all controlled copies for retrieval
    // Update linked documents
    // Archive physical copies
    // Maintain audit trail per retention policy
}

function validateStateTransition($fromStatus, $toStatus, $docId) {
    // Check if transition is allowed
    // Validate business rules
    // Check user permissions
    // Ensure prerequisites are met
}
```

**E-signature Integration:**
```php
function recordESignature($docId, $userId, $action, $signatureData) {
    // Store signature data securely
    // Include timestamp and IP address
    // Validate signature authenticity
    // Link to specific document revision
    // Maintain non-repudiation trail
}
```

#### B-4: Controlled PDF Output with Watermark & QR Code
**Requirement:** Prevents misuse of uncontrolled prints

**Functions to Implement:**
```php
function generateControlledPDF($docId, $copyNumber, $issuedTo = null) {
    $document = getDocumentById($docId);
    $revision = getCurrentRevision($docId);
    
    // Load original PDF
    $originalPdf = $revision->file_path;
    
    // Generate watermark text
    $watermarkText = generateWatermarkText($document, $copyNumber, $issuedTo);
    
    // Generate QR code with verification data
    $qrData = [
        'doc_id' => $docId,
        'doc_number' => $document->doc_number,
        'revision' => $document->current_revision,
        'copy_number' => $copyNumber,
        'issued_date' => date('Y-m-d'),
        'verification_url' => APP_URL . "/verify?token=" . generateVerificationToken($docId, $copyNumber)
    ];
    
    $qrCode = generateQRCode(json_encode($qrData));
    
    // Apply watermark and QR code to PDF
    $controlledPdf = addWatermarkAndQR($originalPdf, $watermarkText, $qrCode);
    
    // Track controlled copy issuance
    recordControlledCopy($docId, $copyNumber, $issuedTo);
    
    return $controlledPdf;
}

function addWatermarkAndQR($pdfPath, $watermarkText, $qrCodeData) {
    // Use FPDI/TCPDF to:
    // 1. Add diagonal watermark on each page
    // 2. Add QR code in top-right corner
    // 3. Add copy control information in footer
    // 4. Restrict PDF permissions (no editing, printing controlled)
}

function generateWatermarkText($document, $copyNumber, $issuedTo) {
    return sprintf(
        "CONTROLLED COPY #%s\nIssued to: %s\nDate: %s\nDoc: %s Rev %s",
        $copyNumber,
        $issuedTo ?? 'UNASSIGNED',
        date('Y-m-d'),
        $document->doc_number,
        $document->current_revision
    );
}

function trackPrintRequest($docId, $userId, $reason) {
    // Log all print requests
    // Require justification for printing
    // Generate controlled copy number
    // Set expiry date for printed copy
    // Create retrieval task if needed
}
```

#### B-5: Search & Filters
**Requirement:** Users must find the latest document fast

**Functions to Implement:**
```php
function searchByDocNumber($docNumber) {
    // Exact match on document number
    // Include partial matches
    // Return with revision history
}

function searchByTitle($title) {
    // Full-text search in title field
    // Support wildcards and phrases
    // Rank by relevance
}

function filterByArea($areaId) {
    // Filter documents by process area
    // Include child areas if hierarchical
    // Show user's area by default
}

function filterByType($typeId) {
    // Filter by document type
    // Support multiple type selection
    // Include type-specific metadata
}

function filterByStatus($status) {
    // Filter by lifecycle status
    // Support multiple status selection
    // Default to 'effective' for normal users
}

function filterByDateRange($startDate, $endDate, $dateType = 'created') {
    // Support filtering by:
    // - Created date
    // - Effective date
    // - Last modified date
    // - Review due date
}

function buildAdvancedQuery($filters) {
    // Combine multiple filters with AND/OR logic
    // Support nested conditions
    // Optimize query performance
    // Apply security filters
    
    $query = "SELECT d.*, dt.type_name, pa.area_name 
              FROM dms_documents d
              LEFT JOIN master_doc_types dt ON d.doc_type_id = dt.doc_type_id
              LEFT JOIN master_process_areas pa ON d.process_area_id = pa.area_id
              WHERE 1=1";
              
    if (!empty($filters['status'])) {
        $query .= " AND d.status IN (" . implode(',', $filters['status']) . ")";
    }
    
    if (!empty($filters['area_id'])) {
        $query .= " AND d.process_area_id = " . (int)$filters['area_id'];
    }
    
    // Add user access control filters
    $query .= " AND " . buildAccessControlFilter($userId);
    
    return $query;
}
```

#### B-6: Immutable Audit Trail
**Requirement:** Mandatory for third-party audits

**Functions to Implement:**
```php
function logDocumentAction($docId, $action, $oldValue = null, $newValue = null) {
    global $user;
    
    $auditData = [
        'entity_type' => 'document',
        'entity_id' => $docId,
        'action' => $action,
        'old_value' => json_encode($oldValue),
        'new_value' => json_encode($newValue),
        'user_id' => $user['id'],
        'user_ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'performed_at' => date('Y-m-d H:i:s')
    ];
    
    // Insert with no update capability (immutable)
    $db->insert('dms_audit_log', $auditData);
    
    // Optional: Sign audit entry with hash for tamper detection
    signAuditEntry($db->lastInsertId());
}

function getDocumentHistory($docId) {
    // Retrieve complete history for a document
    // Include all lifecycle changes
    // Include approval/rejection records
    // Include training records
    // Include access records
    // Sort chronologically
}

function getAuditTrail($filters = []) {
    // Retrieve audit records with filters:
    // - Date range
    // - User ID
    // - Action type
    // - Document type
    // - Entity type
    
    // Include user names and document titles
    // Paginate results for performance
    // Export capabilities for external audits
}

function verifyAuditIntegrity($auditId) {
    // Verify audit entry has not been tampered with
    // Check hash signatures if implemented
    // Validate sequential numbering
    // Report any inconsistencies
}

function exportAuditLog($dateRange, $format = 'excel') {
    // Export audit trail for external auditors
    // Include all required ISO fields
    // Support Excel, PDF, CSV formats
    // Include digital signatures
}
```

#### B-7: Retention Rules
**Requirement:** Meets legal & IATF retention requirements

**Functions to Implement:**
```php
function applyRetentionPolicy($docId) {
    $document = getDocumentById($docId);
    $retentionClass = getRetentionClass($document->retention_class_id);
    
    $retentionEndDate = date('Y-m-d', strtotime(
        $document->created_at . ' + ' . $retentionClass->years_to_keep . ' years'
    ));
    
    // Update document with retention information
    updateDocumentRetention($docId, $retentionEndDate);
    
    // Schedule archival if auto-archive enabled
    if ($retentionClass->auto_archive) {
        scheduleArchival($docId, $retentionEndDate);
    }
}

function checkRetentionExpiry() {
    // Run daily/weekly job to check expiring documents
    $expiringDocs = getExpiringDocuments(30); // 30 days warning
    
    foreach ($expiringDocs as $doc) {
        // Send retention expiry warnings
        // Create tasks for retention review
        // Prepare for archival or extension
    }
}

function archiveDocument($docId) {
    // Move document files to archive storage
    // Update database status
    // Maintain read-only access for audits
    // Preserve all audit trail data
    // Generate archival certificate
}

function scheduleDocumentPurge($docId, $purgeDate) {
    // Schedule permanent deletion after retention period
    // Ensure regulatory compliance before purging
    // Maintain summary records for audit trail
    // Secure deletion of sensitive data
}

function getRetentionReport() {
    // Generate report showing:
    // - Documents by retention class
    // - Upcoming retention expirations
    // - Archived documents count
    // - Compliance status
}
```

#### B-8: KaizenTasks Integration
**Requirement:** Keeps workflow moving without email chaos

**Functions to Implement:**
```php
function createKaizenTask($event, $docId, $assignedTo, $priority = 'medium') {
    $document = getDocumentById($docId);
    
    $taskData = [
        'type' => $event, // DOC_REVIEW, DOC_APPROVED, DOC_TRAINING, etc.
        'title' => generateTaskTitle($event, $document),
        'description' => generateTaskDescription($event, $document),
        'priority' => $document->safety_critical ? 'critical' : $priority,
        'assigned_to' => $assignedTo,
        'due_date' => calculateDueDate($event, $document),
        'metadata' => [
            'doc_id' => $docId,
            'doc_number' => $document->doc_number,
            'revision' => $document->current_revision,
            'site_id' => $document->site_id,
            'area' => $document->process_area,
            'safety_critical' => $document->safety_critical,
            'tags' => ['DMS', $document->doc_type, $event],
            'deep_link' => APP_URL . "/document_view.php?id={$docId}"
        ]
    ];
    
    // Add safety-critical escalation if required
    if ($document->safety_critical) {
        $taskData['tags'][] = 'SAFETY_CRITICAL';
        $taskData['escalation_chain'] = ['QA_Manager', 'PSO'];
        $taskData['escalation_hours'] = 24;
    }
    
    $taskApi = new KaizenTasksAPI();
    $taskId = $taskApi->createTask($taskData);
    
    // Update document or review record with task ID
    linkTaskToDocument($docId, $event, $taskId);
    
    return $taskId;
}

function createReviewTask($docId, $reviewers) {
    foreach ($reviewers as $reviewer) {
        $taskId = createKaizenTask('DOC_REVIEW', $docId, $reviewer['user_id']);
        
        // Update review record with task ID
        updateReviewTaskId($docId, $reviewer['user_id'], $taskId);
    }
}

function createApprovalTask($docId, $approvers) {
    foreach ($approvers as $approver) {
        $taskId = createKaizenTask('DOC_APPROVED', $docId, $approver['user_id'], 'high');
        
        // Link approval task
        linkApprovalTask($docId, $approver['user_id'], $taskId);
    }
}

function createTrainingTask($docId, $users) {
    foreach ($users as $user) {
        $taskId = createKaizenTask('DOC_TRAINING', $docId, $user['user_id']);
        
        // Create training record
        createTrainingRecord($docId, $user['user_id'], $taskId);
    }
}

function updateTaskStatus($taskId, $status, $result = null) {
    $taskApi = new KaizenTasksAPI();
    $taskApi->updateTaskStatus($taskId, $status);
    
    if ($result) {
        $taskApi->addTaskResult($taskId, $result);
    }
}

function handleTaskCallback($taskId, $result) {
    // Handle callbacks from KaizenTasks when tasks are completed
    $taskData = getTaskByKaizenId($taskId);
    
    switch ($taskData['type']) {
        case 'DOC_REVIEW':
            processReviewCompletion($taskData['doc_id'], $result);
            break;
        case 'DOC_APPROVED':
            processApprovalCompletion($taskData['doc_id'], $result);
            break;
        case 'DOC_TRAINING':
            processTrainingCompletion($taskData['doc_id'], $result);
            break;
    }
}

function escalateTask($taskId, $level) {
    // Handle task escalation from KaizenTasks
    // Send notifications to higher level
    // Update priority if needed
    // Log escalation for metrics
}
```

### 2.2 EFFICIENCY & EFFECTIVENESS FEATURES (E-1 to E-10)

#### E-1: Read & Understood Micro-Training
**Requirement:** Fast, line-side competence proof

**Functions to Implement:**
```php
function assignTraining($docId, $users, $trainingType = 'read') {
    foreach ($users as $userId) {
        // Create training record
        $trainingRecord = [
            'doc_id' => $docId,
            'revision_id' => getCurrentRevisionId($docId),
            'user_id' => $userId,
            'training_type' => $trainingType,
            'status' => 'pending',
            'pass_threshold' => getPassThreshold($docId, $trainingType),
            'started_at' => null
        ];
        
        $trainingId = insertTrainingRecord($trainingRecord);
        
        // Create KaizenTask for training
        createTrainingTask($docId, $userId, $trainingId);
        
        // Send notification
        notifyTrainingAssignment($userId, $docId, $trainingId);
    }
}

function startTraining($trainingId) {
    // Mark training as started
    updateTrainingStatus($trainingId, 'in_progress');
    updateTrainingStartTime($trainingId, time());
    
    // Log training start
    logTrainingActivity($trainingId, 'started');
}

function recordReadConfirmation($docId, $userId, $timeSpent) {
    $trainingId = getTrainingRecord($docId, $userId, 'read');
    
    // Validate minimum time spent reading
    $minReadTime = getMinimumReadTime($docId);
    if ($timeSpent < $minReadTime) {
        throw new Exception("Insufficient time spent reading document. Minimum: {$minReadTime} seconds");
    }
    
    // Mark as completed
    updateTrainingRecord($trainingId, [
        'status' => 'completed',
        'completed_at' => date('Y-m-d H:i:s'),
        'time_spent_seconds' => $timeSpent,
        'score' => 100.00 // Read & understood is pass/fail
    ]);
    
    // Generate certificate if required
    if (requiresCertificate($docId)) {
        generateTrainingCertificate($trainingId);
    }
    
    // Complete associated KaizenTask
    completeTrainingTask($trainingId);
    
    // Log completion
    logTrainingActivity($trainingId, 'completed');
}

function createQuiz($docId, $questions) {
    foreach ($questions as $question) {
        insertQuizQuestion([
            'doc_id' => $docId,
            'question_text' => $question['text'],
            'question_type' => $question['type'],
            'options_json' => json_encode($question['options']),
            'correct_answer' => json_encode($question['correct']),
            'points' => $question['points'] ?? 1,
            'explanation' => $question['explanation'] ?? null
        ]);
    }
}

function evaluateQuizResponse($trainingId, $answers) {
    $training = getTrainingRecord($trainingId);
    $questions = getQuizQuestions($training['doc_id']);
    
    $totalPoints = 0;
    $earnedPoints = 0;
    $results = [];
    
    foreach ($questions as $question) {
        $totalPoints += $question['points'];
        $userAnswer = $answers[$question['question_id']] ?? null;
        $correctAnswer = json_decode($question['correct_answer'], true);
        
        $isCorrect = ($userAnswer == $correctAnswer);
        if ($isCorrect) {
            $earnedPoints += $question['points'];
        }
        
        $results[] = [
            'question_id' => $question['question_id'],
            'user_answer' => $userAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'explanation' => $question['explanation']
        ];
    }
    
    $score = ($earnedPoints / $totalPoints) * 100;
    $passed = ($score >= $training['pass_threshold']);
    
    // Update training record
    updateTrainingRecord($trainingId, [
        'status' => $passed ? 'completed' : 'failed',
        'completed_at' => date('Y-m-d H:i:s'),
        'score' => $score,
        'attempts' => $training['attempts'] + 1
    ]);
    
    // Store quiz results
    storeQuizResults($trainingId, $results, $score);
    
    if ($passed) {
        generateTrainingCertificate($trainingId);
        completeTrainingTask($trainingId);
    } else {
        // Allow retake if under attempt limit
        if ($training['attempts'] < getMaxAttempts($training['doc_id'])) {
            resetQuizForRetake($trainingId);
        }
    }
    
    return [
        'score' => $score,
        'passed' => $passed,
        'results' => $results
    ];
}

function generateCertificate($trainingId) {
    $training = getTrainingRecord($trainingId);
    $document = getDocumentById($training['doc_id']);
    $user = getUserById($training['user_id']);
    
    $certificateNumber = generateCertificateNumber($trainingId);
    
    // Generate PDF certificate
    $certificatePdf = createCertificatePDF([
        'certificate_number' => $certificateNumber,
        'user_name' => $user['name'],
        'document_title' => $document['title'],
        'document_number' => $document['doc_number'],
        'revision' => $document['current_revision'],
        'completion_date' => $training['completed_at'],
        'score' => $training['score'],
        'valid_until' => calculateCertificateExpiry($training)
    ]);
    
    $certificatePath = saveCertificate($certificateNumber, $certificatePdf);
    
    // Update training record with certificate info
    updateTrainingRecord($trainingId, [
        'certificate_number' => $certificateNumber,
        'certificate_path' => $certificatePath
    ]);
    
    return $certificateNumber;
}

function checkTrainingCompliance($docId) {
    $requirements = getTrainingRequirements($docId);
    $compliance = [];
    
    foreach ($requirements as $req) {
        $requiredUsers = getRequiredUsers($req);
        $completedUsers = getCompletedTrainingUsers($docId, $req['training_type']);
        
        $complianceRate = count($completedUsers) / count($requiredUsers) * 100;
        
        $compliance[] = [
            'requirement_id' => $req['requirement_id'],
            'requirement_description' => $req['description'],
            'required_users' => count($requiredUsers),
            'completed_users' => count($completedUsers),
            'compliance_rate' => $complianceRate,
            'is_compliant' => ($complianceRate >= 100)
        ];
    }
    
    return $compliance;
}
```

#### E-2: Safety Gate (PSO) Auto-enforced
**Requirement:** Blocks unsafe release for safety-critical changes

**Functions to Implement:**
```php
function detectSafetyCharacteristics($docId, $content = null) {
    $document = getDocumentById($docId);
    
    // Get PSA rules for this process area
    $psaRules = getPSARules($document['process_area_id']);
    
    $detectedCharacteristics = [];
    
    foreach ($psaRules as $rule) {
        // Check if trigger condition is met
        if (preg_match('/' . $rule['condition_regex'] . '/i', $content ?? $document['title'])) {
            $detectedCharacteristics[] = [
                'rule_id' => $rule['rule_id'],
                'category' => $rule['trigger_area'],
                'description' => $rule['description'],
                'pso_required' => $rule['pso_required']
            ];
        }
    }
    
    // Update document safety characteristics
    if (!empty($detectedCharacteristics)) {
        updateDocumentSafetyCharacteristics($docId, $detectedCharacteristics);
        
        // Mark document as safety critical if not already
        if (!$document['safety_critical']) {
            updateDocument($docId, ['safety_critical' => true]);
        }
        
        // Set PSO requirement if any rule requires it
        $psoRequired = array_reduce($detectedCharacteristics, function($carry, $char) {
            return $carry || $char['pso_required'];
        }, false);
        
        if ($psoRequired) {
            updateDocument($docId, ['pso_required' => true]);
        }
    }
    
    return $detectedCharacteristics;
}

function requirePSOApproval($docId, $reason = 'Safety characteristics detected') {
    // Mark document as requiring PSO approval
    updateDocument($docId, [
        'pso_required' => true,
        'pso_approved_by' => null,
        'pso_approved_at' => null
    ]);
    
    // Create high-priority task for PSO
    $psoUsers = getUsersByRole('PSO');
    foreach ($psoUsers as $pso) {
        createKaizenTask('PSO_REVIEW', $docId, $pso['user_id'], 'critical');
    }
    
    // Block document from becoming effective
    blockDocumentRelease($docId, 'Pending PSO approval: ' . $reason);
    
    // Log PSO requirement
    logDocumentAction($docId, 'pso_required', null, $reason);
}

function validatePSOApproval($docId, $psoUserId, $approvalData) {
    // Verify user has PSO role
    if (!userHasRole($psoUserId, 'PSO')) {
        throw new Exception('User does not have PSO authority');
    }
    
    $document = getDocumentById($docId);
    if (!$document['pso_required']) {
        throw new Exception('Document does not require PSO approval');
    }
    
    // Record PSO approval
    updateDocument($docId, [
        'pso_approved_by' => $psoUserId,
        'pso_approved_at' => date('Y-m-d H:i:s')
    ]);
    
    // Record approval details
    recordPSOApproval($docId, $psoUserId, $approvalData);
    
    // Remove release block
    unblockDocumentRelease($docId, 'PSO approved');
    
    // Complete PSO tasks
    completePSOTasks($docId);
    
    // Log PSO approval
    logDocumentAction($docId, 'pso_approved', null, [
        'pso_user_id' => $psoUserId,
        'approval_data' => $approvalData
    ]);
}

function blockReleaseWithoutPSO($docId) {
    $document = getDocumentById($docId);
    
    if ($document['pso_required'] && !$document['pso_approved_by']) {
        // Block any attempt to make document effective
        addReleaseBlock($docId, 'pso_approval', 'PSO approval required before release');
        
        // Create urgent escalation task
        createKaizenTask('RELEASE_BLOCKED', $docId, getQAManager(), 'critical');
        
        return false;
    }
    
    return true;
}

function getPSOPendingDocs() {
    return queryDocuments([
        'pso_required' => true,
        'pso_approved_by' => null,
        'status' => ['review', 'approved']
    ]);
}
```

#### E-3: Linked Documents Integrity (PFMEA ↔ CP ↔ WI)
**Requirement:** Stops "orphan" edits

**Functions to Implement:**
```php
function linkDocuments($sourceId, $targetId, $linkType, $linkStrength = 'recommended') {
    // Validate documents exist and user has permission
    validateDocumentExists($sourceId);
    validateDocumentExists($targetId);
    
    // Prevent circular links
    if (hasCircularLink($sourceId, $targetId)) {
        throw new Exception('Circular link detected');
    }
    
    // Create link record
    $linkId = insertDocumentLink([
        'source_doc_id' => $sourceId,
        'target_doc_id' => $targetId,
        'link_type' => $linkType,
        'link_strength' => $linkStrength,
        'validation_required' => ($linkStrength === 'mandatory'),
        'sync_status' => 'pending',
        'created_by' => getCurrentUserId()
    ]);
    
    // If linking PFMEA ↔ CP ↔ WI, create process link
    if (isPFMEAtoCPtoWILink($sourceId, $targetId)) {
        createProcessLink($sourceId, $targetId);
    }
    
    // Trigger validation if required
    if ($linkStrength === 'mandatory') {
        validateLinkedDocuments($sourceId, $targetId);
    }
    
    logDocumentAction($sourceId, 'document_linked', null, [
        'target_id' => $targetId,
        'link_type' => $linkType,
        'link_strength' => $linkStrength
    ]);
    
    return $linkId;
}

function validatePFMEAtoCPLink($pfmeaId, $cpId) {
    $pfmea = getDocumentById($pfmeaId);
    $cp = getDocumentById($cpId);
    
    // Validate they are correct document types
    if ($pfmea['doc_type_code'] !== 'PFMEA' || $cp['doc_type_code'] !== 'CP') {
        throw new Exception('Invalid document types for PFMEA-CP link');
    }
    
    // Validate they are for same process/area
    if ($pfmea['process_area_id'] !== $cp['process_area_id']) {
        throw new Exception('PFMEA and Control Plan must be for same process area');
    }
    
    // Extract characteristics from both documents
    $pfmeaCharacteristics = extractCharacteristicsFromPFMEA($pfmeaId);
    $cpCharacteristics = extractCharacteristicsFromCP($cpId);
    
    // Check for missing characteristics
    $missingInCP = array_diff($pfmeaCharacteristics, $cpCharacteristics);
    $orphanInCP = array_diff($cpCharacteristics, $pfmeaCharacteristics);
    
    if (!empty($missingInCP) || !empty($orphanInCP)) {
        updateLinkSyncStatus($pfmeaId, $cpId, 'conflict');
        
        // Create task to resolve discrepancies
        createSyncTask($pfmeaId, $cpId, [
            'missing_in_cp' => $missingInCP,
            'orphan_in_cp' => $orphanInCP
        ]);
        
        return false;
    }
    
    updateLinkSyncStatus($pfmeaId, $cpId, 'synced');
    return true;
}

function validateCPtoWILink($cpId, $wiId) {
    $cp = getDocumentById($cpId);
    $wi = getDocumentById($wiId);
    
    // Similar validation logic for CP to WI
    // Check that all control methods in CP have corresponding steps in WI
    // Check that all critical parameters are covered
    
    $cpControlMethods = extractControlMethodsFromCP($cpId);
    $wiSteps = extractStepsFromWI($wiId);
    
    $validation = validateControlMethodsCoverage($cpControlMethods, $wiSteps);
    
    if (!$validation['valid']) {
        updateLinkSyncStatus($cpId, $wiId, 'conflict');
        createSyncTask($cpId, $wiId, $validation['issues']);
        return false;
    }
    
    updateLinkSyncStatus($cpId, $wiId, 'synced');
    return true;
}

function checkOrphanLinks($docId) {
    $links = getDocumentLinks($docId);
    $orphanLinks = [];
    
    foreach ($links as $link) {
        $targetDoc = getDocumentById($link['target_doc_id']);
        
        // Check if target document still exists and is not obsolete
        if (!$targetDoc || $targetDoc['status'] === 'obsolete') {
            $orphanLinks[] = $link;
        }
    }
    
    return $orphanLinks;
}

function syncLinkedDocuments($docId) {
    $links = getMandatoryLinks($docId);
    $syncResults = [];
    
    foreach ($links as $link) {
        $result = validateLinkedDocuments($link['source_doc_id'], $link['target_doc_id']);
        $syncResults[] = $result;
        
        if (!$result['valid']) {
            // Create sync task
            createSyncTask($link['source_doc_id'], $link['target_doc_id'], $result['issues']);
        }
    }
    
    return $syncResults;
}

function generateLinkageReport($docId) {
    $document = getDocumentById($docId);
    $links = getDocumentLinks($docId);
    
    $report = [
        'document' => $document,
        'total_links' => count($links),
        'link_breakdown' => [
            'parent' => countLinksByType($links, 'parent'),
            'child' => countLinksByType($links, 'child'),
            'reference' => countLinksByType($links, 'reference'),
            'supersedes' => countLinksByType($links, 'supersedes')
        ],
        'sync_status' => [
            'synced' => countLinksByStatus($links, 'synced'),
            'pending' => countLinksByStatus($links, 'pending'),
            'conflict' => countLinksByStatus($links, 'conflict')
        ],
        'orphan_links' => checkOrphanLinks($docId)
    ];
    
    return $report;
}
```

#### E-4: Effective Date Scheduler & Readiness Check
**Requirement:** Zero-downtime changeover

**Functions to Implement:**
```php
function scheduleEffectiveDate($docId, $effectiveDate) {
    // Validate future date
    if (strtotime($effectiveDate) <= time()) {
        throw new Exception('Effective date must be in the future');
    }
    
    // Update document with scheduled effective date
    updateDocument($docId, [
        'effective_date' => $effectiveDate,
        'status' => 'approved' // Must be approved to schedule
    ]);
    
    // Create readiness check tasks leading up to effective date
    scheduleReadinessChecks($docId, $effectiveDate);
    
    // Log scheduling
    logDocumentAction($docId, 'effective_date_scheduled', null, $effectiveDate);
}

function checkReadinessForRelease($docId) {
    $document = getDocumentById($docId);
    $readinessChecks = [];
    
    // Check 1: All approvals complete
    $readinessChecks['approvals'] = checkApprovalReadiness($docId);
    
    // Check 2: Training completion above threshold
    $readinessChecks['training'] = checkTrainingReadiness($docId);
    
    // Check 3: PSO approval if required
    $readinessChecks['pso'] = checkPSOReadiness($docId);
    
    // Check 4: Linked documents synchronized
    $readinessChecks['links'] = checkLinkReadiness($docId);
    
    // Check 5: Controlled copies prepared
    $readinessChecks['copies'] = checkCopyReadiness($docId);
    
    $overallReady = array_reduce($readinessChecks, function($carry, $check) {
        return $carry && $check['ready'];
    }, true);
    
    return [
        'ready' => $overallReady,
        'checks' => $readinessChecks,
        'blocking_issues' => array_filter($readinessChecks, function($check) {
            return !$check['ready'] && $check['critical'];
        })
    ];
}

function validateTrainingCompletion($docId) {
    $requirements = getTrainingRequirements($docId);
    $compliance = [];
    
    foreach ($requirements as $req) {
        $requiredUsers = getRequiredUsers($req);
        $completedUsers = getCompletedTrainingUsers($docId, $req['training_type']);
        
        $complianceRate = count($completedUsers) / count($requiredUsers) * 100;
        
        // Determine if training completion meets threshold
        $minThreshold = getMinTrainingThreshold($docId);
        $trainingReady = ($complianceRate >= $minThreshold);
        
        $compliance[] = [
            'requirement_type' => $req['training_type'],
            'required_count' => count($requiredUsers),
            'completed_count' => count($completedUsers),
            'compliance_rate' => $complianceRate,
            'threshold' => $minThreshold,
            'ready' => $trainingReady,
            'missing_users' => array_diff($requiredUsers, $completedUsers)
        ];
    }
    
    return $compliance;
}

function validatePSOApproval($docId) {
    $document = getDocumentById($docId);
    
    if (!$document['pso_required']) {
        return ['required' => false, 'ready' => true];
    }
    
    return [
        'required' => true,
        'ready' => !empty($document['pso_approved_by']),
        'approved_by' => $document['pso_approved_by'],
        'approved_at' => $document['pso_approved_at']
    ];
}

function validateLinkedDocsSync($docId) {
    $links = getMandatoryLinks($docId);
    $syncStatus = [];
    
    foreach ($links as $link) {
        $targetDoc = getDocumentById($link['target_doc_id']);
        
        $syncStatus[] = [
            'target_doc_id' => $link['target_doc_id'],
            'target_doc_number' => $targetDoc['doc_number'],
            'link_type' => $link['link_type'],
            'sync_status' => $link['sync_status'],
            'ready' => ($link['sync_status'] === 'synced')
        ];
    }
    
    $allSynced = array_reduce($syncStatus, function($carry, $status) {
        return $carry && $status['ready'];
    }, true);
    
    return [
        'ready' => $allSynced,
        'links' => $syncStatus
    ];
}

function blockOrReleaseDocument($docId) {
    $readiness = checkReadinessForRelease($docId);
    $document = getDocumentById($docId);
    
    if ($readiness['ready']) {
        // Make document effective
        updateDocument($docId, [
            'status' => 'effective',
            'effective_date' => date('Y-m-d')
        ]);
        
        // Clear any existing blocks
        clearReleaseBlocks($docId);
        
        // Generate controlled copies
        generateRequiredControlledCopies($docId);
        
        // Send effectiveness notifications
        sendEffectivenessNotifications($docId);
        
        // Log effectiveness
        logDocumentAction($docId, 'made_effective', null, $readiness);
        
        return true;
    } else {
        // Block release and create tasks to resolve issues
        foreach ($readiness['blocking_issues'] as $issue) {
            addReleaseBlock($docId, $issue['type'], $issue['description']);
            
            // Create task to resolve the blocking issue
            createIssueResolutionTask($docId, $issue);
        }
        
        // Create urgent notification for document owner
        createKaizenTask('RELEASE_BLOCKED', $docId, $document['owner_user_id'], 'urgent');
        
        return false;
    }
}

function scheduleReadinessChecks($docId, $effectiveDate) {
    $checkDates = [
        7 => 'final_readiness_check',   // 7 days before
        3 => 'critical_readiness_check', // 3 days before  
        1 => 'go_nogo_decision'         // 1 day before
    ];
    
    foreach ($checkDates as $daysBefore => $checkType) {
        $checkDate = date('Y-m-d', strtotime($effectiveDate . " -{$daysBefore} days"));
        
        // Schedule automated readiness check
        scheduleTask([
            'type' => $checkType,
            'doc_id' => $docId,
            'scheduled_date' => $checkDate,
            'task_data' => [
                'effective_date' => $effectiveDate,
                'days_remaining' => $daysBefore
            ]
        ]);
    }
}
```

### 2.3 IMPLEMENTATION PHASES & TIMELINE

#### PHASE 1: DATABASE FOUNDATION (Week 1)
**Priority: CRITICAL - Nothing works without this**

**Day 1-2: Master Data Tables**
- Create all master tables in KaizenMasters module
- Insert initial master data (doc types, areas, sites, etc.)
- Set up proper relationships and constraints

**Day 3-4: Core DMS Tables**
- Create dms_documents, dms_revisions, dms_categories
- Create approval and review tables
- Set up audit logging tables

**Day 5-6: Supporting Tables**  
- Training and competence tables
- Controlled copy management
- Document linking tables

**Day 7: Data Validation & Testing**
- Test all foreign key relationships
- Verify data integrity constraints
- Load test data for development

#### PHASE 2: BASIC COMPLIANCE (Week 2-3)
**Implements B-1 through B-8**

**Week 2:**
- Document CRUD operations (B-1, B-2)
- File upload and storage system
- Basic search and filtering (B-5)
- Document lifecycle management (B-3)

**Week 3:**
- PDF watermarking and QR codes (B-4)
- Audit trail implementation (B-6)
- Retention policy system (B-7)
- KaizenTasks integration (B-8)

#### PHASE 3: EFFICIENCY FEATURES (Week 4-5)
**Implements E-1 through E-5**

**Week 4:**
- Training and competence system (E-1)
- Safety gate automation (E-2)
- Document linking validation (E-3)

**Week 5:**
- Effective date scheduler (E-4)
- Shop floor kiosk access (E-5)

#### PHASE 4: ADVANCED EFFICIENCY (Week 6)
**Implements E-6 through E-10**

- Controlled copy ledger (E-6)
- Periodic review cycles (E-7)
- Dashboard and KPI system (E-8)
- Bulk import utilities (E-9)
- Impact analysis tools (E-10)

#### PHASE 5: GOOD-TO-HAVE FEATURES (Future)
**Implements G-1 through G-7 as time permits**

### 2.4 CRITICAL SUCCESS FACTORS

1. **Database First Approach**
   - All tables must be created and tested before any application development
   - Master data must be properly populated
   - Foreign key relationships must be enforced

2. **KaizenTasks Integration**
   - Seamless workflow integration is essential
   - All document events must create appropriate tasks
   - Escalation mechanisms must work reliably

3. **Training Compliance**
   - Training effectiveness must be measurable
   - Competence verification must be automated
   - Certificate management must be robust

4. **Safety Gate Enforcement**
   - PSO approval must be mandatory for safety-critical documents
   - System must prevent bypassing safety requirements
   - Audit trail must capture all safety decisions

5. **User Adoption**
   - Interface must follow Kaizen design standards
   - Training materials must be comprehensive
   - Rollout must be phased by department

---

*This implementation plan ensures full compliance with ISO 9001:2015 and IATF 16949:2016 requirements while providing the efficiency and effectiveness features needed for modern manufacturing operations.*