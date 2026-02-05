<?php
declare(strict_types=1);

namespace Rbac\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * Represents a role in the RBAC system. Roles can be assigned to users
 * and have permissions associated with them.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_system
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $parent_id
 * @property \Cake\I18n\DateTime|null $deleted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \Rbac\Model\Entity\Role|null $parent_role
 * @property \Rbac\Model\Entity\Role[] $child_roles
 * @property \CakeDC\Users\Model\Entity\User[] $users
 * @property \Rbac\Model\Entity\Permission[] $permissions
 * @property \Rbac\Model\Entity\UsersRole $_joinData
 */
class Role extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advisable to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'description' => true,
        'is_system' => true,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => true,
        'parent_id' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        // Association fields
        'users' => true,
        'permissions' => true,
        'parent_role' => true,
        'child_roles' => true,
        '_joinData' => true,
        // Protect primary key
        'id' => false,
    ];
}
