<?php
declare(strict_types=1);

namespace Rbac\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolesPermissionsFixture
 *
 * Provides test data for roles_permissions pivot table
 */
class RolesPermissionsFixture extends TestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public string $table = 'roles_permissions';

    /**
     * records property
     *
     * Note: superadmin (role_id=1) has NO permissions because it's a system role
     * that bypasses permission checks. Admin role (role_id=2) has RBAC permissions,
     * and user role (role_id=3) has basic permissions.
     *
     * @var array<array>
     */
    public array $records = [
        [
            'role_id' => 2, // admin
            'permission_id' => 1, // rbac.roles.view
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'role_id' => 2, // admin
            'permission_id' => 2, // rbac.roles.edit
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'role_id' => 3, // user
            'permission_id' => 3, // dashboard.view
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'role_id' => 3, // user
            'permission_id' => 4, // profile.view
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'role_id' => 3, // user
            'permission_id' => 5, // profile.edit
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
    ];
}
