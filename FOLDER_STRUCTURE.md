# KaizenDMS Folder Structure

## 📁 **Organized Project Structure**

```
KaizenDMS/
├── 📋 PROJECT ROOT
│   ├── README.md                    # Project overview and setup
│   ├── CLAUDE.md                    # AI assistant instructions  
│   ├── FOLDER_STRUCTURE.md          # This file - folder organization
│   ├── .env                         # Environment configuration
│   ├── .gitignore                   # Git ignore rules
│   └── LICENSE                      # Project license (if applicable)
│
├── 📖 docs/                         # Documentation
│   ├── requirements/                # Requirements & Specifications
│   │   ├── dms_requirements_summary.md
│   │   ├── dms_detailed_implementation_guide.md
│   │   ├── dms_audit_trail_requirements.md
│   │   └── dms_rbac_requirements.md
│   ├── implementation/              # Implementation Planning
│   │   ├── dms_implementation_plan.md
│   │   ├── phase1_task_list.md
│   │   ├── requirements_to_implementation_mapping.md
│   │   └── dms_self_managed_architecture.md
│   ├── deployment/                  # Deployment Documentation
│   │   └── DEPLOYMENT_GUIDE.md
│   └── archived/                    # Historical/Archived Documents
│       ├── todo.md
│       ├── kaizen-ui-cheat-sheet.html
│       └── ui_ux_guidelines.md
│
├── 💾 src/                          # Application Source Code
│   ├── 🏠 ROOT PAGES (served directly)
│   │   ├── index.php                # Login/landing page
│   │   ├── dashboard.php            # Main dashboard
│   │   ├── sso.php                  # SSO authentication
│   │   ├── logout.php               # Logout handler
│   │   ├── 404.php                  # Not found page
│   │   ├── 500.php                  # Server error page
│   │   └── access_denied.php        # Access denied page
│   │
│   ├── 📄 DOCUMENT MANAGEMENT
│   │   ├── document_create.php      # Document creation form
│   │   ├── document_list.php        # Document listing & search
│   │   ├── document_view.php        # Document viewing interface
│   │   ├── document_edit.php        # Document editing interface
│   │   ├── document_review.php      # Document approval interface
│   │   └── document_history.php     # Document version history
│   │
│   ├── 👤 USER MANAGEMENT
│   │   ├── profile.php              # User profile management
│   │   ├── settings.php             # User settings
│   │   ├── module_users.php         # Module user management
│   │   └── access.php               # Access control interface
│   │
│   ├── 🔧 includes/                 # Core Classes & Utilities
│   │   ├── config.php               # Application configuration
│   │   ├── database.php             # Database connection manager
│   │   ├── kaizen_sso.php           # KaizenAuth SSO integration
│   │   ├── AccessControl.php        # Enhanced RBAC access control (NEW RBAC + Legacy)
│   │   ├── AdditivePermissionManager.php # Advanced RBAC permission engine
│   │   ├── CSRFProtection.php       # CSRF protection utilities
│   │   ├── UserDisplayHelper.php    # User display utilities
│   │   ├── KaizenAuthAPI.php        # KaizenAuth API client
│   │   ├── header.php               # Common header template
│   │   └── footer.php               # Common footer template
│   │
│   ├── 🔌 api/                      # REST API Endpoints
│   │   ├── documents.php            # Document management API
│   │   ├── user_search.php          # User lookup API
│   │   ├── send-whatsapp.php        # WhatsApp notification API
│   │   └── send-sms.php             # SMS notification API
│   │
│   ├── 👑 admin/                    # Admin Panel
│   │   ├── index.php                # Admin dashboard
│   │   ├── roles_permissions.php    # RBAC management
│   │   ├── categories.php           # Category management
│   │   ├── settings.php             # System settings
│   │   └── export_options.php       # Data export options
│   │
│   ├── 🎨 views/                    # View Templates & Components
│   │   ├── components/              # Reusable UI components
│   │   ├── layouts/                 # Page layouts
│   │   └── partials/                # Partial templates
│   │
│   └── 📦 assets/                   # Static Assets
│       ├── css/                     # Stylesheets
│       ├── js/                      # JavaScript files
│       └── images/                  # Images and icons
│           └── kaizenflowlogo.png   # Project logo
│
├── 🛠️ tools/                        # Development & Deployment Tools
│   ├── database/                    # Database Management Tools
│   │   ├── web_db_check.php         # Web-based database status checker
│   │   ├── web_deploy_tables.php    # Web-based table deployment
│   │   └── simple_db_check.php      # Simple diagnostic tool
│   ├── access_control_pdca_test.php # RBAC integration PDCA validation
│   ├── comprehensive_access_control_pdca.php # Full RBAC testing suite
│   ├── fix_role_names.php           # Fix missing role_name values
│   └── resolve_role_conflicts.php   # Resolve role ID conflicts (1-4 vs 6-14)
│
├── 🗄️ database/                     # Database Schema & Data
│   ├── migrations/                  # Database migration scripts
│   │   ├── 001_create_master_tables.sql
│   │   ├── 002_create_rbac_tables.sql
│   │   ├── 003_create_audit_tables.sql
│   │   └── 004_create_document_tables.sql
│   └── seeds/                       # Sample data for development
│       ├── master_data_seeds.sql
│       ├── role_permissions_seeds.sql
│       └── sample_documents_seeds.sql
│
├── 🧪 tests/                        # Testing
│   ├── unit/                        # Unit tests
│   ├── integration/                 # Integration tests
│   └── fixtures/                    # Test fixtures and data
│
├── 📚 integration/                  # External Integration Research
│   └── masterdata/                  # Archived master data integration research
│       ├── dms_master_data_request.json
│       ├── dms_master_tables_schema.sql
│       ├── kaizen_masters_integration_summary.md
│       ├── master_data_validation_correction.md
│       └── kaizen-masters-original/  # Original KaizenMasters documentation
│
└── 🔄 backups/                      # System Backups (RBAC Integration)
    └── rbac_integration_20250916_100746/  # RBAC implementation backup
        ├── AccessControl.php.backup     # Original AccessControl before RBAC
        ├── roles_permissions.php.backup # Original admin roles page
        └── module_users.php.backup      # Original module users page
```

## 🎯 **Folder Purpose & Usage**

### **📖 docs/ - Documentation Hub**
**Purpose:** All project documentation organized by type
- **requirements/** - Requirements, specifications, and compliance documents
- **implementation/** - Implementation plans, task lists, and architecture
- **deployment/** - Server deployment and setup guides
- **archived/** - Historical documents and outdated files

### **💾 src/ - Application Source Code**  
**Purpose:** All PHP application code organized by functionality
- **Root level** - Main application pages served directly by web server
- **includes/** - Core classes, utilities, and common templates
- **api/** - REST API endpoints for external integration
- **admin/** - Administrative interfaces for system management
- **views/** - Templates and UI components (future use)
- **assets/** - Static files (CSS, JS, images)

### **🛠️ tools/ - Development Tools**
**Purpose:** Utilities for development, testing, and deployment
- **database/** - Database setup, verification, and management tools
- **RBAC Tools:** - Advanced permission system management
  - `access_control_pdca_test.php` - PDCA testing for RBAC integration
  - `comprehensive_access_control_pdca.php` - Full RBAC test suite
  - `fix_role_names.php` - Database role name correction
  - `resolve_role_conflicts.php` - Legacy/new role conflict resolution
- Web-based interfaces for server environment setup

### **🗄️ database/ - Database Schema Management**
**Purpose:** Database structure and sample data management
- **migrations/** - SQL scripts for creating database structure
- **seeds/** - Sample data for development and testing

### **🧪 tests/ - Testing Framework**
**Purpose:** Test suites for quality assurance
- **unit/** - Individual component testing
- **integration/** - System integration testing
- **fixtures/** - Test data and mock objects

## 🌐 **Web Server Mapping**

### **Production Server Structure**
```
Server Root (https://doms.kaizenapps.co.in/)
├── index.php              # Maps to src/index.php
├── dashboard.php          # Maps to src/dashboard.php  
├── document_create.php    # Maps to src/document_create.php
├── includes/              # Maps to src/includes/
├── api/                   # Maps to src/api/
├── admin/                 # Maps to src/admin/
├── assets/                # Maps to src/assets/
└── tools/                 # Maps to tools/ (database tools)
```

### **Development vs Production**
- **Development:** Files in `src/` folder for organization
- **Production:** `src/` contents deployed to server root
- **Tools:** Database tools accessible via web for server management

## 📋 **File Organization Principles**

### **Naming Conventions**
- **Snake_case** for PHP files: `document_create.php`
- **PascalCase** for classes: `AccessControl.php`
- **lowercase** for directories: `includes/`, `admin/`
- **UPPERCASE** for documentation: `README.md`, `LICENSE`

### **Logical Grouping**
- **By Function:** Documents, users, admin separated
- **By Type:** API endpoints grouped together
- **By Purpose:** Tools vs application code vs documentation

### **Clean Separation**
- **Source Code:** Only in `src/` directory
- **Documentation:** Only in `docs/` directory  
- **Tools:** Only in `tools/` directory
- **Configuration:** Environment-specific files in root

## 🚀 **Development Workflow**

### **Adding New Features**
1. **Requirements:** Document in `docs/requirements/`
2. **Implementation:** Code in appropriate `src/` subdirectory
3. **Database:** Migrations in `database/migrations/`
4. **Testing:** Tests in `tests/`
5. **Documentation:** Update relevant docs

### **File Creation Guidelines**
- **New Pages:** Add to `src/` root level
- **New Classes:** Add to `src/includes/`
- **New APIs:** Add to `src/api/`
- **New Admin Features:** Add to `src/admin/`

---

**Benefits of This Structure:**
✅ **Clear Organization** - Easy to find any file or functionality  
✅ **Scalable Architecture** - Room for growth without restructuring  
✅ **Development Friendly** - Logical grouping for efficient development  
✅ **Production Ready** - Clean deployment to server root  
✅ **Maintenance Ease** - Separated concerns for easy maintenance  
✅ **Team Collaboration** - Clear conventions for multiple developers