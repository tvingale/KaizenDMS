# KaizenDMS Database Structure - Updated

**Automatically generated from actual database schema**

**Generated:** 2025-09-17 16:49:50
**Database:** kaizenap_flow_db
**Host:** 162.214.80.31
**Total Tables:** 22

---

## 📊 **Database Overview**

**Database Name:** `kaizenap_flow_db`
**Prefix:** `dms_` (all tables)
**Total Tables:** 22 actual tables
**Storage Engine:** InnoDB with foreign key constraints
**Character Set:** utf8mb4_unicode_ci for full Unicode support

---

## 🏗️ **Table Categories**

### **📋 Audit Trail Tables (1)**
- `dms_activity_log` - System Activity Audit Trail (5 rows)

### **📋 Organization Tables (1)**
- `dms_categories` - Document Categorization (4 rows)

### **📋 Master Data Tables (9)**
- `dms_customers` - Customer Data Management (0 rows)
- `dms_departments` - Department Structure (5 rows)
- `dms_languages` - Multi-language Support (4 rows)
- `dms_notification_channels` - Communication Channels (2 rows)
- `dms_notification_templates` - Message Templates (2 rows)
- `dms_process_areas` - Process Classification (7 rows)
- `dms_review_cycles` - Review Scheduling (3 rows)
- `dms_sites` - Site/Location Management (2 rows)
- `dms_suppliers` - Supplier Qualification (0 rows)

### **📋 Document Management Tables (5)**
- `dms_document_acl` - Document acl (0 rows)
- `dms_document_assignments` - Document assignments (0 rows)
- `dms_document_hierarchy` - Document hierarchy (0 rows)
- `dms_document_types` - Document Type Catalog (7 rows)
- `dms_documents` - Document Repository (1 rows)

### **📋 RBAC System Tables (5)**
- `dms_permissions` - Permission Catalog (38 rows)
- `dms_role_permissions` - Role-Permission Mapping (35 rows)
- `dms_roles` - Role Definitions (12 rows)
- `dms_user_effective_permissions` - RBAC Permission Caching (25 rows)
- `dms_user_roles` - User Role Assignments (2 rows)

### **📋 System Configuration Tables (1)**
- `dms_settings` - Application Configuration Storage (2 rows)

---

## 📋 **Detailed Table Structures**

### **Audit Trail Tables**

#### **dms_activity_log** - System Activity Audit Trail
```sql
CREATE TABLE `dms_activity_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., documents, category, user_role',
  `entity_id` int(11) NOT NULL COMMENT 'ID of the affected entity',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'created, updated, deleted, assigned, completed',
  `user_id` int(11) NOT NULL COMMENT 'KaizenAuth user ID who performed action',
  `old_values` json DEFAULT NULL COMMENT 'Previous values (for updates)',
  `new_values` json DEFAULT NULL COMMENT 'New values',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_dms_activity_log_composite` (`entity_type`,`entity_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status:** Currently implemented with 5 rows

### **Organization Tables**

#### **dms_categories** - Document Categorization
```sql
CREATE TABLE `dms_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#667eea' COMMENT 'Hex color code',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '?',
  `sort_order` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'KaizenAuth user ID (e.g. keadmin)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_category_name` (`name`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status:** Currently implemented with 4 rows

### **Master Data Tables**

#### **dms_customers** - Customer Data Management
```sql
CREATE TABLE `dms_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Customer identifier (e.g., GSRTC, MSRTC)',
  `customer_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Customer full name',
  `customer_type` enum('government','private','oem','distributor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primary contact person name',
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GST/Tax identification number',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `idx_customer_code` (`customer_code`),
  KEY `idx_customer_type` (`customer_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DMS-managed customer master data';
```

**Status:** Currently implemented with 0 rows

#### **dms_departments** - Department Structure
```sql
CREATE TABLE `dms_departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Department identifier (e.g., QA, MFG, ENG)',
  `dept_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Department full name',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Department description and responsibilities',
  `parent_dept_id` int(11) DEFAULT NULL COMMENT 'For hierarchical department structure',
  `manager_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Department manager name',
  `manager_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Department manager email for escalations',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Display order in lists',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_code` (`dept_code`),
  KEY `idx_dept_code` (`dept_code`),
  KEY `idx_parent` (`parent_dept_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `dms_departments_ibfk_1` FOREIGN KEY (`parent_dept_id`) REFERENCES `dms_departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DMS-managed department master data';
```

**Foreign Keys:**
- `parent_dept_id` → `dms_departments.id`

**Status:** Currently implemented with 5 rows

#### **dms_languages** - Multi-language Support
```sql
CREATE TABLE `dms_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO language code (e.g., en, mr, hi)',
  `lang_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Language name in English',
  `native_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Language name in native script',
  `rtl_flag` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Right-to-left text direction',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Default language flag',
  `date_format` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Y-m-d' COMMENT 'Date format for this language',
  `decimal_separator` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT '.' COMMENT 'Decimal separator character',
  `thousands_separator` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT ',' COMMENT 'Thousands separator character',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Display order in lists',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_code` (`lang_code`),
  KEY `idx_lang_code` (`lang_code`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Multi-language support configuration';
```

**Status:** Currently implemented with 4 rows

#### **dms_notification_channels** - Communication Channels
```sql
CREATE TABLE `dms_notification_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Channel identifier',
  `channel_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Channel display name',
  `channel_type` enum('whatsapp','email','sms','push') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Channel type',
  `api_endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'API endpoint URL for external services',
  `configuration` json NOT NULL COMMENT 'Channel-specific configuration',
  `rate_limit_per_minute` int(11) DEFAULT NULL COMMENT 'Maximum messages per minute',
  `cost_per_message` decimal(10,4) DEFAULT NULL COMMENT 'Cost per message for analytics',
  `priority_order` int(11) NOT NULL DEFAULT '1' COMMENT 'Priority for fallback (1=highest)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_health_check` timestamp NULL DEFAULT NULL COMMENT 'Last successful health check',
  `health_status` enum('healthy','degraded','down') COLLATE utf8mb4_unicode_ci DEFAULT 'healthy',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_code` (`channel_code`),
  KEY `idx_channel_code` (`channel_code`),
  KEY `idx_channel_type` (`channel_type`),
  KEY `idx_priority` (`priority_order`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Communication channel configuration and status';
```

**Status:** Currently implemented with 2 rows

#### **dms_notification_templates** - Message Templates
```sql
CREATE TABLE `dms_notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template identifier',
  `template_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template display name',
  `scenario` enum('approval_request','approval_reminder','document_released','training_required','review_due','account_created','password_reset','document_updated','document_obsoleted') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Notification scenario',
  `channel` enum('whatsapp','email','sms') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Communication channel',
  `language_id` int(11) NOT NULL COMMENT 'Template language',
  `subject_template` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Subject line template (for email)',
  `message_template` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Message body with {{variables}}',
  `template_variables` json NOT NULL COMMENT 'Available variable placeholders',
  `meta_approved` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'WhatsApp Meta approval status',
  `meta_template_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Meta approved template ID',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_code` (`template_code`),
  UNIQUE KEY `unique_scenario_channel_lang` (`scenario`,`channel`,`language_id`),
  KEY `idx_template_code` (`template_code`),
  KEY `idx_scenario_channel` (`scenario`,`channel`),
  KEY `idx_language` (`language_id`),
  KEY `idx_meta_approved` (`meta_approved`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `dms_notification_templates_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `dms_languages` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WhatsApp and email notification message templates';
```

**Foreign Keys:**
- `language_id` → `dms_languages.id`

**Status:** Currently implemented with 2 rows

#### **dms_process_areas** - Process Classification
```sql
CREATE TABLE `dms_process_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Process area identifier (e.g., WELD, ASSY, QC)',
  `area_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Process area display name',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Process area description and scope',
  `parent_area_id` int(11) DEFAULT NULL COMMENT 'For hierarchical process structure',
  `safety_critical_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Default safety criticality',
  `requires_special_training` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Special training requirement',
  `department_id` int(11) DEFAULT NULL COMMENT 'Primary responsible department',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Display order in lists',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_code` (`area_code`),
  KEY `idx_area_code` (`area_code`),
  KEY `idx_parent` (`parent_area_id`),
  KEY `idx_department` (`department_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `dms_process_areas_ibfk_1` FOREIGN KEY (`parent_area_id`) REFERENCES `dms_process_areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dms_process_areas_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `dms_departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Universal process area classification (site-independent)';
```

**Foreign Keys:**
- `parent_area_id` → `dms_process_areas.id`
- `department_id` → `dms_departments.id`

**Status:** Currently implemented with 7 rows

#### **dms_review_cycles** - Review Scheduling
```sql
CREATE TABLE `dms_review_cycles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cycle_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Review cycle identifier (e.g., ANNUAL, BIENNIAL)',
  `cycle_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Review cycle display name',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Review cycle description and criteria',
  `months` int(11) NOT NULL COMMENT 'Review frequency in months',
  `reminder_days` json NOT NULL COMMENT 'Days before due date to send reminders [30, 7, 1]',
  `escalation_days` int(11) DEFAULT '7' COMMENT 'Days after due date to escalate',
  `applicable_doc_types` json DEFAULT NULL COMMENT 'Document type codes this cycle applies to',
  `mandatory` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether review is mandatory',
  `escalation_chain` json DEFAULT NULL COMMENT 'Roles for escalation [document_owner, manager, qa_head]',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Display order in lists',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cycle_code` (`cycle_code`),
  KEY `idx_cycle_code` (`cycle_code`),
  KEY `idx_months` (`months`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Periodic review cycle definitions';
```

**Status:** Currently implemented with 3 rows

#### **dms_sites** - Site/Location Management
```sql
CREATE TABLE `dms_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Short site identifier (e.g., B75, G44)',
  `site_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Full site name',
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ahmednagar',
  `state` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Maharashtra',
  `country` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Kolkata',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_main_site` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_code` (`site_code`),
  KEY `idx_site_code` (`site_code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DMS-managed site/location master data';
```

**Status:** Currently implemented with 2 rows

#### **dms_suppliers** - Supplier Qualification
```sql
CREATE TABLE `dms_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Supplier identifier',
  `supplier_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Supplier full name',
  `supplier_type` enum('raw_material','component','service','tooling') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'component',
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primary contact person name',
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GST/Tax identification number',
  `iso_certified` tinyint(1) DEFAULT '0' COMMENT 'ISO 9001 certification status',
  `iatf_certified` tinyint(1) DEFAULT '0' COMMENT 'IATF 16949 certification status',
  `approval_status` enum('approved','conditional','rejected','under_review') COLLATE utf8mb4_unicode_ci DEFAULT 'under_review',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`),
  KEY `idx_supplier_code` (`supplier_code`),
  KEY `idx_supplier_type` (`supplier_type`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DMS-managed supplier master data';
```

**Status:** Currently implemented with 0 rows

### **Document Management Tables**

#### **dms_document_acl** - Document acl
```sql
CREATE TABLE `dms_document_acl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `sensitivity_level` enum('public','internal','confidential','safety_critical','regulatory') COLLATE utf8mb4_unicode_ci DEFAULT 'internal',
  `owner_user_id` int(11) NOT NULL,
  `co_owners` json DEFAULT NULL COMMENT 'Array of co-owner user IDs',
  `explicit_permissions` json DEFAULT NULL COMMENT 'User/role specific permissions override',
  `access_restrictions` json DEFAULT NULL COMMENT 'Time, location, context restrictions',
  `inheritance_blocked` tinyint(1) DEFAULT '0' COMMENT 'Block hierarchical inheritance',
  `requires_training` tinyint(1) DEFAULT '0',
  `required_clearance_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_access_allowed` tinyint(1) DEFAULT '0',
  `audit_access_required` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_document_sensitivity` (`document_id`,`sensitivity_level`),
  KEY `idx_owner` (`owner_user_id`),
  KEY `idx_sensitivity_level` (`sensitivity_level`),
  CONSTRAINT `dms_document_acl_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dms_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Foreign Keys:**
- `document_id` → `dms_documents.id`

**Status:** Currently implemented with 0 rows

#### **dms_document_assignments** - Document assignments
```sql
CREATE TABLE `dms_document_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `assigned_to_user_id` int(11) NOT NULL,
  `assigned_by_user_id` int(11) NOT NULL,
  `assignment_type` enum('reviewer','expert_reviewer','approver','consultant','stakeholder_input','compliance_check','peer_review') COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignment_reason` text COLLATE utf8mb4_unicode_ci,
  `granted_permissions` json DEFAULT NULL COMMENT 'Specific permissions for this assignment',
  `access_restrictions` json DEFAULT NULL COMMENT 'Time limits, sections, conditions',
  `assignment_context` json DEFAULT NULL COMMENT 'Department, project, compliance requirement',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','completed','expired','cancelled','overdue') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_assignment_status` (`assigned_to_user_id`,`status`,`due_date`),
  KEY `idx_document_assignments` (`document_id`,`assignment_type`,`status`),
  KEY `idx_assignment_timeline` (`assigned_at`,`due_date`,`expires_at`),
  KEY `assigned_by_user_id` (`assigned_by_user_id`),
  CONSTRAINT `dms_document_assignments_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dms_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dms_document_assignments_ibfk_2` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `dms_user_roles` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Foreign Keys:**
- `document_id` → `dms_documents.id`
- `assigned_by_user_id` → `dms_user_roles.user_id`

**Status:** Currently implemented with 0 rows

#### **dms_document_hierarchy** - Document hierarchy
```sql
CREATE TABLE `dms_document_hierarchy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `process_area_id` int(11) DEFAULT NULL,
  `production_line_id` int(11) DEFAULT NULL,
  `station_id` int(11) DEFAULT NULL,
  `hierarchy_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Full path like /B75/Manufacturing/Assembly/Line2/Station15',
  `parent_hierarchy_id` int(11) DEFAULT NULL,
  `level_type` enum('site','department','process_area','production_line','station') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hierarchy_path` (`hierarchy_path`),
  KEY `idx_document_hierarchy` (`document_id`,`site_id`,`department_id`),
  KEY `idx_level_type` (`level_type`,`is_active`),
  KEY `site_id` (`site_id`),
  KEY `department_id` (`department_id`),
  KEY `process_area_id` (`process_area_id`),
  KEY `parent_hierarchy_id` (`parent_hierarchy_id`),
  CONSTRAINT `dms_document_hierarchy_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dms_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dms_document_hierarchy_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `dms_sites` (`id`),
  CONSTRAINT `dms_document_hierarchy_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `dms_departments` (`id`),
  CONSTRAINT `dms_document_hierarchy_ibfk_4` FOREIGN KEY (`process_area_id`) REFERENCES `dms_process_areas` (`id`),
  CONSTRAINT `dms_document_hierarchy_ibfk_5` FOREIGN KEY (`parent_hierarchy_id`) REFERENCES `dms_document_hierarchy` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Foreign Keys:**
- `document_id` → `dms_documents.id`
- `site_id` → `dms_sites.id`
- `department_id` → `dms_departments.id`
- `process_area_id` → `dms_process_areas.id`
- `parent_hierarchy_id` → `dms_document_hierarchy.id`

**Status:** Currently implemented with 0 rows

#### **dms_document_types** - Document Type Catalog
```sql
CREATE TABLE `dms_document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Document type identifier (e.g., POL, SOP, WI)',
  `type_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Document type display name',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Document type description and usage',
  `numbering_format` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Auto-numbering template (e.g., POL-{YYYY}-{####})',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether approval is required',
  `min_approval_levels` int(11) NOT NULL DEFAULT '1' COMMENT 'Minimum approval levels required',
  `requires_training` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether training is required',
  `retention_years` int(11) NOT NULL DEFAULT '3' COMMENT 'Retention period in years',
  `is_controlled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether controlled copy management applies',
  `default_language_id` int(11) DEFAULT NULL COMMENT 'Default language for this document type',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Display order in lists',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_code` (`type_code`),
  KEY `idx_type_code` (`type_code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `default_language_id` (`default_language_id`),
  CONSTRAINT `dms_document_types_ibfk_1` FOREIGN KEY (`default_language_id`) REFERENCES `dms_languages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Document type classification and auto-numbering rules';
```

**Foreign Keys:**
- `default_language_id` → `dms_languages.id`

**Status:** Currently implemented with 7 rows

#### **dms_documents** - Document Repository
```sql
CREATE TABLE `dms_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','draft','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'KaizenAuth user ID (e.g. keadmin)',
  `updated_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'KaizenAuth user ID',
  `assigned_to` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'KaizenAuth user ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_category` (`category_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_updated` (`updated_at`),
  CONSTRAINT `dms_documents_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `dms_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Foreign Keys:**
- `category_id` → `dms_categories.id`

**Status:** Currently implemented with 1 rows

### **RBAC System Tables**

#### **dms_permissions** - Permission Catalog
```sql
CREATE TABLE `dms_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `resource` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'documents, users, reports, system, workflow',
  `scope_qualifier` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'all, department, process_area, assigned_only',
  `risk_level` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `requires_approval` tinyint(1) DEFAULT '0',
  `approved_by_role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audit_logged` tinyint(1) DEFAULT '1',
  `is_system_permission` tinyint(1) DEFAULT '0',
  `created_by_user_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `scope_level` enum('all','cross_department','department','process_area','station','assigned_only') COLLATE utf8mb4_unicode_ci DEFAULT 'assigned_only' COMMENT 'Required scope level',
  `requires_context` tinyint(1) DEFAULT '0' COMMENT 'Needs additional context',
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_name` (`permission_name`),
  UNIQUE KEY `unique_permission` (`permission_name`),
  KEY `idx_category` (`category`),
  KEY `idx_resource` (`resource`),
  KEY `idx_scope_level` (`scope_level`),
  KEY `idx_system_permission` (`is_system_permission`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status:** Currently implemented with 38 rows

#### **dms_role_permissions** - Role-Permission Mapping
```sql
CREATE TABLE `dms_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_scope` enum('all','cross_department','department','process_area','station','assigned_only') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Specific granted scope',
  `context_filter` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON context filters',
  `is_inherited` tinyint(1) DEFAULT '0' COMMENT 'Inherited from parent role',
  `granted_by` int(11) DEFAULT NULL COMMENT 'User who granted permission',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Temporary permissions',
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  KEY `idx_granted_scope` (`granted_scope`),
  KEY `idx_granted_by` (`granted_by`),
  CONSTRAINT `dms_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `dms_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dms_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `dms_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Foreign Keys:**
- `role_id` → `dms_roles.id`
- `permission_id` → `dms_permissions.id`

**Status:** Currently implemented with 35 rows

#### **dms_roles** - Role Definitions
```sql
CREATE TABLE `dms_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_scope` json DEFAULT NULL COMMENT 'Array of departments this role applies to',
  `hierarchy_level` enum('operator','lead','supervisor','manager','director','executive') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `can_be_combined_with` json DEFAULT NULL COMMENT 'Compatible roles for multi-role users',
  `is_system_role` tinyint(1) DEFAULT '0' COMMENT 'Cannot be deleted if true',
  `created_by_user_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `scope` enum('all','cross_department','department','process_area','station','assigned_only') COLLATE utf8mb4_unicode_ci DEFAULT 'assigned_only' COMMENT 'Default permission scope',
  `max_permissions` int(11) DEFAULT '100' COMMENT 'Permission limit for role',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`),
  UNIQUE KEY `unique_role` (`role_name`),
  KEY `idx_hierarchy_level` (`hierarchy_level`),
  KEY `idx_role_name` (`role_name`),
  KEY `idx_scope` (`scope`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status:** Currently implemented with 12 rows

#### **dms_user_effective_permissions** - RBAC Permission Caching
```sql
CREATE TABLE `dms_user_effective_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'all, department, process_area, assigned_only',
  `context` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'default, auditor_context, safety_context, emergency',
  `granted_by_roles` json DEFAULT NULL COMMENT 'Array of roles that grant this permission',
  `effective_scope` json DEFAULT NULL COMMENT 'Specific departments, areas, restrictions',
  `permission_source` enum('role_based','document_assignment','emergency_grant','temporary_elevation') COLLATE utf8mb4_unicode_ci DEFAULT 'role_based',
  `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'NULL = permanent, based on role validity',
  `is_cached` tinyint(1) DEFAULT '1',
  `cache_invalidated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_permission` (`user_id`,`permission_name`,`context`),
  KEY `idx_permission_lookup` (`permission_name`,`scope_level`),
  KEY `idx_cache_expiry` (`expires_at`,`is_cached`),
  KEY `idx_user_context` (`user_id`,`context`,`calculated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status:** Currently implemented with 25 rows

#### **dms_user_roles** - User Role Assignments
```sql
CREATE TABLE `dms_user_roles` (
  `user_id` int(11) NOT NULL COMMENT 'KaizenAuth user ID',
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `granted_by` int(11) NOT NULL COMMENT 'KaizenAuth user ID who granted access',
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_access` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `process_areas` json DEFAULT NULL COMMENT 'Array of process areas',
  `assigned_by_user_id` int(11) DEFAULT NULL,
  `assignment_reason` text COLLATE utf8mb4_unicode_ci,
  `effective_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `effective_until` timestamp NULL DEFAULT NULL COMMENT 'NULL = permanent',
  `scope_context` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON scope limitations',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Role expiration',
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_dms_user_roles_active` (`user_id`,`status`),
  KEY `idx_granted_by` (`granted_by`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `dms_user_roles_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `dms_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Foreign Keys:**
- `role_id` → `dms_roles.id`

**Status:** Currently implemented with 2 rows

### **System Configuration Tables**

#### **dms_settings** - Application Configuration Storage
```sql
CREATE TABLE `dms_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'KaizenAuth user ID who updated the setting',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`),
  KEY `idx_updated_by` (`updated_by`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status:** Currently implemented with 2 rows

---

## ⚙️ **Implementation Status**

### ✅ **Implemented Tables (22)**
- **dms_activity_log** - System Activity Audit Trail (5 rows)
- **dms_categories** - Document Categorization (4 rows)
- **dms_customers** - Customer Data Management (0 rows)
- **dms_departments** - Department Structure (5 rows)
- **dms_document_acl** - Document acl (0 rows)
- **dms_document_assignments** - Document assignments (0 rows)
- **dms_document_hierarchy** - Document hierarchy (0 rows)
- **dms_document_types** - Document Type Catalog (7 rows)
- **dms_documents** - Document Repository (1 rows)
- **dms_languages** - Multi-language Support (4 rows)
- **dms_notification_channels** - Communication Channels (2 rows)
- **dms_notification_templates** - Message Templates (2 rows)
- **dms_permissions** - Permission Catalog (38 rows)
- **dms_process_areas** - Process Classification (7 rows)
- **dms_review_cycles** - Review Scheduling (3 rows)
- **dms_role_permissions** - Role-Permission Mapping (35 rows)
- **dms_roles** - Role Definitions (12 rows)
- **dms_settings** - Application Configuration Storage (2 rows)
- **dms_sites** - Site/Location Management (2 rows)
- **dms_suppliers** - Supplier Qualification (0 rows)
- **dms_user_effective_permissions** - RBAC Permission Caching (25 rows)
- **dms_user_roles** - User Role Assignments (2 rows)

### 🔧 **RBAC System Status**
**RBAC Enabled:** Yes
**RBAC Tables:** 5 implemented
- `dms_permissions` (38 rows)
- `dms_role_permissions` (35 rows)
- `dms_roles` (12 rows)
- `dms_user_effective_permissions` (25 rows)
- `dms_user_roles` (2 rows)

---

## 📈 **Database Statistics**

- **Total Tables:** 22
- **Total Records:** 156
- **Master Data Tables:** 9
- **RBAC Tables:** 5
- **Document Management Tables:** 5
- **Audit Trail Tables:** 1

---

## 🔄 **Migration Notes**

This documentation reflects the **actual current state** of the database as of 2025-09-17 16:49:50.

**Key Changes from Original Documentation:**
- ✅ Document management system is **implemented** with 5 tables
- ✅ Audit trail system is **implemented** with 1 tables
- ✅ RBAC system is **partially implemented** with 5 tables

**Recommendations:**
1. Update main `database_structure.md` file with this current schema
2. Mark unimplemented tables as 'Future' in documentation
3. Document the current RBAC implementation status
4. Add migration plan for remaining master data tables

---

**Generated by KaizenDMS Database Documentation Generator**
**Timestamp:** 2025-09-17 16:49:50
**Database:** kaizenap_flow_db @ 162.214.80.31
                