<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Create permissions table migration.
 *
 * Permissions table stores permission definitions for RBAC system.
 * Each permission uses dotted notation (resource.action) for naming.
 */
class CreatePermissionsTable extends BaseMigration
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
        $table = $this->table('permissions');
        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Dotted format: resource.action (e.g., posts.edit)',
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
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
            ->create();
    }
}
