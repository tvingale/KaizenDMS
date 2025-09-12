# KaizenMaster - Existing Master Data Catalog

## Overview
This document provides a comprehensive catalog of all existing master data available in KaizenMaster for other KaizenFlow modules to analyze and reuse.

## Master Data Categories

### 1. Organization & Identity

#### **Sites** (`master_sites`)
**Purpose:** Manufacturing locations with complete operational details

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Unique identifier | 1, 2, 3 |
| name | VARCHAR(100) | Yes | Site display name | "Pune Plant", "Chennai Factory" |
| code | VARCHAR(20) | Yes | Unique site code | "B-75", "G-44", "MH-01" |
| address | TEXT | No | Complete postal address | "Survey No. 123, Industrial Area" |
| city | VARCHAR(50) | No | City name | "Pune", "Chennai" |
| state | VARCHAR(50) | No | State/Province | "Maharashtra", "Tamil Nadu" |
| country | VARCHAR(50) | No | Country | "India" |
| postal_code | VARCHAR(20) | No | ZIP/Postal code | "411057", "600001" |
| timezone | VARCHAR(50) | No | Site timezone | "Asia/Kolkata" |
| contact_name | VARCHAR(100) | No | Site contact person | "Rajesh Kumar" |
| contact_email | VARCHAR(255) | No | Contact email | "rajesh.kumar@company.com" |
| contact_phone | VARCHAR(20) | No | Contact phone | "+91-9876543210" |
| is_active | BOOLEAN | Yes | Active status | true, false |
| valid_from | DATE | No | Effective start date | "2025-01-01" |
| valid_to | DATE | No | Effective end date | "2025-12-31" |
| created_by | INT | Yes | Creator user ID | 1, 2, 3 |
| created_at | TIMESTAMP | Yes | Creation timestamp | "2025-01-15 10:30:00" |
| updated_by | INT | No | Last updater ID | 1, 2, 3 |
| updated_at | TIMESTAMP | No | Last update timestamp | "2025-01-15 15:45:00" |

**Relationships:**
- One-to-Many with `master_areas`
- Referenced by task assignments, production planning
- Used in location-based reporting

**Business Rules:**
- Site codes must be unique globally
- Cannot delete sites with active areas
- Timezone defaults to "Asia/Kolkata" if not specified

---

#### **Areas** (`master_areas`)
**Purpose:** Production areas within sites (Welding, Assembly, Painting, etc.)

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Unique identifier | 1, 2, 3 |
| name | VARCHAR(100) | Yes | Area display name | "Welding Shop", "Final Assembly" |
| code | VARCHAR(20) | Yes | Unique area code | "WLD-01", "ASM-02", "PAINT-01" |
| site_id | INT FOREIGN KEY | Yes | Parent site reference | 1, 2, 3 |
| description | TEXT | No | Area description | "Main welding area with 12 stations" |
| is_active | BOOLEAN | Yes | Active status | true, false |
| valid_from | DATE | No | Effective start date | "2025-01-01" |
| valid_to | DATE | No | Effective end date | "2025-12-31" |
| created_by | INT | Yes | Creator user ID | 1, 2, 3 |
| created_at | TIMESTAMP | Yes | Creation timestamp | "2025-01-15 10:30:00" |
| updated_by | INT | No | Last updater ID | 1, 2, 3 |
| updated_at | TIMESTAMP | No | Last update timestamp | "2025-01-15 15:45:00" |

**Relationships:**
- Many-to-One with `master_sites`
- Referenced by work station assignments
- Used in production line planning

**Business Rules:**
- Area codes must be unique within a site
- Cannot delete areas referenced by active work orders
- Must belong to an active site

---

#### **Departments** (`master_departments`)
**Purpose:** Organizational units and hierarchy (Engineering, Quality, Production, etc.)

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Unique identifier | 1, 2, 3 |
| name | VARCHAR(100) | Yes | Department name | "Quality Assurance", "Production Engineering" |
| code | VARCHAR(20) | Yes | Department code | "QA", "PE", "MAINT" |
| description | TEXT | No | Department description | "Responsible for quality control processes" |
| is_active | BOOLEAN | Yes | Active status | true, false |
| valid_from | DATE | No | Effective start date | "2025-01-01" |
| valid_to | DATE | No | Effective end date | "2025-12-31" |
| created_by | INT | Yes | Creator user ID | 1, 2, 3 |
| created_at | TIMESTAMP | Yes | Creation timestamp | "2025-01-15 10:30:00" |
| updated_by | INT | No | Last updater ID | 1, 2, 3 |
| updated_at | TIMESTAMP | No | Last update timestamp | "2025-01-15 15:45:00" |

**Note:** Parent-child relationships and head information temporarily disabled due to schema limitations

**Relationships:**
- Used in user role assignments
- Referenced by approval workflows
- Connected to responsibility matrices

**Business Rules:**
- Department codes must be unique
- Cannot delete departments with active users
- Used for task routing and escalations

---

### 2. Business Partners

#### **Customers** (`master_customers`)
**Purpose:** Customer details and contact information for orders and programs

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Unique identifier | 1, 2, 3 |
| name | VARCHAR(100) | Yes | Customer name | "Tata Motors", "Mahindra & Mahindra" |
| code | VARCHAR(20) | Yes | Customer code | "TATA", "M&M", "BAJAJ" |
| customer_type | ENUM | Yes | Customer category | "OEM", "Tier1", "Tier2", "Export" |
| address | TEXT | No | Customer address | "Bombay House, Mumbai" |
| city | VARCHAR(50) | No | City | "Mumbai", "Pune", "Chennai" |
| state | VARCHAR(50) | No | State | "Maharashtra", "Tamil Nadu" |
| country | VARCHAR(50) | Yes | Country | "India", "USA", "Germany" |
| postal_code | VARCHAR(20) | No | Postal code | "400001", "411057" |
| contact_name | VARCHAR(100) | No | Primary contact | "Amit Sharma" |
| contact_email | VARCHAR(255) | No | Contact email | "amit.sharma@tata.com" |
| contact_phone | VARCHAR(20) | No | Contact phone | "+91-9876543210" |
| gst_number | VARCHAR(15) | No | GST registration | "27AAAAA0000A1Z5" |
| pan_number | VARCHAR(10) | No | PAN number | "AAAAA0000A" |
| credit_limit | DECIMAL(15,2) | No | Credit limit amount | 1000000.00, 5000000.00 |
| payment_terms | VARCHAR(50) | No | Payment terms | "Net 30", "Advance", "60 Days" |
| is_active | BOOLEAN | Yes | Active status | true, false |
| valid_from | DATE | No | Effective start date | "2025-01-01" |
| valid_to | DATE | No | Effective end date | "2025-12-31" |
| created_by | INT | Yes | Creator user ID | 1, 2, 3 |
| created_at | TIMESTAMP | Yes | Creation timestamp | "2025-01-15 10:30:00" |
| updated_by | INT | No | Last updater ID | 1, 2, 3 |
| updated_at | TIMESTAMP | No | Last update timestamp | "2025-01-15 15:45:00" |

**Relationships:**
- One-to-Many with customer programs
- Referenced in order management
- Used in quality planning (PPAP)

**Business Rules:**
- Customer codes must be unique
- GST number format validation for Indian customers
- Credit limit checks in order processing

---

#### **Suppliers** (`master_suppliers`)
**Purpose:** Supplier details and contact information for procurement and quality

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Unique identifier | 1, 2, 3 |
| name | VARCHAR(100) | Yes | Supplier name | "ABC Steel Ltd", "XYZ Chemicals" |
| code | VARCHAR(20) | Yes | Supplier code | "SUP001", "CHEM01", "STEEL05" |
| supplier_type | ENUM | Yes | Supplier category | "Material", "Process", "Lab", "Equipment", "Maintenance", "Logistics" |
| category | VARCHAR(100) | No | Material category | "Raw Materials", "Chemicals", "Components" |
| address | TEXT | No | Supplier address | "Industrial Estate, Sector 5" |
| city | VARCHAR(50) | No | City | "Mumbai", "Pune", "Chennai" |
| state | VARCHAR(50) | No | State | "Maharashtra", "Tamil Nadu" |
| country | VARCHAR(50) | Yes | Country | "India", "USA", "China" |
| postal_code | VARCHAR(20) | No | Postal code | "400001", "411057" |
| contact_name | VARCHAR(100) | No | Primary contact | "Rajesh Patel" |
| contact_email | VARCHAR(255) | No | Contact email | "rajesh@abcsteel.com" |
| contact_phone | VARCHAR(20) | No | Contact phone | "+91-9876543210" |
| gst_number | VARCHAR(15) | No | GST registration | "27BBBBB1111B2Z6" |
| pan_number | VARCHAR(10) | No | PAN number | "BBBBB1111B" |
| iso_certified | BOOLEAN | Yes | ISO certification status | true, false |
| iatf_certified | BOOLEAN | Yes | IATF 16949 status | true, false |
| certification_details | TEXT | No | Certification details | "ISO 9001:2015 valid until Dec 2025" |
| payment_terms | VARCHAR(50) | No | Payment terms | "Net 30", "Advance Payment" |
| is_active | BOOLEAN | Yes | Active status | true, false |
| valid_from | DATE | No | Effective start date | "2025-01-01" |
| valid_to | DATE | No | Effective end date | "2025-12-31" |
| created_by | INT | Yes | Creator user ID | 1, 2, 3 |
| created_at | TIMESTAMP | Yes | Creation timestamp | "2025-01-15 10:30:00" |
| updated_by | INT | No | Last updater ID | 1, 2, 3 |
| updated_at | TIMESTAMP | No | Last update timestamp | "2025-01-15 15:45:00" |

**Relationships:**
- Referenced in purchase orders
- Connected to supplier assessments
- Used in PPAP submissions

**Business Rules:**
- Supplier codes must be unique
- Certification tracking for quality compliance
- Supplier type determines approval workflows

---

## System Configuration Tables

### **Permissions** (`master_permissions`)
**Purpose:** Granular permissions for access control

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Permission ID | 1, 2, 3 |
| name | VARCHAR(100) | Yes | Permission name | "sites.view", "customers.create" |
| description | TEXT | Yes | Description | "View site master data" |
| resource | VARCHAR(50) | Yes | Resource type | "sites", "customers", "suppliers" |
| action | VARCHAR(50) | Yes | Action type | "view", "create", "edit", "delete" |
| is_active | BOOLEAN | Yes | Active status | true, false |
| created_at | TIMESTAMP | Yes | Creation time | "2025-01-15 10:30:00" |

**Current Permissions Available:**
```
sites.view, sites.create, sites.edit, sites.delete
areas.view, areas.create, areas.edit, areas.delete  
departments.view, departments.create, departments.edit, departments.delete
customers.view, customers.create, customers.edit, customers.delete
suppliers.view, suppliers.create, suppliers.edit, suppliers.delete
categories.manage, admin.logs, admin.maintenance, admin.settings
reports.view, reports.export, users.view, users.manage
```

### **Roles** (`master_roles`)
**Purpose:** User role definitions

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Role ID | 1, 2, 3 |
| name | VARCHAR(50) | Yes | Role name | "admin", "manager", "viewer" |
| description | TEXT | No | Role description | "System administrator" |
| is_active | BOOLEAN | Yes | Active status | true, false |
| created_at | TIMESTAMP | Yes | Creation time | "2025-01-15 10:30:00" |

### **Activity Log** (`master_activity_log`)
**Purpose:** Audit trail for all master data changes

| Field Name | Data Type | Required | Description | Sample Values |
|------------|-----------|----------|-------------|---------------|
| id | INT PRIMARY KEY | Yes | Log ID | 1, 2, 3 |
| user_id | INT | Yes | User who made change | 1, 2, 3 |
| action | VARCHAR(10) | Yes | Action performed | "CREATE", "UPDATE", "DELETE" |
| entity_type | VARCHAR(50) | Yes | Table name | "master_sites", "master_customers" |
| entity_id | INT | Yes | Record ID | 1, 2, 3 |
| created_at | TIMESTAMP | Yes | When action occurred | "2025-01-15 10:30:00" |

**Business Value:** Complete audit trail for compliance and troubleshooting

---

## Integration Patterns Currently Used

### 1. **KaizenAuth Integration**
- User authentication via JWT cookies
- SSO login flow
- Permission-based access control

### 2. **Direct Database Access**
- All master tables accessible directly
- Proper indexing for performance
- Foreign key constraints for data integrity

### 3. **Audit Trail System**
- All changes logged automatically
- User attribution for all actions
- Timestamp tracking for compliance

---

## Data Volumes and Performance

### Current Data Volumes (Approximate):
- Sites: 10-50 records
- Areas: 50-200 records  
- Departments: 20-100 records
- Customers: 100-1000 records
- Suppliers: 200-2000 records
- Permissions: 25 records
- Roles: 5-10 records

### Performance Characteristics:
- Read operations: <50ms average
- Write operations: <100ms average
- Batch operations: 1000 records/minute
- Concurrent users: Tested up to 50

---

## Naming Conventions

### Table Names:
- Pattern: `master_{entity_name}`
- Example: `master_sites`, `master_customers`

### Field Names:
- snake_case format
- Descriptive names
- Standard audit fields: `created_by`, `created_at`, `updated_by`, `updated_at`

### Code Values:
- UPPERCASE for codes
- Meaningful abbreviations
- Unique within entity type

---

## Standard Audit Fields

**Every master table includes these fields:**

```sql
is_active BOOLEAN NOT NULL DEFAULT 1,
valid_from DATE NULL,
valid_to DATE NULL,
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_by INT NULL,
updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
```

**Benefits:**
- Complete audit trail
- Soft delete capability
- Effective dating for historical accuracy
- User attribution for all changes

---

## Reusability Guidelines

### High Reusability (Recommended for Reuse):
1. **Sites** - Location-based operations
2. **Departments** - Organizational workflows
3. **Customers** - Customer-facing modules
4. **Suppliers** - Procurement-related modules

### Medium Reusability (Consider Extension):
1. **Areas** - May need production-specific fields
2. **Permissions System** - May need module-specific permissions

### Extension Examples:
```sql
-- Adding fields to sites for task management
ALTER TABLE master_sites 
ADD COLUMN operating_hours_start TIME,
ADD COLUMN operating_hours_end TIME,
ADD COLUMN max_concurrent_tasks INT DEFAULT 10;

-- Adding fields to departments for escalation
ALTER TABLE master_departments
ADD COLUMN escalation_sla_hours INT DEFAULT 24,
ADD COLUMN requires_approval BOOLEAN DEFAULT false;
```

This catalog provides the complete foundation for other KaizenFlow modules to make informed decisions about reusing existing master data versus creating new entities.