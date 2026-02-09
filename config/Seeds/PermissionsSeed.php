<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * Permissions seed.
 *
 * Seeds default permissions for RBAC system:
 * - RBAC management permissions (rbac.* namespace) - for admin role
 * - Basic user permissions (dashboard, profile) - for user role
 *
 * This seed is idempotent - safe to run multiple times.
 */
class PermissionsSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * Creates default permissions if they don't already exist.
     *
     * @return void
     */
    public function run(): void
    {
        // Idempotency check - skip if rbac.roles.view permission already exists
        $stmt = $this->query("SELECT COUNT(*) as count FROM permissions WHERE name = 'rbac.roles.view'");
        $result = $stmt->fetch('assoc');

        if ($result['count'] > 0) {
            return; // Permissions already seeded
        }

        $now = date('Y-m-d H:i:s');

        $data = [
            // RBAC Management Permissions (for admin role)
            [
                'name' => 'rbac.roles.view',
                'description' => 'View roles list',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.roles.create',
                'description' => 'Create new roles',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.roles.edit',
                'description' => 'Edit existing roles',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.roles.delete',
                'description' => 'Delete roles',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.permissions.view',
                'description' => 'View permissions list',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.permissions.create',
                'description' => 'Create permissions',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.permissions.edit',
                'description' => 'Edit permissions',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.permissions.delete',
                'description' => 'Delete permissions',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.users.assign',
                'description' => 'Assign roles and permissions to users',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.matrix.view',
                'description' => 'View permission matrix',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'rbac.matrix.edit',
                'description' => 'Edit permission matrix assignments',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            // Basic User Permissions
            [
                'name' => 'dashboard.view',
                'description' => 'View dashboard',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'profile.view',
                'description' => 'View own profile',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'profile.edit',
                'description' => 'Edit own profile',
                'is_active' => true,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
        ];

        $table = $this->table('permissions');
        $table->insert($data)->saveData();
    }
}
