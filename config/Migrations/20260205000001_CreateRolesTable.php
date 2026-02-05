<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Create roles table migration.
 *
 * Roles table stores role definitions for RBAC system.
 * Each role can be assigned to multiple users and have multiple permissions.
 */
class CreateRolesTable extends BaseMigration
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
        $table = $this->table('roles');
        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('is_system', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'System roles cannot be deleted',
            ])
            ->addColumn('is_default', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Auto-assigned to new users',
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('sort_order', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => 'Display order in admin UI',
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'null' => true,
                'comment' => 'Future: hierarchical roles',
            ])
            ->addColumn('deleted_at', 'datetime', [
                'default' => null,
                'null' => true,
                'comment' => 'Soft delete support',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['is_active'])
            ->addIndex(['sort_order'])
            ->create();
    }
}
