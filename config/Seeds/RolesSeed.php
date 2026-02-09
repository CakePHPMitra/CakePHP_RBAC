<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * Roles seed.
 *
 * Seeds default roles for RBAC system:
 * - superadmin: System administrator with unrestricted access (bypasses all checks)
 * - admin: RBAC administrator who manages roles and permissions
 * - user: Standard user with basic access (default role for new users)
 *
 * This seed is idempotent - safe to run multiple times.
 */
class RolesSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * Creates default roles if they don't already exist.
     *
     * @return void
     */
    public function run(): void
    {
        // Idempotency check - skip if superadmin role already exists
        $stmt = $this->query("SELECT COUNT(*) as count FROM roles WHERE name = 'superadmin'");
        $result = $stmt->fetch('assoc');

        if ($result['count'] > 0) {
            return; // Roles already seeded
        }

        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name' => 'superadmin',
                'description' => 'System administrator with unrestricted access',
                'is_system' => true,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 1,
                'parent_id' => null,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'admin',
                'description' => 'RBAC administrator - manages roles and permissions',
                'is_system' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
                'parent_id' => null,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'user',
                'description' => 'Standard user with basic access',
                'is_system' => false,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 3,
                'parent_id' => null,
                'deleted_at' => null,
                'created' => $now,
                'modified' => $now,
            ],
        ];

        $table = $this->table('roles');
        $table->insert($data)->saveData();
    }
}
