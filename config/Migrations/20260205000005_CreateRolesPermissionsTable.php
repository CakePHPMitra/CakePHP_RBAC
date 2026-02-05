<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Create roles_permissions pivot table migration.
 *
 * Junction table linking roles to permissions (many-to-many).
 * Uses composite primary key on (role_id, permission_id).
 */
class CreateRolesPermissionsTable extends BaseMigration
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
        $table = $this->table('roles_permissions', [
            'id' => false,
            'primary_key' => ['role_id', 'permission_id'],
        ]);
        $table
            ->addColumn('role_id', 'integer', [
                'null' => false,
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
            ->addIndex(['role_id', 'permission_id'], [
                'unique' => true,
                'name' => 'roles_permissions_unique',
            ])
            ->addIndex(['permission_id'], [
                'name' => 'roles_permissions_permission_id',
                'comment' => 'Speeds up reverse lookups (permissions -> roles)',
            ])
            ->addForeignKey('role_id', 'roles', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_roles_permissions_role_id',
            ])
            ->addForeignKey('permission_id', 'permissions', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_roles_permissions_permission_id',
            ])
            ->create();
    }
}
