<?php
declare(strict_types=1);

namespace Rbac\Model\Entity;

use Cake\ORM\Entity;

/**
 * UsersPermission Entity (Pivot)
 *
 * Represents a direct user-permission assignment in the RBAC system.
 * This is a junction entity linking users and permissions directly,
 * bypassing role-based permission assignment.
 *
 * @property string $user_id
 * @property int $permission_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \CakeDC\Users\Model\Entity\User $user
 * @property \Rbac\Model\Entity\Permission $permission
 */
class UsersPermission extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'permission_id' => true,
        'created' => true,
        'modified' => true,
        // Association fields
        'user' => true,
        'permission' => true,
    ];
}
