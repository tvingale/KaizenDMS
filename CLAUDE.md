# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

KaizenDMS is a PHP-based Document Management System that integrates with KaizenAuth for SSO authentication. The system provides document management capabilities with role-based access control, admin panel, and REST API endpoints.

## Development Commands

### No Build Process Required
This is a traditional PHP application with no build step, package management, or compilation required. Files are served directly by the web server.

### Environment Setup
1. Copy `.env` file and configure database/auth settings:
   ```bash
   cp .env.example .env  # if .env.example exists
   # Edit .env with actual values
   ```

2. Ensure web server has PHP 7.4+ with PDO MySQL extension

### Testing
No automated test framework is configured. Testing is manual:
- Test authentication flows with different user roles (user, manager, admin)
- Verify access control restrictions work correctly
- Test CRUD operations for documents
- Validate API endpoints in `api/` directory
- Check admin panel functionality

## Architecture

### Core Components
- **Authentication**: KaizenAuth JWT-based SSO (`src/includes/kaizen_sso.php`)
- **Access Control**: Role-based permissions system (`src/includes/AccessControl.php`) 
- **Database**: MySQL with PDO singleton pattern (`src/includes/database.php`)
- **Configuration**: Environment-based config management (`src/config.php`)

### Directory Structure
```
src/
├── admin/              # Admin panel files  
├── api/               # REST API endpoints
├── includes/          # Core classes and utilities
├── views/             # View templates/components
├── assets/           # Static assets
├── document_*.php    # Document CRUD operations
└── *.php             # Main application pages
```

### Authentication Flow
JWT tokens are stored in cookies by KaizenAuth. The `KaizenSSO` class:
1. Extracts JWT from cookies (`kaizen_refresh`, `kaizen_token`, etc.)
2. Decodes JWT payload containing complete user information
3. No database queries needed for user display data
4. Access control checked via `AccessControl::requireAccess()`

### Database Design
- Role hierarchy: user → manager → admin
- Module-specific access control via `task_user_roles` table
- Document management through `dms_documents` table
- All queries use PDO prepared statements

## Development Patterns

### Page Structure
Each page follows this pattern:
```php
require_once 'config.php';
require_once 'includes/database.php'; 
require_once 'includes/kaizen_sso.php';
require_once 'includes/AccessControl.php';

$accessControl = AccessControl::requireAccess($requiredRole);
```

### Security Implementation
- CSRF tokens for all forms (`$_SESSION['dms_csrf_token']`)
- Input validation and sanitization throughout
- Role-based access checks before operations
- Environment-based configuration (no hardcoded credentials)
- Error logging respects `DEBUG_MODE` setting

### Database Interaction
- Use `getDB()` helper for database connection
- All queries use prepared statements
- Follow PDO exception handling patterns shown in existing files

## Common Operations

### Adding New Pages
1. Copy authentication boilerplate from existing pages
2. Include appropriate header/footer templates from `includes/`
3. Implement CSRF protection for forms
4. Follow existing SQL query patterns

### Database Schema Changes  
- Update schema manually (no migration system)
- Consider existing data when making changes
- Test with different user permission levels

### API Development
REST endpoints are in `api/` directory:
- `documents.php` - Document management operations
- `user_search.php` - User lookup functionality
- Follow existing authentication patterns

## File Change Reporting
Always report file changes using relative paths from project root:

**Files Updated:**
- `src/includes/AccessControl.php` - Description of changes
- `src/document_list.php` - Description of changes