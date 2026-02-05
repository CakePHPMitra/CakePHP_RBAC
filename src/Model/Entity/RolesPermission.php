<?php
declare(strict_types=1);

namespace Rbac\Model\Entity;

use Cake\ORM\Entity;

/**
 * RolesPermission Entity (Pivot)
 *
 * Represents a role-permission assignment in the RBAC system.
 * This is a junction entity linking roles and permissions.
 *
 * @property int $role_id
 * @property int $permission_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \Rbac\Model\Entity\Role $role
 * @property \Rbac\Model\Entity\Permission $permission
 */
class RolesPermission extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'role_id' => true,
        'permission_id' => true,
        'created' => true,
        'modified' => true,
        // Association fields
        'role' => true,
        'permission' => true,
    ];
}
