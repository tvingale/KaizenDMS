<?php
/**
 * Unified Access Control System
 * Single flow: KaizenAuth → Module Access Check → RBAC
 * COPIED EXACTLY FROM KaizenTasks
 */

class AccessControl {
    private $db;
    private $user;
    private $userAccess = null;
    
    public function __construct($db, $user) {
        $this->db = $db;
        $this->user = $user;
    }
    
    /**
     * Main access control method - implements single flow
     * Returns: true if access granted, false if denied
     */
    public function checkAccess($requiredRole = null) {
        // Step 1: Get user's access information
        $this->loadUserAccess();
        
        // Step 2: Check if user has module access
        if (!$this->hasModuleAccess()) {
            return false;
        }
        
        // Step 3: Check role-based permissions if required
        if ($requiredRole && !$this->hasRole($requiredRole)) {
            return false;
        }
        
        // Step 4: Update last access time
        $this->updateLastAccess();
        
        return true;
    }
    
    /**
     * Check if user has access to the module at all
     */
    public function hasModuleAccess() {
        $this->loadUserAccess(); // Ensure data is loaded
        return $this->userAccess && $this->userAccess['status'] === 'active';
    }
    
    /**
     * Check if user has specific role or higher
     */
    public function hasRole($requiredRole) {
        $this->loadUserAccess(); // Ensure data is loaded
        if (!$this->userAccess) return false;
        
        $roleHierarchy = [
            'user' => 1,
            'manager' => 2, 
            'admin' => 3
        ];
        
        $userLevel = $roleHierarchy[$this->userAccess['role_name']] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 999;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Get user's role information
     */
    public function getUserRole() {
        $this->loadUserAccess(); // Ensure data is loaded
        return $this->userAccess ? $this->userAccess['role_name'] : null;
    }
    
    /**
     * Check specific permissions by permission name
     */
    public function hasPermission($permissionName) {
        $this->loadUserAccess();
        if (!$this->userAccess) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM dms_user_roles ur
                JOIN dms_role_permissions rp ON ur.role_id = rp.role_id
                JOIN dms_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ? AND ur.status = 'active' AND p.name = ?
            ");
            $stmt->execute([$this->user['id'], $permissionName]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("AccessControl: Failed to check permission '$permissionName': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check specific permissions (legacy methods)
     */
    public function canManageUsers() {
        return $this->hasPermission('users.manage');
    }
    
    public function canViewReports() {
        return $this->hasPermission('reports.view');
    }
    
    public function canManageEscalations() {
        return $this->hasRole('manager');
    }
    
    /**
     * Entity visibility permissions
     */
    public function canViewAlldocuments() {
        return $this->hasPermission('documents.view.all');
    }
    
    public function canViewAssigneddocuments() {
        return $this->hasPermission('documents.view.assigned');
    }
    
    public function canViewCreateddocuments() {
        return $this->hasPermission('documents.view.created');
    }
    
    /**
     * Redirect to access denied page
     */
    public function redirectToAccessDenied($reason = 'no_access') {
        header("Location: access_denied.php?reason=" . urlencode($reason));
        exit;
    }
    
    /**
     * Load user access information from database
     */
    private function loadUserAccess() {
        if ($this->userAccess !== null) {
            return; // Already loaded
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT ur.*, r.name as role_name
                FROM dms_user_roles ur
                JOIN dms_roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND ur.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$this->user['id']]);
            $this->userAccess = $stmt->fetch(PDO::FETCH_ASSOC);
            
            
        } catch (Exception $e) {
            error_log("AccessControl: Failed to load user access: " . $e->getMessage());
            $this->userAccess = false;
        }
    }
    
    /**
     * Update user's last access time
     */
    private function updateLastAccess() {
        if (!$this->userAccess) return;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE dms_user_roles 
                SET last_access = NOW() 
                WHERE user_id = ?
            ");
            $stmt->execute([$this->user['id']]);
        } catch (Exception $e) {
            // Don't fail the request if we can't update last access
            error_log("AccessControl: Failed to update last access: " . $e->getMessage());
        }
    }
    
    /**
     * Helper method to ensure access control is applied to a page
     * Call this at the top of protected pages
     */
    public static function requireAccess($requiredRole = null) {
        global $sso, $db;
        
        // Ensure SSO is initialized
        if (!$sso || !$sso->isAuthenticated()) {
            header('Location: sso.php');
            exit;
        }
        
        $user = $sso->getUserInfo();
        $accessControl = new AccessControl($db, $user);
        
        // Check access
        if (!$accessControl->checkAccess($requiredRole)) {
            if (!$accessControl->hasModuleAccess()) {
                $accessControl->redirectToAccessDenied('no_module_access');
            } else {
                $accessControl->redirectToAccessDenied('insufficient_role');
            }
        }
        
        return $accessControl;
    }
    
    /**
     * Get all users with module access (for admin interfaces)
     */
    public function getAllModuleUsers() {
        if (!$this->hasRole('admin')) {
            return [];
        }
        
        try {
            $stmt = $this->db->query("
                SELECT ur.user_id, ur.status, ur.granted_by, ur.granted_at, ur.last_access, ur.notes,
                       ur.role_id, r.name as role_name
                FROM dms_user_roles ur
                JOIN dms_roles r ON ur.role_id = r.id
                ORDER BY ur.granted_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AccessControl: Failed to get module users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Grant access to a user (admin only)
     */
    public function grantUserAccess($userId, $roleId, $notes = '') {
        if (!$this->hasRole('admin')) {
            throw new Exception('Admin access required');
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO dms_user_roles (user_id, role_id, status, granted_by, granted_at, notes)
                VALUES (?, ?, 'active', ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE
                    role_id = VALUES(role_id),
                    status = 'active',
                    granted_by = VALUES(granted_by),
                    granted_at = NOW(),
                    notes = VALUES(notes)
            ");
            
            return $stmt->execute([$userId, $roleId, $this->user['id'], $notes]);
        } catch (Exception $e) {
            error_log("AccessControl: Failed to grant access: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Revoke user access (admin only)
     */
    public function revokeUserAccess($userId, $notes = '') {
        if (!$this->hasRole('admin')) {
            throw new Exception('Admin access required');
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE dms_user_roles 
                SET status = 'inactive', granted_by = ?, granted_at = NOW(), notes = ?
                WHERE user_id = ?
            ");
            
            return $stmt->execute([$this->user['id'], $notes, $userId]);
        } catch (Exception $e) {
            error_log("AccessControl: Failed to revoke access: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Restore user access (admin only) - reactivate existing record
     */
    public function restoreUserAccess($userId, $notes = '') {
        if (!$this->hasRole('admin')) {
            throw new Exception('Admin access required');
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE dms_user_roles 
                SET status = 'active', granted_by = ?, granted_at = NOW(), notes = ?
                WHERE user_id = ? AND status = 'inactive'
            ");
            
            return $stmt->execute([$this->user['id'], $notes, $userId]);
        } catch (Exception $e) {
            error_log("AccessControl: Failed to restore access: " . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Helper function for backward compatibility
 */
function requireAdminAccess() {
    return AccessControl::requireAccess('admin');
}

function requireManagerAccess() {
    return AccessControl::requireAccess('manager');
}
?>