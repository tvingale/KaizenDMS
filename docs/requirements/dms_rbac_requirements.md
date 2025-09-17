# KaizenDMS Role-Based Access Control (RBAC) Requirements

## Executive Summary

This document defines the comprehensive Role-Based Access Control (RBAC) system for KaizenDMS, supporting multi-department, multi-role, and hierarchical document management with flexible permission assignment. The system implements additive role permissions (union model) with document-level access control, department ownership, and context-aware access management.

---

## RBAC Architecture Overview

### Access Control Flow
```
KaizenAuth Authentication → Module Access Check → Multi-Role Context → Document-Level ACL → Hierarchical Permissions → Final Access Decision
```

### Core Principles
1. **Additive Role Model** - User permissions are the union of all assigned roles
2. **Department Ownership** - Department owners have full control within their domain
3. **Context-Aware Access** - Permissions vary based on user's active role context
4. **Document-Specific Assignments** - Temporary access for specific documents
5. **Hierarchical Organization** - Documents organized in tree structure with inherited permissions

---

## Multi-Department & Multi-Role Support

### Department Ownership Model
**Department owners have complete authority within their department:**
- Full CRUD operations for department documents
- User management within department
- Report generation for department metrics
- Master data management for department-specific elements
- Cross-department collaboration through structured permissions

### Multi-Role User Examples
```
Priya Sharma:
├── Primary Role: Quality Department Owner
│   ├── Full control: Quality department documents
│   ├── User management: Quality department staff
│   └── Reports: Quality metrics and compliance
├── Additional Role: Internal Auditor
│   ├── Read access: All department documents
│   ├── Audit trail: Complete system access logs
│   └── Compliance: Cross-department review capabilities
├── Additional Role: ISO Coordinator
│   ├── ISO documents: All ISO-tagged documents
│   ├── Management review: System-wide compliance data
│   └── Training: ISO requirement assignments
└── Additional Role: Safety Committee Member
    ├── Safety documents: All safety-critical documents
    ├── Incident investigation: Cross-department access
    └── Safety recommendations: System-wide input authority
```

---

## Role Hierarchy & Permission Model

### Standard Role Hierarchy

**1. OPERATOR (Base Level)**
```
Role: 'operator'
Department Scope: Assigned areas only
Permissions:
- documents.view.assigned          # Only assigned work instructions
- documents.read                   # Read-only document access
- training.complete                # Complete E-1A acknowledgments  
- qr.scan                         # QR code scanning access
- reports.view.personal           # Personal training/performance data
```

**2. LINE LEAD (Inherits Operator + Additional)**
```
Role: 'line_lead'  
Department Scope: Assigned production lines
Permissions:
- documents.view.area             # View all docs for assigned area
- documents.print.controlled      # Generate controlled copies
- training.monitor.team           # Monitor team training completion
- escalation.receive             # Receive escalation notifications
- reports.view.area              # Area-specific reports
- users.view.direct_reports      # View team member information
```

**3. SUPERVISOR (Inherits Line Lead + Additional)**
```
Role: 'supervisor'
Department Scope: Process areas within department
Permissions:
- documents.approve.routine       # Approve routine document changes
- documents.create               # Create new documents (drafts)
- documents.assign.reviews       # Assign document reviews
- reports.view.area              # Area-specific KPI reports
- users.manage.area              # Manage area team members
- workflow.escalate              # Escalate workflow issues
```

**4. ENGINEER (Specialized Technical Role)**
```
Role: 'engineer' 
Department Scope: Technical domain expertise
Permissions:
- documents.create.technical     # Create technical docs (SOPs, WIs)
- documents.edit                 # Edit existing documents
- documents.link                 # Link related documents (E-3)
- drawings.upload                # Upload technical drawings
- metadata.edit                  # Edit document metadata
- specifications.define          # Define technical specifications
- change.impact.assess           # Assess change impacts
```

**5. DEPARTMENT OWNER (Department Authority)**
```
Role: 'department_owner'
Department Scope: Complete department control
Permissions:
- documents.create.department    # Create any document type in department
- documents.edit.department      # Edit any department document
- documents.delete.department    # Delete/obsolete department documents
- documents.approve.department   # Approve department documents
- users.manage.department        # Complete user management
- reports.generate.department    # Generate department reports
- masters.manage.department      # Manage department master data
- workflow.control.department    # Control department workflows
- budget.view.department         # View department budget data
```

**6. PSO - PRODUCT SAFETY OFFICER (Safety Authority)**
```
Role: 'pso'
Department Scope: Cross-department safety authority
Permissions:
- documents.approve.safety       # Mandatory for safety-critical docs
- documents.view.safety.all      # View all safety-critical documents
- safety.gate.control           # Control safety gate rules
- incident.investigate.all       # Investigate safety incidents
- emergency.access              # Emergency document access
- safety.standards.define        # Define safety standards
- regulatory.compliance.monitor  # Monitor regulatory compliance
```

**7. ADMIN (System Administration)**
```
Role: 'admin'
Department Scope: System-wide administration
Permissions:
- system.manage                 # System configuration
- users.manage.all              # User and role management
- masters.manage.all            # Manage all master data tables
- backup.restore                # System backup/restore
- audit.export                  # Export audit reports
- security.configure            # Security settings management
- integration.configure         # External system integrations
```

---

## Additive Permission Model (Union of Roles)

### Permission Union Logic
When a user has multiple roles, their effective permissions are the **union (sum)** of all role permissions, taking the most permissive option:

**Example: User with Manufacturing Supervisor + Safety Committee Member**
```
Manufacturing Supervisor Permissions:
- documents.view.manufacturing = true (scope: department)
- documents.edit.manufacturing = true (scope: owned_only)  
- users.manage.team = true (scope: direct_reports)

Safety Committee Member Permissions:
- documents.view.safety_critical = true (scope: all)  # BROADER
- documents.comment.safety = true (scope: all)
- incident.investigate = true (scope: cross_department)

Effective Permissions (Union):
- documents.view.manufacturing = true (scope: all)  # Broader scope wins
- documents.view.safety_critical = true (scope: all)
- documents.edit.manufacturing = true (scope: owned_only)
- documents.comment.safety = true (scope: all)
- users.manage.team = true (scope: direct_reports)
- incident.investigate = true (scope: cross_department)
```

### Scope Hierarchy (Most Permissive Wins)
```
Permission Scope Levels (Highest to Lowest):
1. all                    # System-wide access
2. cross_department       # Multiple departments  
3. department            # Single department
4. process_area          # Specific process area
5. station               # Individual station
6. assigned_only         # Only assigned items
7. none                  # No access
```

---

## Hierarchical Document Organization

### Multi-Level Document Tree Structure
```
📁 KaizenDMS Document Hierarchy
├── 🏭 SITE (B-75, G-44)
│   ├── 🏬 DEPARTMENT (Manufacturing, Quality, Design, Safety)
│   │   ├── 🔧 PROCESS AREA (Welding, Stitching, Assembly, Inspection)  
│   │   │   ├── 🏭 PRODUCTION LINE (Line-1, Line-2, Line-3)
│   │   │   │   ├── 🎯 STATION (Station-15, Station-16, Station-17)
│   │   │   │   │   ├── 📄 DOCUMENT TYPE (POL, SOP, WI, PFMEA, CP, DWG, FORM)
│   │   │   │   │   │   └── 📋 SPECIFIC DOCUMENT
│   │   │   │   │   │       └── WI-2025-0156-BeltAnchor-Rev3
```

### Hierarchical Permission Inheritance
Users gain access to documents based on their position in the organizational hierarchy:
- **Site-level roles:** Access to all documents at the site
- **Department-level roles:** Access to all department documents + public cross-department  
- **Process area roles:** Access to process area documents + department public documents
- **Station-level roles:** Access to station documents + area public documents

---

## Document-Level Access Control Lists (ACL)

### Document Sensitivity Levels
```
1. PUBLIC
   - Description: General information, company policies
   - Access Default: All authenticated users
   - Example: Company Holiday Calendar, General Safety Guidelines

2. INTERNAL  
   - Description: Standard operating procedures, work instructions
   - Access Default: Department and related roles
   - Example: Standard Work Instructions, Process SOPs

3. CONFIDENTIAL
   - Description: Customer specifications, pricing, proprietary processes  
   - Access Default: Explicit permission required
   - Example: GSRTC Customer Requirements, Cost Analysis

4. SAFETY_CRITICAL
   - Description: Safety procedures, PPAP docs, regulatory compliance
   - Access Default: Safety clearance required + PSO approval
   - Example: Welding Safety Procedures, Seat Belt Anchor Specs

5. REGULATORY
   - Description: IATF compliance, audit documents, legal requirements
   - Access Default: Management and auditors only
   - Example: ISO Audit Reports, Regulatory Submissions
```

### Document-Specific Assignment Access
**Temporary access for specific documents when assigned:**
```
Assignment Types:
- expert_reviewer       # Subject matter expert review
- stakeholder_input     # Stakeholder impact assessment  
- compliance_check      # Regulatory compliance verification
- peer_review          # Peer technical review
- consultant           # External expertise consultation

Assignment Permissions:
- Time-limited access (expires after assignment completion)
- Purpose-specific permissions (only what's needed for assignment)
- Audit trail of assignment context and deliverable expectations
- Override normal department/role boundaries when justified
```

---

## Dynamic Permission Definition System

### Permission Categories
```
Documents:
- create, edit, delete, approve, view, print, share, obsolete
- Scopes: all, department, process_area, owned_only, assigned_only

Users:  
- manage, assign_roles, view, train, evaluate
- Scopes: all, department, process_area, direct_reports

Reports:
- generate, export, view, share, schedule
- Scopes: all, department, process_area, personal

System:
- configure, backup, audit, integrate, monitor
- Scopes: all, module_specific, read_only

Workflow:
- approve, escalate, reassign, monitor, control
- Scopes: all, department, process_area, owned
```

### Configurable Permissions Per Role
Each role can have **base permissions** (always granted) and **configurable permissions** (can be enabled/disabled per user):

```
Example - Quality Manager Role:
Base Permissions (Always Granted):
- documents.create.quality = true
- documents.edit.quality = true  
- documents.approve.quality = true
- users.manage.quality_dept = true

Configurable Permissions (Optional):
- documents.view.all_departments = configurable (default: false)
- reports.export.detailed = configurable (default: false, requires approval)
- users.manage.cross_department = configurable (default: false, requires manager approval)
```

---

## Database Schema

### Role Definitions
```sql
CREATE TABLE dms_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(150),
    description TEXT,
    department_scope JSON, -- ["quality", "manufacturing"] or ["*"] for all
    hierarchy_level ENUM('operator', 'lead', 'supervisor', 'manager', 'director', 'executive'),
    can_be_combined_with JSON, -- ["auditor", "iso_coordinator"]
    is_system_role BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```

### Multi-Role User Assignments
```sql
CREATE TABLE dms_user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_name VARCHAR(100) NOT NULL,
    
    -- Context & Scope
    department VARCHAR(100),
    site_id INT,
    process_areas JSON, -- ["welding", "assembly"]
    
    -- Assignment details  
    assigned_by_user_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assignment_reason TEXT,
    
    -- Temporal validity
    effective_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    effective_until TIMESTAMP NULL, -- NULL = permanent
    
    -- Status
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_active_roles (user_id, status, effective_from, effective_until)
);
```

### Permission Catalog
```sql
CREATE TABLE dms_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(150) UNIQUE NOT NULL, -- 'documents.create.quality'
    category VARCHAR(50), -- 'documents', 'users', 'reports'
    action VARCHAR(50), -- 'create', 'edit', 'view'  
    scope_qualifier VARCHAR(50), -- 'quality', 'all', 'department'
    description TEXT,
    risk_level ENUM('low', 'medium', 'high', 'critical'),
    requires_approval BOOLEAN DEFAULT FALSE,
    approved_by_role VARCHAR(100),
    audit_logged BOOLEAN DEFAULT TRUE
);
```

### Document Hierarchy
```sql
CREATE TABLE dms_document_hierarchy (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    site_id INT,
    department_id INT,
    process_area_id INT,
    production_line_id INT NULL,
    station_id INT NULL,
    hierarchy_path VARCHAR(500), -- "/B75/Manufacturing/Assembly/Line2/Station15"
    INDEX idx_hierarchy_path (hierarchy_path),
    FOREIGN KEY (document_id) REFERENCES dms_documents(id)
);
```

### Document Access Control Lists
```sql
CREATE TABLE dms_document_acl (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    sensitivity_level ENUM('public', 'internal', 'confidential', 'safety_critical', 'regulatory'),
    owner_user_id INT NOT NULL,
    co_owners JSON, -- ["user_123", "user_456"]
    explicit_permissions JSON, -- User/role specific permissions
    restrictions JSON, -- Time, location, context restrictions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES dms_documents(id)
);
```

### Document-Specific Assignments
```sql
CREATE TABLE dms_document_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    assigned_to_user_id INT NOT NULL,
    assigned_by_user_id INT NOT NULL,
    assignment_type ENUM('reviewer', 'expert_reviewer', 'approver', 'consultant', 'stakeholder_input'),
    assignment_reason TEXT,
    granted_permissions JSON, -- ["view", "comment", "approve_aspects"]
    access_restrictions JSON, -- {"time_limit": 7, "sections": ["quality"]}
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date TIMESTAMP,
    expires_at TIMESTAMP,
    status ENUM('active', 'completed', 'expired', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (document_id) REFERENCES dms_documents(id)
);
```

---

## Context-Aware Access Management

### Role Context Switching
Users with multiple roles can switch between contexts to access different permission sets:

```
Context Examples:
1. Department Owner Context
   - Full control within owned department
   - Limited cross-department access

2. Auditor Context  
   - Read-only access across all departments
   - Audit trail access
   - Time-limited sessions with full logging

3. Safety Committee Context
   - Access to safety-critical documents across all departments
   - Incident investigation capabilities  
   - Safety recommendation authority

4. Project Coordinator Context
   - Cross-department collaboration permissions
   - Project-specific document access
   - Team coordination capabilities
```

### Access Decision Algorithm
```
1. Authenticate user via KaizenAuth
2. Check module access permissions
3. Determine active role context
4. Calculate effective permissions (union of all active roles)
5. Check document-level ACL restrictions
6. Apply hierarchical inheritance rules
7. Verify any document-specific assignments
8. Apply time/location/context restrictions  
9. Log access decision and context
10. Return final access permissions
```

---

## Implementation Requirements

### Performance Optimization
- **Permission Caching:** Cache effective permissions for active users
- **Indexed Queries:** Optimized database indexes for permission lookups
- **Async Processing:** Background permission recalculation
- **Session Storage:** Store calculated permissions in user session

### Security Requirements
- **Principle of Least Privilege:** Default deny, explicit grant model
- **Separation of Duties:** Incompatible role combinations prevented
- **Audit Trail:** All permission changes and access attempts logged
- **Regular Review:** Automated role assignment reviews and approvals

### Scalability Considerations
- **Horizontal Scaling:** Support for multiple sites and departments
- **Role Templates:** Predefined role templates for quick setup
- **Bulk Operations:** Efficient bulk user and permission management
- **API Integration:** REST APIs for external system integration

---

## Benefits for Manufacturing Operations

### Operational Flexibility
✅ **Multi-Role Support** - Users can have any combination of roles needed
✅ **Department Autonomy** - Department owners control their domain completely  
✅ **Cross-Department Collaboration** - Structured access for collaboration needs
✅ **Temporary Assignments** - Time-limited access for specific projects
✅ **Context Awareness** - Permissions change based on user's active role

### Security & Compliance
✅ **Principle of Least Privilege** - Minimum necessary access granted
✅ **Complete Audit Trail** - All access decisions logged for compliance
✅ **Document-Level Security** - Sensitive documents get additional protection
✅ **Regulatory Compliance** - ISO 9001/IATF 16949 access control requirements met
✅ **Tamper Prevention** - Access control changes require approval and logging

### Management Control
✅ **Centralized Permission Management** - Define roles and permissions centrally
✅ **Delegated Administration** - Department owners manage their staff
✅ **Real-time Monitoring** - Dashboard views of access patterns and violations
✅ **Automated Compliance** - Permission reviews and role certifications
✅ **Risk Management** - High-risk permissions flagged and monitored

---

*Document Version: 1.0*  
*Created: 2025-09-12*  
*Classification: Technical Requirements*  
*Retention: Permanent (Reference Document)*