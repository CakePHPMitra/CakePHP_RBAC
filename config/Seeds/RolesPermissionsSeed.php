<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * RolesPermissions seed.
 *
 * Seeds default role-permission assignments:
 * - Admin role: All rbac.* permissions (RBAC management)
 * - User role: dashboard.view, profile.view, profile.edit (basic access)
 * - Superadmin role: NO permissions (bypass handled in service layer)
 *
 * This seed depends on RolesSeed and PermissionsSeed being run first.
 * This seed is idempotent - safe to run multiple times.
 */
class RolesPermissionsSeed extends BaseSeed
{
    /**
     * Declare dependencies.
     *
     * This seed requires roles and permissions to exist first.
     *
     * @return array<string>
     */
    public function getDependencies(): array
    {
        return ['RolesSeed', 'PermissionsSeed'];
    }

    /**
     * Run Method.
     *
     * Creates role-permission assignments if they don't already exist.
     *
     * @return void
     */
    public function run(): void
    {
        // Idempotency check - skip if any role-permission assignments exist
        $stmt = $this->query("SELECT COUNT(*) as count FROM roles_permissions");
        $result = $stmt->fetch('assoc');

        if ($result['count'] > 0) {
            return; // Assignments already seeded
        }

        // Fetch role IDs
        $stmt = $this->query("SELECT id FROM roles WHERE name = 'admin'");
        $adminRole = $stmt->fetch('assoc');
        $adminRoleId = $adminRole['id'];

        $stmt = $this->query("SELECT id FROM roles WHERE name = 'user'");
        $userRole = $stmt->fetch('assoc');
        $userRoleId = $userRole['id'];

        // Note: We do NOT fetch superadmin - it gets no permissions (bypass in service layer)

        // Fetch permission IDs for admin (all rbac.* permissions)
        $stmt = $this->query("SELECT id FROM permissions WHERE name LIKE 'rbac.%'");
        $rbacPermissions = $stmt->fetchAll('assoc');

        // Fetch permission IDs for user (basic permissions)
        $stmt = $this->query("SELECT id FROM permissions WHERE name IN ('dashboard.view', 'profile.view', 'profile.edit')");
        $userPermissions = $stmt->fetchAll('assoc');

        $now = date('Y-m-d H:i:s');
        $data = [];

        // Assign all rbac.* permissions to admin role
        foreach ($rbacPermissions as $permission) {
            $data[] = [
                'role_id' => $adminRoleId,
                'permission_id' => $permission['id'],
                'created' => $now,
                'modified' => $now,
            ];
        }

        // Assign basic permissions to user role
        foreach ($userPermissions as $permission) {
            $data[] = [
                'role_id' => $userRoleId,
                'permission_id' => $permission['id'],
                'created' => $now,
                'modified' => $now,
            ];
        }

        $table = $this->table('roles_permissions');
        $table->insert($data)->saveData();
    }
}
