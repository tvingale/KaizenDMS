# KaizenDMS - Document Management System

**ISO 9001:2015 & IATF 16949:2016 Compliant Document Management System**

KaizenDMS is a PHP-based Document Management System designed for manufacturing environments, providing comprehensive document lifecycle management with quality standards compliance.

## 🌟 Key Features

- **ISO 9001:2015 & IATF 16949:2016 Compliance** - Built for automotive quality standards
- **Document Lifecycle Management** - Draft → Review → Approved → Effective → Obsolete workflow
- **Role-Based Access Control** - Integration with KaizenAuth SSO
- **QR Code Generation** - Shop floor document access via QR codes
- **WhatsApp Integration** - Automated notifications and reminders
- **Multi-Site Support** - Manage documents across multiple locations
- **Version Control** - Complete document revision tracking

## 🏗️ Architecture

- **Backend**: PHP 7.4+ with PDO MySQL
- **Database**: MySQL with `dms_` prefixed tables
- **Authentication**: KaizenAuth JWT-based SSO
- **Frontend**: Responsive web interface with Segoe UI typography
- **API**: RESTful endpoints for integration

## 📋 System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- KaizenAuth for authentication

## 🚀 Quick Start

### 1. Database Setup
```bash
# Access the web-based database checker
https://your-domain.com/web_db_check.php

# Deploy master tables via web interface
https://your-domain.com/web_deploy_tables.php
```

### 2. Configuration
```bash
# Configure environment variables
cp .env.example .env
# Edit .env with your database and auth settings
```

### 3. Verify Installation
- Check database status: `/web_db_check.php`
- All 10 DMS master tables should be present
- KaizenAuth integration should be working

## 📁 Project Structure

```
KaizenDMS/
├── src/                           # Application source files
│   ├── includes/                  # Core classes and utilities
│   │   ├── database.php          # Database connection
│   │   ├── kaizen_sso.php        # Authentication
│   │   └── AccessControl.php     # Authorization
│   ├── admin/                     # Admin panel
│   ├── api/                       # REST API endpoints
│   └── *.php                     # Main application pages
├── integration/masterdata/        # Archived master data files
├── web_db_check.php              # Database status checker
├── web_deploy_tables.php         # Table deployment tool
├── simple_db_check.php           # Simple diagnostic tool
└── DEPLOYMENT_GUIDE.md           # Server deployment guide
```

## 🗄️ Database Schema

KaizenDMS creates 10 master tables with `dms_` prefix:

| Table | Purpose | Records |
|-------|---------|---------|
| `dms_sites` | Site/location management | Sample locations |
| `dms_departments` | Department structure | QA, MFG, ENG, etc. |
| `dms_customers` | Customer data | Ready for data |
| `dms_suppliers` | Supplier qualification | Ready for data |
| `dms_process_areas` | Process classification | WELD, STITCH, ASSY, etc. |
| `dms_document_types` | Document types | POL, SOP, WI, FORM, etc. |
| `dms_languages` | Multi-language support | en, mr, hi, gu |
| `dms_review_cycles` | Review scheduling | ANNUAL, BIENNIAL, etc. |
| `dms_notification_templates` | Message templates | WhatsApp/Email templates |
| `dms_notification_channels` | Communication channels | EMAIL_SMTP, WHATSAPP |

## 🔧 Development

### File Change Reporting
When making changes, always report using relative paths:

```
Files Updated:
- src/includes/AccessControl.php - Added new permission checks
- src/document_list.php - Updated search functionality
```

### Security Best Practices
- All forms use CSRF protection
- Input validation and sanitization throughout
- Role-based access checks before operations
- Environment-based configuration (no hardcoded credentials)

## 📖 Documentation

- [Implementation Plan](dms_implementation_plan.md) - Comprehensive development plan
- [Requirements Summary](dms_requirements_summary.md) - ISO compliance requirements  
- [Deployment Guide](DEPLOYMENT_GUIDE.md) - Server setup instructions
- [Project Instructions](CLAUDE.md) - Development guidelines for AI assistants

## 🔗 Integration

KaizenDMS integrates with:
- **KaizenAuth** - Single Sign-On authentication
- **WhatsApp Business API** - Document notifications
- **QR Code System** - Shop floor document access

## 📜 License

This project is proprietary software developed for Kaizen manufacturing environments.

## 🏢 About

Developed for ISO 9001:2015 & IATF 16949:2016 compliant bus seat manufacturing operations, providing single source-of-truth for work instructions, forms, and drawings with automated approval workflows and shop-floor delivery systems.

---

**KaizenDMS** - Where document control meets manufacturing excellence.