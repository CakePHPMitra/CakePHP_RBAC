<?php
declare(strict_types=1);

namespace Rbac\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PermissionsFixture
 *
 * Provides test data for permissions table
 */
class PermissionsFixture extends TestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public string $table = 'permissions';

    /**
     * records property
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'name' => 'rbac.roles.view',
            'description' => 'View roles in RBAC admin',
            'is_active' => true,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'id' => 2,
            'name' => 'rbac.roles.edit',
            'description' => 'Edit roles in RBAC admin',
            'is_active' => true,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'id' => 3,
            'name' => 'dashboard.view',
            'description' => 'View dashboard',
            'is_active' => true,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'id' => 4,
            'name' => 'profile.view',
            'description' => 'View own profile',
            'is_active' => true,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'id' => 5,
            'name' => 'profile.edit',
            'description' => 'Edit own profile',
            'is_active' => true,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
    ];
}
