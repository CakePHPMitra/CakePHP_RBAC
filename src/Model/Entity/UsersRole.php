<?php
declare(strict_types=1);

namespace Rbac\Model\Entity;

use Cake\ORM\Entity;

/**
 * UsersRole Entity (Pivot)
 *
 * Represents a user-role assignment in the RBAC system.
 * This is a junction entity linking users and roles.
 *
 * @property string $user_id
 * @property int $role_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \CakeDC\Users\Model\Entity\User $user
 * @property \Rbac\Model\Entity\Role $role
 */
class UsersRole extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'role_id' => true,
        'created' => true,
        'modified' => true,
        // Association fields
        'user' => true,
        'role' => true,
    ];
}
