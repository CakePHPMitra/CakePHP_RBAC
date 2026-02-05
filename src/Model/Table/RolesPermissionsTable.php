<?php
declare(strict_types=1);

namespace Rbac\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * RolesPermissions Model (Pivot Table)
 *
 * Junction table linking Roles and Permissions in a many-to-many relationship.
 * Uses composite primary key (role_id, permission_id) defined at database level.
 *
 * @property \Rbac\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 * @property \Rbac\Model\Table\PermissionsTable&\Cake\ORM\Association\BelongsTo $Permissions
 * @method \Rbac\Model\Entity\RolesPermission newEmptyEntity()
 * @method \Rbac\Model\Entity\RolesPermission newEntity(array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\RolesPermission> newEntities(array $data, array $options = [])
 * @method \Rbac\Model\Entity\RolesPermission get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Rbac\Model\Entity\RolesPermission findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Rbac\Model\Entity\RolesPermission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\RolesPermission> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Rbac\Model\Entity\RolesPermission|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Rbac\Model\Entity\RolesPermission saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RolesPermissionsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles_permissions');
        // Note: Composite primary key (role_id, permission_id) handled at database level

        // Behaviors
        $this->addBehavior('Timestamp');

        // BelongsTo Roles
        $this->belongsTo('Roles', [
            'className' => 'Rbac.Roles',
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
        ]);

        // BelongsTo Permissions
        $this->belongsTo('Permissions', [
            'className' => 'Rbac.Permissions',
            'foreignKey' => 'permission_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('role_id')
            ->requirePresence('role_id', 'create')
            ->notEmptyString('role_id');

        $validator
            ->integer('permission_id')
            ->requirePresence('permission_id', 'create')
            ->notEmptyString('permission_id');

        return $validator;
    }
}
