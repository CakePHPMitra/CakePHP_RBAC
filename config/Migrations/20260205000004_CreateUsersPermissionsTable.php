<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Create users_permissions pivot table migration.
 *
 * Junction table for direct user-permission assignments (many-to-many).
 * Allows assigning permissions directly to users, bypassing roles.
 * Uses composite primary key on (user_id, permission_id).
 */
class CreateUsersPermissionsTable extends BaseMigration
{
    /**
     * Change Method.
     *
     * Reversible migration using change() method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users_permissions', [
            'id' => false,
            'primary_key' => ['user_id', 'permission_id'],
        ]);
        $table
            ->addColumn('user_id', 'uuid', [
                'null' => false,
                'comment' => 'References CakeDC/Users UUID primary key',
            ])
            ->addColumn('permission_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['user_id', 'permission_id'], [
                'unique' => true,
                'name' => 'users_permissions_unique',
            ])
            ->addIndex(['permission_id'], [
                'name' => 'users_permissions_permission_id',
                'comment' => 'Speeds up reverse lookups (permissions -> users)',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_users_permissions_user_id',
            ])
            ->addForeignKey('permission_id', 'permissions', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_users_permissions_permission_id',
            ])
            ->create();
    }
}
