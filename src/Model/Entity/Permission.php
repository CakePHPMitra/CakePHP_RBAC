<?php
declare(strict_types=1);

namespace Rbac\Model\Entity;

use Cake\ORM\Entity;

/**
 * Permission Entity
 *
 * Represents a permission in the RBAC system. Permissions can be assigned
 * to roles or directly to users. Permission names use dotted format
 * (e.g., 'posts.edit', 'rbac.roles.view').
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \Cake\I18n\DateTime|null $deleted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \Rbac\Model\Entity\Role[] $roles
 * @property \CakeDC\Users\Model\Entity\User[] $users
 * @property \Rbac\Model\Entity\RolesPermission $_joinData
 */
class Permission extends Entity
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
        'is_active' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        // Association fields
        'roles' => true,
        'users' => true,
        '_joinData' => true,
        // Protect primary key
        'id' => false,
    ];
}
