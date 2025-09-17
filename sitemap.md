# KaizenDMS Site Map

**Complete navigation structure for the KaizenDMS application based on Phase 1 implementation**

---

## 🏠 **Main Site Structure**

```
KaizenDMS Application
├── 🔐 Authentication Flow
│   ├── index.php                    # Landing/Login page
│   ├── sso.php                     # KaizenAuth SSO handler
│   └── logout.php                  # Logout & session cleanup
│
├── 📊 Dashboard & Main Navigation
│   ├── dashboard.php               # Main dashboard with KPIs
│   ├── profile.php                 # User profile management
│   ├── settings.php                # User settings & preferences
│   └── access_denied.php           # Access denied error page
│
├── 📄 Document Management System
│   ├── 📝 Document Operations
│   │   ├── document_create.php     # Document creation & upload (Task 2.2)
│   │   ├── document_list.php       # Document listing & search (Task 2.3)
│   │   ├── document_view.php       # Document viewing with QR codes (Task 4.1)
│   │   ├── document_edit.php       # Document editing interface
│   │   └── document_history.php    # Version history & audit trail
│   │
│   ├── 🔄 Workflow Management
│   │   ├── document_review.php     # Document approval interface (Task 3.3)
│   │   ├── approval_queue.php      # Pending approvals dashboard
│   │   └── workflow_status.php     # Document lifecycle status (Task 3.1)
│   │
│   └── 📋 Training & Compliance
│       ├── training_assignments.php # Read & Understood system (Task 5.1)
│       ├── training_progress.php   # Training completion tracking
│       └── periodic_reviews.php    # Periodic review management (Task 5.3)
│
├── 👑 Administration Panel
│   ├── admin/index.php             # Admin dashboard
│   ├── admin/roles_permissions.php # RBAC management (Task 1.1)
│   ├── admin/module_users.php      # User access management
│   ├── admin/categories.php        # Document categories & metadata
│   ├── admin/settings.php          # System settings & configuration
│   ├── admin/audit_reports.php     # Audit trail reports (Task 1.2)
│   ├── admin/retention_management.php # Retention rules (Task 5.2)
│   └── admin/export_options.php    # Data export & backup
│
├── 📈 Reports & Analytics
│   ├── reports/dashboards.php      # Management dashboards (Task 6.1)
│   ├── reports/kpi_metrics.php     # Key Performance Indicators
│   ├── reports/training_reports.php # Training completion metrics
│   ├── reports/approval_metrics.php # Document approval tracking
│   └── reports/access_audit.php    # Access control audit (Task 4.3)
│
├── 🔌 API Endpoints
│   ├── api/documents.php           # Document management REST API
│   ├── api/user_search.php         # User lookup for assignments
│   ├── api/notifications.php       # Notification management
│   ├── api/send-whatsapp.php       # WhatsApp integration (Task 6.2)
│   └── api/send-sms.php            # SMS notification API
│
├── 🛠️ Tools & Utilities
│   ├── tools/database/
│   │   ├── web_db_check.php        # Database status checker
│   │   ├── web_deploy_tables.php   # Database deployment tool
│   │   └── simple_db_check.php     # Simple diagnostic tool
│   ├── tools/access_control_pdca_test.php # RBAC integration testing
│   ├── tools/fix_role_names.php    # Role management utilities
│   └── tools/resolve_role_conflicts.php # Role conflict resolution
│
└── ❌ Error Pages
    ├── 404.php                     # Page not found
    ├── 500.php                     # Server error
    └── access_denied.php           # Access denied with reasons
```

---

## 🎯 **Feature-Based Navigation Map**

### **📋 Phase 1 Core Features (Week 1-12)**

#### **🔐 Week 1-2: RBAC & Security Foundation**
- **Primary Pages:**
  - `admin/roles_permissions.php` - Role-Based Access Control management (Task 1.1)
  - `admin/audit_reports.php` - Audit trail viewing and integrity checks (Task 1.2)
  - `access.php` - Enhanced access control with multi-role support (Task 1.3)

#### **📄 Week 3-4: Document Management Core**
- **Primary Pages:**
  - `document_create.php` - Document upload with metadata validation (Task 2.1, 2.2)
  - `document_list.php` - Advanced search and filtering system (Task 2.3)
  - `admin/categories.php` - Document hierarchy and numbering management

#### **🔄 Week 5-6: Document Lifecycle & Workflow**
- **Primary Pages:**
  - `workflow_status.php` - Document lifecycle state management (Task 3.1)
  - `approval_queue.php` - Approval workflow engine with notifications (Task 3.2)
  - `document_review.php` - Review interface with e-signature support (Task 3.3)

#### **🛡️ Week 7-8: Document Access & Security**
- **Primary Pages:**
  - `document_view.php` - Secure viewing with watermarks and QR codes (Task 4.1)
  - `controlled_pdf.php` - Controlled PDF generation system (Task 4.2)
  - `reports/access_audit.php` - Document access logging and monitoring (Task 4.3)

#### **📚 Week 9-10: Training & Compliance Features**
- **Primary Pages:**
  - `training_assignments.php` - Simple Read & Understood system (Task 5.1)
  - `admin/retention_management.php` - Document retention rule management (Task 5.2)
  - `periodic_reviews.php` - Automated review scheduling system (Task 5.3)

#### **📊 Week 11-12: Dashboard, Reports & Integration**
- **Primary Pages:**
  - `reports/dashboards.php` - Management KPI dashboards (Task 6.1)
  - `api/send-whatsapp.php` - WhatsApp Business API integration (Task 6.2)
  - `settings.php` - Multi-language support interface (Task 6.3)
  - `admin/system_testing.php` - System testing and documentation (Task 6.4)

---

## 🔗 **User Journey Navigation**

### **👤 Standard User Journey**
```
1. Login (sso.php) → 
2. Dashboard (dashboard.php) → 
3. Document List (document_list.php) → 
4. Document View (document_view.php) → 
5. Training Assignment (training_assignments.php)
```

### **📝 Document Creator Journey**
```
1. Login → 
2. Dashboard → 
3. Create Document (document_create.php) → 
4. Monitor Status (workflow_status.php) → 
5. Track Approval (approval_queue.php)
```

### **✅ Document Approver Journey**
```
1. Login → 
2. Dashboard → 
3. Review Queue (document_review.php) → 
4. Document Review → 
5. Approval Decision
```

### **👑 Administrator Journey**
```
1. Login → 
2. Admin Dashboard (admin/index.php) → 
3. RBAC Management (admin/roles_permissions.php) → 
4. User Management (admin/module_users.php) → 
5. System Reports (reports/dashboards.php)
```

---

## 📱 **Mobile & External Access**

### **QR Code Access Points**
- Document QR codes generated in `document_view.php` (Task 4.1)
- Shop floor document access via mobile scanning
- Controlled copy tracking through QR integration

### **API Integration Points**
- WhatsApp Business API for workflow notifications (Task 6.2)
- SMS notifications for critical approvals
- REST API for external system integration

---

## 🔍 **Search & Discovery**

### **Document Search Hierarchy**
```
document_list.php (Main Search)
├── Quick Search (header search bar)
├── Advanced Filters
│   ├── By Document Type
│   ├── By Department
│   ├── By Status
│   ├── By Date Range
│   └── By Assigned Users
├── Saved Searches
└── Recently Viewed Documents
```

---

## 🛡️ **Security & Access Control**

### **Permission-Based Page Access**
- **Public:** `index.php`, `sso.php`, `404.php`, `500.php`
- **Authenticated Users:** `dashboard.php`, `document_list.php`, `document_view.php`
- **Document Creators:** `document_create.php`, `document_edit.php`
- **Document Approvers:** `document_review.php`, `approval_queue.php`
- **Administrators:** `admin/*` pages, `reports/*` pages
- **System Administrators:** `tools/*` utilities

---

## 📊 **Reporting Structure**

### **Dashboard Hierarchy**
```
dashboard.php (Main Dashboard)
├── User-specific KPIs
├── Document Status Summary
├── Training Progress
├── Approval Queue Status
└── Recent Activity Feed

reports/dashboards.php (Management Dashboards - Task 6.1)
├── Training Completion Metrics
├── Document Approval Time Tracking
├── System Usage Statistics
├── Compliance Status Reports
└── Role-specific Dashboard Views
```

---

## 🔄 **Workflow Integration Points**

### **Document Lifecycle Integration**
- **Creation:** `document_create.php` → Database → Workflow Engine
- **Review:** `document_review.php` → Approval Engine → Notification System
- **Publication:** `document_view.php` → QR Generation → Training Assignment
- **Retention:** `admin/retention_management.php` → Automated Lifecycle Management

---

**Navigation Note:** All pages implement KaizenAuth SSO integration and RBAC-based access control with comprehensive audit logging as specified in Phase 1 requirements.