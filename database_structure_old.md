# KaizenDMS Database Structure

**Complete database schema documentation for KaizenDMS with advanced RBAC implementation**

---

## 📊 **Database Overview**

**Database Name:** `KaizenDMS`
**Prefix:** `dms_` (all tables)
**Total Tables:** 14 master tables + RBAC system + audit trail
**Storage Engine:** InnoDB with foreign key constraints
**Character Set:** utf8mb4_unicode_ci for full Unicode support

---

## 🏗️ **Table Categories**

### **📋 Core Master Tables (10)**
Foundation tables with sample data for system operation

### **🔐 RBAC System Tables (4)** 
Advanced Role-Based Access Control with additive permissions

### **📝 Document Management Tables (Future)**
Document lifecycle and content management (Phase 1 implementation)

### **📊 Audit Trail Tables (Future)**
Tamper-proof audit logging system (Phase 1 implementation)

---

## 📋 **Core Master Tables**

### **1. `dms_sites`** - Site/Location Management
```sql
CREATE TABLE `dms_sites` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `site_code` varchar(10) NOT NULL,
    `site_name` varchar(100) NOT NULL,
    `address` text,
    `city` varchar(50),
    `state` varchar(50),
    `country` varchar(50) DEFAULT 'India',
    `postal_code` varchar(20),
    `phone` varchar(20),
    `email` varchar(100),
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `site_code` (`site_code`)
);
```

**Sample Data:** MUMBAI, PUNE, AURANGABAD, NASHIK, NAGPUR

### **2. `dms_departments`** - Department Structure
```sql
CREATE TABLE `dms_departments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `dept_code` varchar(10) NOT NULL,
    `dept_name` varchar(100) NOT NULL,
    `description` text,
    `parent_dept_id` int(11) DEFAULT NULL,
    `dept_head_user_id` int(11) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `dept_code` (`dept_code`),
    KEY `parent_dept_id` (`parent_dept_id`),
    CONSTRAINT `dms_departments_ibfk_1` FOREIGN KEY (`parent_dept_id`) REFERENCES `dms_departments` (`id`)
);
```

**Sample Data:** QA (Quality Assurance), MFG (Manufacturing), ENG (Engineering), HR (Human Resources), IT (Information Technology), FIN (Finance)

### **3. `dms_customers`** - Customer Data Management
```sql
CREATE TABLE `dms_customers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_code` varchar(20) NOT NULL,
    `customer_name` varchar(200) NOT NULL,
    `short_name` varchar(50),
    `customer_type` enum('OEM','Tier1','Tier2','Tier3','Other') DEFAULT 'OEM',
    `address` text,
    `city` varchar(50),
    `state` varchar(50),
    `country` varchar(50) DEFAULT 'India',
    `postal_code` varchar(20),
    `primary_contact` varchar(100),
    `email` varchar(100),
    `phone` varchar(20),
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `customer_code` (`customer_code`)
);
```

**Status:** Ready for customer data entry

### **4. `dms_suppliers`** - Supplier Qualification
```sql
CREATE TABLE `dms_suppliers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `supplier_code` varchar(20) NOT NULL,
    `supplier_name` varchar(200) NOT NULL,
    `short_name` varchar(50),
    `supplier_type` enum('Material','Service','Both') DEFAULT 'Material',
    `qualification_status` enum('Qualified','Under_Review','Rejected','Suspended') DEFAULT 'Under_Review',
    `address` text,
    `city` varchar(50),
    `state` varchar(50),
    `country` varchar(50) DEFAULT 'India',
    `postal_code` varchar(20),
    `primary_contact` varchar(100),
    `email` varchar(100),
    `phone` varchar(20),
    `qualification_date` date,
    `review_due_date` date,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `supplier_code` (`supplier_code`)
);
```

**Status:** Ready for supplier qualification data

### **5. `dms_process_areas`** - Process Classification
```sql
CREATE TABLE `dms_process_areas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `area_code` varchar(10) NOT NULL,
    `area_name` varchar(100) NOT NULL,
    `description` text,
    `parent_area_id` int(11) DEFAULT NULL,
    `responsible_dept_id` int(11) DEFAULT NULL,
    `risk_level` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `area_code` (`area_code`),
    KEY `parent_area_id` (`parent_area_id`),
    KEY `responsible_dept_id` (`responsible_dept_id`),
    CONSTRAINT `dms_process_areas_ibfk_1` FOREIGN KEY (`parent_area_id`) REFERENCES `dms_process_areas` (`id`),
    CONSTRAINT `dms_process_areas_ibfk_2` FOREIGN KEY (`responsible_dept_id`) REFERENCES `dms_departments` (`id`)
);
```

**Sample Data:** WELD (Welding), STITCH (Stitching), ASSY (Assembly), PAINT (Painting), QC (Quality Control), PACK (Packaging), SHIP (Shipping)

### **6. `dms_document_types`** - Document Type Catalog
```sql
CREATE TABLE `dms_document_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type_code` varchar(10) NOT NULL,
    `type_name` varchar(100) NOT NULL,
    `description` text,
    `numbering_format` varchar(50),
    `retention_years` int(11) DEFAULT 7,
    `requires_approval` tinyint(1) DEFAULT 1,
    `approval_levels` int(11) DEFAULT 1,
    `is_controlled` tinyint(1) DEFAULT 1,
    `category` enum('Policy','Procedure','Instruction','Form','Record','Drawing','Specification','Other') DEFAULT 'Procedure',
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `type_code` (`type_code`)
);
```

**Sample Data:** POL (Policy), SOP (Standard Operating Procedure), WI (Work Instruction), FORM (Forms), RECORD (Records), DWG (Drawing), SPEC (Specification)

### **7. `dms_languages`** - Multi-language Support
```sql
CREATE TABLE `dms_languages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lang_code` varchar(10) NOT NULL,
    `lang_name` varchar(50) NOT NULL,
    `native_name` varchar(50),
    `is_rtl` tinyint(1) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 100,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `lang_code` (`lang_code`)
);
```

**Sample Data:** en (English), mr (Marathi), hi (Hindi), gu (Gujarati)

### **8. `dms_review_cycles`** - Review Scheduling
```sql
CREATE TABLE `dms_review_cycles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `cycle_code` varchar(10) NOT NULL,
    `cycle_name` varchar(50) NOT NULL,
    `months_interval` int(11) NOT NULL,
    `description` text,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cycle_code` (`cycle_code`)
);
```

**Sample Data:** ANNUAL (12 months), BIENNIAL (24 months), TRIENNIAL (36 months), QUARTERLY (3 months)

### **9. `dms_notification_templates`** - Message Templates
```sql
CREATE TABLE `dms_notification_templates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `template_code` varchar(20) NOT NULL,
    `template_name` varchar(100) NOT NULL,
    `subject` varchar(200),
    `message_template` text NOT NULL,
    `template_type` enum('Email','SMS','WhatsApp','In_App') NOT NULL,
    `event_trigger` varchar(50),
    `variables` text,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `template_code` (`template_code`)
);
```

**Sample Data:** Document approval requests, training assignments, review reminders

### **10. `dms_notification_channels`** - Communication Channels
```sql
CREATE TABLE `dms_notification_channels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `channel_code` varchar(20) NOT NULL,
    `channel_name` varchar(100) NOT NULL,
    `channel_type` enum('Email_SMTP','WhatsApp_API','SMS_Gateway','Push_Notification') NOT NULL,
    `endpoint_url` varchar(500),
    `api_key` varchar(200),
    `configuration` text,
    `is_active` tinyint(1) DEFAULT 1,
    `last_tested` timestamp NULL DEFAULT NULL,
    `test_status` enum('Success','Failed','Not_Tested') DEFAULT 'Not_Tested',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `channel_code` (`channel_code`)
);
```

**Sample Data:** EMAIL_SMTP, WHATSAPP_BUSINESS, SMS_TWILIO

---

## 🔐 **RBAC System Tables**

### **1. `dms_roles`** - Role Definitions
```sql
CREATE TABLE `dms_roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,                    -- Legacy field (admin, manager, user)
    `role_name` varchar(50) DEFAULT NULL,           -- New RBAC field (operator, line_lead, etc.)
    `display_name` varchar(100) DEFAULT NULL,       -- Human-readable name
    `description` text,                             -- Role description
    `is_system_role` tinyint(1) DEFAULT 0,         -- System vs custom role
    `hierarchy_level` int(11) DEFAULT 100,         -- Role hierarchy (lower = higher authority)
    `scope` enum('all','cross_department','department','process_area','station','assigned_only') DEFAULT 'assigned_only',
    `max_permissions` int(11) DEFAULT 100,          -- Permission limit for role
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `role_name` (`role_name`),
    KEY `hierarchy_level` (`hierarchy_level`)
);
```

**System Roles Hierarchy (hierarchy_level):**
1. **system_admin** (10) - Complete system authority
2. **pso** (20) - Product Safety Officer with cross-department access
3. **department_owner** (30) - Complete department authority
4. **engineer** (40) - Technical specialist for document creation/editing
5. **supervisor** (50) - Process area supervisor with approval authority
6. **line_lead** (60) - Production line leader with team oversight
7. **operator** (70) - Basic production operator with minimal permissions

### **2. `dms_permissions`** - Permission Catalog
```sql
CREATE TABLE `dms_permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,                   -- Permission identifier (documents.view.all)
    `display_name` varchar(150) DEFAULT NULL,       -- Human-readable name
    `description` text,                             -- Permission description
    `category` varchar(50) DEFAULT 'general',      -- Permission grouping
    `resource` varchar(50) DEFAULT NULL,           -- Resource type (documents, users, reports)
    `action` varchar(50) DEFAULT NULL,             -- Action type (view, create, edit, delete)
    `scope_level` enum('all','cross_department','department','process_area','station','assigned_only') DEFAULT 'assigned_only',
    `risk_level` enum('low','medium','high','critical') DEFAULT 'medium',
    `requires_context` tinyint(1) DEFAULT 0,       -- Needs additional context
    `is_system_permission` tinyint(1) DEFAULT 0,   -- System vs custom permission
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `category` (`category`),
    KEY `resource` (`resource`),
    KEY `scope_level` (`scope_level`)
);
```

**Permission Categories:**
- **documents** - Document management permissions
- **users** - User and role management
- **reports** - Reporting and analytics
- **admin** - System administration
- **audit** - Audit trail access
- **training** - Training system management

### **3. `dms_role_permissions`** - Role-Permission Mapping
```sql
CREATE TABLE `dms_role_permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `role_id` int(11) NOT NULL,
    `permission_id` int(11) NOT NULL,
    `granted_scope` enum('all','cross_department','department','process_area','station','assigned_only') DEFAULT NULL,
    `context_filter` text DEFAULT NULL,            -- JSON context filters
    `is_inherited` tinyint(1) DEFAULT 0,          -- Inherited from parent role
    `granted_by` int(11) DEFAULT NULL,            -- User who granted permission
    `granted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NULL DEFAULT NULL,     -- Temporary permissions
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_permission` (`role_id`,`permission_id`),
    KEY `permission_id` (`permission_id`),
    KEY `granted_by` (`granted_by`),
    CONSTRAINT `dms_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `dms_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `dms_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `dms_permissions` (`id`) ON DELETE CASCADE
);
```

### **4. `dms_user_roles`** - User Role Assignments
```sql
CREATE TABLE `dms_user_roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,                    -- KaizenAuth user ID
    `role_id` int(11) NOT NULL,
    `status` enum('active','inactive','suspended') DEFAULT 'active',
    `scope_context` text DEFAULT NULL,            -- JSON scope limitations
    `granted_by` int(11) DEFAULT NULL,           -- Administrator who granted role
    `granted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NULL DEFAULT NULL,    -- Role expiration
    `last_access` timestamp NULL DEFAULT NULL,   -- Last access tracking
    `notes` text DEFAULT NULL,                   -- Administrative notes
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_role` (`user_id`,`role_id`),
    KEY `role_id` (`role_id`),
    KEY `status` (`status`),
    KEY `granted_by` (`granted_by`),
    CONSTRAINT `dms_user_roles_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `dms_roles` (`id`) ON DELETE CASCADE
);
```

---

## ⚙️ **RBAC System Features**

### **🔺 Hierarchical Role System**
- **Lower hierarchy_level = Higher authority**
- **Automatic permission inheritance** from higher-authority roles
- **Scope-based permissions** (all → cross_department → department → process_area → station → assigned_only)

### **🔄 Additive Permission Model**
- **Multiple roles per user** with union of all permissions
- **Context-aware permissions** based on department/process area
- **Temporary role assignments** with expiration dates

### **🛡️ Advanced Security Features**
- **Permission scope enforcement** at database level
- **Audit trail integration** for all role/permission changes
- **Role conflict detection** and resolution
- **Automatic cache invalidation** for permission changes

### **📊 Permission Scope Hierarchy**
1. **all** - System-wide access (system_admin, pso)
2. **cross_department** - Multi-department access (pso, department_owner)
3. **department** - Single department access (department_owner, engineer)
4. **process_area** - Process area access (supervisor, engineer)
5. **station** - Station-specific access (line_lead, supervisor)
6. **assigned_only** - Only assigned items (operator, line_lead)

---

## 🔧 **RBAC Integration Status**

### ✅ **Completed Integration**
- **AccessControl.php** enhanced with AdditivePermissionManager integration
- **Legacy fallback** for backward compatibility with existing authentication
- **KaizenAuth SSO preservation** - no disruption to authentication flow
- **Role conflict resolution** between original roles (1-4) and new RBAC roles (6-14)

### 🛠️ **RBAC Tools Available**
- **`tools/access_control_pdca_test.php`** - PDCA testing for RBAC integration
- **`tools/fix_role_names.php`** - Fix missing role_name values
- **`tools/resolve_role_conflicts.php`** - Resolve role ID conflicts
- **`admin/roles_permissions.php`** - Web interface for RBAC management

### 🔄 **Backup & Recovery**
- **Complete backups** created in `backups/rbac_integration_20250916_100746/`
- **Original files preserved** before RBAC integration
- **Safe rollback capability** if needed

---

## 📈 **Future Database Expansion**

### **Phase 1 - Document Management Tables**
- `dms_documents` - Main document repository
- `dms_document_versions` - Version control system  
- `dms_document_metadata` - Flexible metadata storage
- `dms_document_acl` - Document-level access control
- `dms_approval_workflows` - Workflow state management

### **Phase 1 - Audit Trail Tables**
- `dms_audit_log` - Master audit with hash chains
- `dms_document_audit` - Document-specific audit trail
- `dms_access_audit` - Access control audit logging
- `dms_system_audit` - System configuration changes

### **Phase 1 - Training System Tables**
- `dms_training_assignments` - Training task assignments
- `dms_training_completions` - Training completion tracking
- `dms_training_history` - Historical training records

---

**Database Engineering Notes:**
- **All tables use InnoDB** for ACID compliance and foreign key support
- **UTF8MB4 encoding** for full Unicode support including emojis
- **Timestamp tracking** on all tables with created_at/updated_at
- **Soft deletes** via is_active flags where appropriate
- **Optimized indexing** for performance on large datasets
- **Foreign key constraints** ensure referential integrity