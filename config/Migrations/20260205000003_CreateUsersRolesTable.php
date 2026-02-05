<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Create users_roles pivot table migration.
 *
 * Junction table linking users to roles (many-to-many).
 * Uses composite primary key on (user_id, role_id).
 */
class CreateUsersRolesTable extends BaseMigration
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
        $table = $this->table('users_roles', [
            'id' => false,
            'primary_key' => ['user_id', 'role_id'],
        ]);
        $table
            ->addColumn('user_id', 'uuid', [
                'null' => false,
                'comment' => 'References CakeDC/Users UUID primary key',
            ])
            ->addColumn('role_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['user_id', 'role_id'], [
                'unique' => true,
                'name' => 'users_roles_unique',
            ])
            ->addIndex(['role_id'], [
                'name' => 'users_roles_role_id',
                'comment' => 'Speeds up reverse lookups (roles -> users)',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_users_roles_user_id',
            ])
            ->addForeignKey('role_id', 'roles', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_users_roles_role_id',
            ])
            ->create();
    }
}
