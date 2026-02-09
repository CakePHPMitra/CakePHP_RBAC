<?php
declare(strict_types=1);

namespace Rbac\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersPermissionsFixture
 *
 * Provides test data for users_permissions pivot table (direct permission assignment)
 */
class UsersPermissionsFixture extends TestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public string $table = 'users_permissions';

    /**
     * records property
     *
     * Uses placeholder UUIDs for testing. Sample direct permission assignment
     * where a user gets a permission directly (not via role).
     *
     * @var array<array>
     */
    public array $records = [
        [
            'user_id' => '550e8400-e29b-41d4-a716-446655440002',
            'permission_id' => 1, // user also gets direct rbac.roles.view
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
    ];
}
