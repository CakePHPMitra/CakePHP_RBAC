<?php
declare(strict_types=1);

namespace Rbac\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolesFixture
 *
 * Provides test data for roles table
 */
class RolesFixture extends TestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public string $table = 'roles';

    /**
     * records property
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'name' => 'superadmin',
            'description' => 'Super Administrator with all system permissions',
            'is_system' => true,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 1,
            'parent_id' => null,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'id' => 2,
            'name' => 'admin',
            'description' => 'Administrator with RBAC management permissions',
            'is_system' => false,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 2,
            'parent_id' => null,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'id' => 3,
            'name' => 'user',
            'description' => 'Default user role with basic permissions',
            'is_system' => false,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 3,
            'parent_id' => null,
            'deleted_at' => null,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
    ];
}
