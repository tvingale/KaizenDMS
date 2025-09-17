<?php
/**
 * Additive Permission Manager
 * 
 * Implements union permission model where users get the sum of all assigned role permissions.
 * Handles hierarchical inheritance, scope resolution, and permission caching.
 */

class AdditivePermissionManager {
    private $db;
    private $cache_duration = 3600; // 1 hour cache
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Calculate effective permissions for a user (union of all roles)
     * 
     * @param int $user_id
     * @param string $context (optional: 'default', 'auditor', 'safety', 'emergency')
     * @return array Effective permissions with highest scope levels
     */
    public function calculateEffectivePermissions($user_id, $context = 'default') {
        try {
            // Check cache first
            $cached = $this->getCachedPermissions($user_id, $context);
            if ($cached !== null) {
                return $cached;
            }
            
            // Get all active roles for user
            $user_roles = $this->getUserActiveRoles($user_id);
            
            if (empty($user_roles)) {
                return []; // No roles = no permissions
            }
            
            // Get union of all role permissions
            $effective_permissions = $this->getUnionOfRolePermissions($user_roles);
            
            // Apply hierarchical inheritance
            $effective_permissions = $this->applyHierarchicalInheritance($effective_permissions, $user_id);
            
            // Resolve scope conflicts (most permissive wins)
            $effective_permissions = $this->resolvePermissionScopes($effective_permissions);
            
            // Apply context-specific modifications
            $effective_permissions = $this->applyContextModifications($effective_permissions, $context, $user_id);
            
            // Cache the results
            $this->cacheUserPermissions($user_id, $effective_permissions, $context);
            
            return $effective_permissions;
            
        } catch (Exception $e) {
            error_log("Permission calculation failed for user $user_id: " . $e->getMessage());
            return []; // Fail secure - no permissions
        }
    }
    
    /**
     * Get all active roles for a user
     */
    private function getUserActiveRoles($user_id) {
        $stmt = $this->db->prepare("
            SELECT 
                ur.role_name,
                ur.department,
                ur.site_id,
                ur.process_areas,
                ur.effective_from,
                ur.effective_until,
                r.hierarchy_level,
                r.department_scope,
                r.can_be_combined_with
            FROM dms_user_roles ur
            JOIN dms_roles r ON ur.role_name = r.role_name
            WHERE ur.user_id = ? 
                AND ur.status = 'active'
                AND (ur.effective_from IS NULL OR ur.effective_from <= NOW())
                AND (ur.effective_until IS NULL OR ur.effective_until > NOW())
                AND r.status = 'active'
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get union of permissions from all roles (additive model)
     */
    public function getUnionOfRolePermissions($user_roles) {
        if (empty($user_roles)) {
            return [];
        }
        
        $role_names = array_column($user_roles, 'role_name');
        $placeholders = str_repeat('?,', count($role_names) - 1) . '?';
        
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                p.permission_name,
                p.category,
                p.action,
                p.scope_qualifier,
                p.risk_level,
                r.role_name as granted_by_role,
                r.hierarchy_level
            FROM dms_roles r
            JOIN dms_role_permissions rp ON r.id = rp.role_id
            JOIN dms_permissions p ON rp.permission_id = p.id
            WHERE r.role_name IN ($placeholders)
                AND r.status = 'active'
                AND p.status = 'active'
            ORDER BY p.permission_name, 
                FIELD(p.scope_qualifier, 'all', 'cross_department', 'department', 'process_area', 'station', 'assigned_only')
        ");
        $stmt->execute($role_names);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Apply hierarchical inheritance based on organizational structure
     */
    private function applyHierarchicalInheritance($permissions, $user_id) {
        // Get user's organizational position
        $user_hierarchy = $this->getUserHierarchyPosition($user_id);
        
        foreach ($permissions as &$permission) {
            // Apply inheritance rules based on user's position
            $permission['inherited_scope'] = $this->calculateInheritedScope(
                $permission['scope_qualifier'], 
                $user_hierarchy
            );
        }
        
        return $permissions;
    }
    
    /**
     * Resolve scope conflicts - most permissive scope wins
     */
    public function resolvePermissionScopes($permissions) {
        $resolved = [];
        
        // Group by permission name
        $grouped = [];
        foreach ($permissions as $perm) {
            $grouped[$perm['permission_name']][] = $perm;
        }
        
        // For each permission, take the most permissive scope
        foreach ($grouped as $permission_name => $perm_group) {
            $most_permissive = $this->getMostPermissiveScope($perm_group);
            $resolved[] = $most_permissive;
        }
        
        return $resolved;
    }
    
    /**
     * Get most permissive scope from multiple permission entries
     */
    private function getMostPermissiveScope($permission_group) {
        $scope_hierarchy = [
            'all' => 7,
            'cross_department' => 6,
            'department' => 5,
            'process_area' => 4,
            'station' => 3,
            'assigned_only' => 2,
            'none' => 1
        ];
        
        $most_permissive = null;
        $highest_score = 0;
        
        foreach ($permission_group as $perm) {
            $score = $scope_hierarchy[$perm['scope_qualifier']] ?? 1;
            if ($score > $highest_score) {
                $highest_score = $score;
                $most_permissive = $perm;
                $most_permissive['granted_by_roles'] = array_column($permission_group, 'granted_by_role');
            }
        }
        
        return $most_permissive;
    }
    
    /**
     * Apply context-specific permission modifications
     */
    private function applyContextModifications($permissions, $context, $user_id) {
        switch ($context) {
            case 'auditor':
                return $this->applyAuditorContext($permissions, $user_id);
            case 'safety':
                return $this->applySafetyContext($permissions, $user_id);
            case 'emergency':
                return $this->applyEmergencyContext($permissions, $user_id);
            default:
                return $permissions;
        }
    }
    
    /**
     * Check if user has specific permission with optional scope
     */
    public function hasPermission($user_id, $permission_name, $required_scope = null, $context = 'default') {
        $effective_permissions = $this->calculateEffectivePermissions($user_id, $context);
        
        foreach ($effective_permissions as $perm) {
            if ($perm['permission_name'] === $permission_name) {
                if ($required_scope === null) {
                    return true; // Permission exists, scope not checked
                }
                
                // Check if user's scope is sufficient
                return $this->isScopeSufficient($perm['scope_qualifier'], $required_scope);
            }
        }
        
        return false;
    }
    
    /**
     * Check if current scope is sufficient for required scope
     */
    private function isScopeSufficient($current_scope, $required_scope) {
        $scope_hierarchy = [
            'all' => 7,
            'cross_department' => 6, 
            'department' => 5,
            'process_area' => 4,
            'station' => 3,
            'assigned_only' => 2,
            'none' => 1
        ];
        
        $current_level = $scope_hierarchy[$current_scope] ?? 1;
        $required_level = $scope_hierarchy[$required_scope] ?? 1;
        
        return $current_level >= $required_level;
    }
    
    /**
     * Get cached permissions for user
     */
    private function getCachedPermissions($user_id, $context) {
        try {
            $stmt = $this->db->prepare("
                SELECT permission_name, scope_level, granted_by_roles, calculated_at
                FROM dms_user_effective_permissions
                WHERE user_id = ? 
                    AND context = ?
                    AND is_cached = TRUE
                    AND (expires_at IS NULL OR expires_at > NOW())
                    AND calculated_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$user_id, $context, $this->cache_duration]);
            
            $cached = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cached)) {
                return null;
            }
            
            // Convert to expected format
            $permissions = [];
            foreach ($cached as $cache_entry) {
                $permissions[] = [
                    'permission_name' => $cache_entry['permission_name'],
                    'scope_qualifier' => $cache_entry['scope_level'],
                    'granted_by_roles' => json_decode($cache_entry['granted_by_roles'], true),
                    'cached' => true
                ];
            }
            
            return $permissions;
            
        } catch (Exception $e) {
            // Cache read failed, fall back to calculation
            return null;
        }
    }
    
    /**
     * Cache user permissions for performance
     */
    public function cacheUserPermissions($user_id, $permissions, $context = 'default') {
        try {
            // Clear existing cache for this user/context
            $stmt = $this->db->prepare("
                DELETE FROM dms_user_effective_permissions 
                WHERE user_id = ? AND context = ?
            ");
            $stmt->execute([$user_id, $context]);
            
            // Insert new cache entries
            $stmt = $this->db->prepare("
                INSERT INTO dms_user_effective_permissions 
                (user_id, permission_name, scope_level, context, granted_by_roles, permission_source, calculated_at, expires_at)
                VALUES (?, ?, ?, ?, ?, 'role_based', NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))
            ");
            
            foreach ($permissions as $perm) {
                $granted_by_roles = json_encode($perm['granted_by_roles'] ?? []);
                $stmt->execute([
                    $user_id,
                    $perm['permission_name'],
                    $perm['scope_qualifier'],
                    $context,
                    $granted_by_roles,
                    $this->cache_duration
                ]);
            }
            
        } catch (Exception $e) {
            // Cache write failed, not critical
            error_log("Permission cache write failed for user $user_id: " . $e->getMessage());
        }
    }
    
    /**
     * Invalidate permission cache for user (call when roles change)
     */
    public function invalidateUserCache($user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE dms_user_effective_permissions 
                SET cache_invalidated_at = NOW(), is_cached = FALSE
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            
        } catch (Exception $e) {
            error_log("Cache invalidation failed for user $user_id: " . $e->getMessage());
        }
    }
    
    /**
     * Get user's hierarchy position for inheritance calculations
     */
    private function getUserHierarchyPosition($user_id) {
        // This would integrate with user management system
        // For now, return basic structure
        return [
            'site_id' => 1,
            'department_id' => null,
            'process_area_id' => null
        ];
    }
    
    /**
     * Calculate inherited scope based on user's organizational position
     */
    private function calculateInheritedScope($permission_scope, $user_hierarchy) {
        // Apply inheritance logic based on organizational hierarchy
        return $permission_scope;
    }
    
    /**
     * Apply auditor context modifications
     */
    private function applyAuditorContext($permissions, $user_id) {
        // Add read-only access across all departments for auditors
        $audit_permissions = [
            [
                'permission_name' => 'documents.view.all',
                'scope_qualifier' => 'all',
                'granted_by_roles' => ['auditor_context']
            ],
            [
                'permission_name' => 'system.audit.export',
                'scope_qualifier' => 'all', 
                'granted_by_roles' => ['auditor_context']
            ]
        ];
        
        return array_merge($permissions, $audit_permissions);
    }
    
    /**
     * Apply safety context modifications
     */
    private function applySafetyContext($permissions, $user_id) {
        // Add safety-specific permissions
        return $permissions;
    }
    
    /**
     * Apply emergency context modifications
     */
    private function applyEmergencyContext($permissions, $user_id) {
        // Add emergency access permissions
        return $permissions;
    }
}
?>