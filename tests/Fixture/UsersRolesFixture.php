<?php
declare(strict_types=1);

namespace Rbac\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersRolesFixture
 *
 * Provides test data for users_roles pivot table
 */
class UsersRolesFixture extends TestFixture
{
    /**
     * table property
     *
     * @var string
     */
    public string $table = 'users_roles';

    /**
     * records property
     *
     * Uses placeholder UUIDs for testing. In host app, these should match
     * actual CakeDC/Users user records.
     *
     * @var array<array>
     */
    public array $records = [
        [
            'user_id' => '550e8400-e29b-41d4-a716-446655440001',
            'role_id' => 2, // admin role
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
        [
            'user_id' => '550e8400-e29b-41d4-a716-446655440002',
            'role_id' => 3, // user role
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 10:00:00',
        ],
    ];
}
