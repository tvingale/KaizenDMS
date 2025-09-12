<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Document Management System - KaizenFlow</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Kaizen Design System -->
    <style>
        :root {
            /* Kaizen Design Tokens - Official Specification */
            --brand-primary: #C53A3A;
            --brand-primary-dark: #A72E2E;
            --neutral-100: #F6F7F8;
            --neutral-300: #E6E9EC;
            --neutral-600: #6B7280;
            --text-default: #111827;
            --white: #FFFFFF;

            --success: #16A34A;
            --warning: #F59E0B;
            --error: #DC2626;
            --info: #2563EB;
            --pending: #9CA3AF;

            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --shadow-soft: 0 6px 18px rgba(16,24,40,0.06);
        }
        
        /* Typography - Kaizen Specification */
        body {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-default);
            background-color: var(--neutral-100);
        }
        
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 600;
            color: var(--text-default);
            margin-bottom: 1rem;
        }
        
        h1, .h1 { font-size: 28px; font-weight: 700; }
        h2, .h2 { font-size: 22px; font-weight: 600; }
        h3, .h3 { font-size: 18px; font-weight: 600; }
        
        .font-weight-bold {
            font-weight: 600;
        }
        
        /* Kaizen Branding */
        .navbar-brand {
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-logo {
            height: 28px;
            width: auto;
        }
        
        .navbar-brand:hover {
            text-decoration: none;
            opacity: 0.9;
        }
        
        /* Hybrid Navigation - Kaizen Pattern */
        .navbar {
            background: var(--white) !important;
            box-shadow: var(--shadow-soft);
            border-bottom: 1px solid var(--neutral-300);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .navbar-brand {
            color: var(--brand-primary) !important;
        }
        
        .navbar-nav .nav-link {
            color: var(--neutral-600) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: var(--radius-md);
            padding: 8px 12px;
            margin: 0 2px;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link:focus {
            color: var(--brand-primary) !important;
            background-color: var(--neutral-300);
        }
        
        .navbar-nav .nav-item.active .nav-link {
            background-color: var(--neutral-300);
            color: var(--brand-primary) !important;
            font-weight: 600;
        }
        
        /* Buttons - Kaizen Design System */
        .btn {
            border-radius: var(--radius-lg);
            font-weight: 600;
            padding: 10px 16px;
            transition: all 0.3s ease;
            border: 0;
            cursor: pointer;
            box-shadow: var(--shadow-soft);
            font-family: inherit;
        }
        
        .btn:focus {
            outline: 3px solid rgba(197, 58, 58, 0.25);
            outline-offset: 2px;
        }
        
        .btn-primary {
            background: var(--brand-primary);
            color: var(--white);
            border: none;
        }
        
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background: var(--brand-primary-dark);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(197, 58, 58, 0.3);
        }
        
        .btn-secondary {
            background: var(--white);
            color: var(--text-default);
            border: 1px solid var(--neutral-300);
            box-shadow: var(--shadow-soft);
        }
        
        .btn-secondary:hover,
        .btn-secondary:focus {
            background: var(--neutral-100);
            color: var(--text-default);
            border-color: var(--neutral-300);
        }
        
        .btn-tertiary {
            background: transparent;
            color: var(--brand-primary);
            box-shadow: none;
            padding: 8px 10px;
            border: none;
        }
        
        .btn-tertiary:hover {
            color: var(--brand-primary-dark);
            background: transparent;
        }
        
        .btn-success {
            background: var(--success);
            color: var(--white);
            border: none;
        }
        
        .btn-warning {
            background: var(--warning);
            color: var(--text-default);
            border: none;
        }
        
        .btn-danger {
            background: var(--error);
            color: var(--white);
            border: none;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Cards - Kaizen Design System */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            background: var(--white);
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16,24,40,0.12);
        }
        
        .card-header {
            background: var(--white);
            color: var(--text-default);
            border-radius: 16px 16px 0 0 !important;
            border-bottom: 1px solid var(--neutral-300);
            font-weight: 600;
            padding: 16px 20px;
        }
        
        .card-body {
            padding: 16px 20px;
        }
        
        /* Status Badges - Kaizen Specification */
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            color: var(--white);
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
        }
        
        .badge-success, .bg-success {
            background: var(--success);
            color: var(--white);
        }
        
        .badge-warning, .bg-warning {
            background: var(--warning);
            color: var(--text-default);
        }
        
        .badge-danger, .badge-error, .bg-error {
            background: var(--error);
            color: var(--white);
        }
        
        .badge-info, .bg-info {
            background: var(--info);
            color: var(--white);
        }
        
        .badge-pending, .bg-pending {
            background: var(--pending);
            color: var(--white);
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 8px;
            border-left: 4px solid;
            font-weight: 500;
        }
        
        .alert-success {
            border-left-color: #28a745;
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            border-left-color: #dc3545;
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
            color: #856404;
        }
        
        .alert-info {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        /* Tables */
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--kaizen-gray-light);
            font-weight: 600;
            color: var(--kaizen-gray-dark);
            border-color: var(--kaizen-red-light);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(217,83,79,0.05);
        }
        
        /* Forms - Kaizen Design System */
        .form-control, .input, .select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--neutral-300);
            background: var(--white);
            color: var(--text-default);
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .input:focus, .select:focus {
            border-color: var(--info);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
            outline: none;
        }
        
        .form-control.is-invalid, .input.invalid {
            border-color: var(--error);
        }
        
        .form-control.is-invalid:focus, .input.invalid:focus {
            border-color: var(--error);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.25);
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-default);
        }
        
        .form-text, .hint {
            font-size: 12px;
            color: var(--neutral-600);
            margin-top: 4px;
        }
        
        .invalid-feedback, .error {
            color: var(--error);
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }
        
        /* Progress Bars */
        .progress {
            border-radius: 6px;
            background-color: var(--kaizen-gray-light);
        }
        
        .progress-bar {
            background-color: var(--kaizen-red);
        }
        
        /* Breadcrumbs */
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: var(--kaizen-gray);
        }
        
        /* Custom Utilities */
        .text-kaizen-red { color: var(--kaizen-red) !important; }
        .bg-kaizen-red { background-color: var(--kaizen-red) !important; }
        .border-kaizen-red { border-color: var(--kaizen-red) !important; }
        
        /* Loading States */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Loading States - Kaizen Pattern */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive Design - Kaizen Mobile Adaptation */
        @media (max-width: 900px) {
            .container-fluid {
                padding-left: 16px;
                padding-right: 16px;
            }
            
            .card-body {
                padding: 12px 16px;
            }
            
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-logo {
                height: 24px;
            }
        }
        
        @media (max-width: 600px) {
            .btn {
                font-size: 14px;
                padding: 8px 12px;
            }
            
            .card {
                border-radius: 12px;
                margin-bottom: 1rem;
            }
            
            .navbar-nav .nav-link {
                padding: 6px 10px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?= isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>index.php">
                    <img src="<?= isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>assets/images/kaizenflowlogo.png" alt="KaizenFlow" class="navbar-logo">
                    Document Management System
                </a>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'document_list.php' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>document_list.php">
                                <i class="fas fa-list"></i> documents
                            </a>
                        </li>
                        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'document_create.php' ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>document_create.php">
                                <i class="fas fa-plus"></i> New Document
                            </a>
                        </li>
                    </ul>

                    <!-- Management Menu (Admin/Manager Only) -->
                    <?php 
                    // Check if user has admin or manager role (like KaizenTasks pattern)
                    if (isset($accessControl) && ($accessControl->hasRole('admin') || $accessControl->hasRole('manager'))): 
                    ?>
                        <ul class="navbar-nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="javascript:void(0)" data-toggle="dropdown">
                                    <i class="fas fa-users-cog"></i> Management
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>module_users.php">
                                        <i class="fas fa-users"></i> User Management
                                    </a>
                                    
                                    <div class="dropdown-divider"></div>
                                    <h6 class="dropdown-header">Administration</h6>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>admin/">
                                        <i class="fas fa-tools"></i> Admin Panel
                                    </a>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>admin/categories.php">
                                        <i class="fas fa-tags"></i> Categories
                                    </a>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>admin/roles_permissions.php">
                                        <i class="fas fa-shield-alt"></i> Roles & Permissions
                                    </a>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>admin/export_options.php">
                                        <i class="fas fa-download"></i> Export Options
                                    </a>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>admin/settings.php">
                                        <i class="fas fa-cog"></i> Admin Settings
                                    </a>
                                </div>
                            </li>
                        </ul>
                    <?php endif; ?>

                    <!-- User Menu -->
                    <?php if (isset($user) && $user): ?>
                        <ul class="navbar-nav">
                            <!-- Notifications placeholder -->
                            <li class="nav-item">
                                <a class="nav-link" href="javascript:void(0)" title="Notifications">
                                    <i class="fas fa-bell"></i>
                                    <span class="sr-only">Notifications</span>
                                </a>
                            </li>
                            
                            <!-- User dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="javascript:void(0)" data-toggle="dropdown" title="<?= htmlspecialchars($user['email'] ?? $user['username'] ?? '') ?>">
                                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['name'] ?? $user['username'] ?? 'User') ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <h6 class="dropdown-header">
                                        <?= htmlspecialchars($user['name'] ?? $user['username'] ?? 'User') ?>
                                    </h6>
                                    <small class="dropdown-item text-muted" style="padding-top: 2px;">
                                        <?= htmlspecialchars($user['email'] ?? '') ?>
                                    </small>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>profile.php">
                                        <i class="fas fa-user"></i> My Profile
                                    </a>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>settings.php">
                                        <i class="fas fa-cog"></i> Settings
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?= KAIZEN_AUTH_URL ?>/apps.php">
                                        <i class="fas fa-th"></i> All Apps
                                    </a>
                                    <a class="dropdown-item" href="<?= strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : '' ?>logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid" style="padding-top: 20px;">