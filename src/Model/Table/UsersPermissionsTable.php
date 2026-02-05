<?php
declare(strict_types=1);

namespace Rbac\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UsersPermissions Model (Pivot Table)
 *
 * Junction table linking Users and Permissions in a many-to-many relationship.
 * Enables direct permission assignment to users, bypassing role-based permissions.
 * Uses composite primary key (user_id, permission_id) defined at database level.
 *
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Rbac\Model\Table\PermissionsTable&\Cake\ORM\Association\BelongsTo $Permissions
 * @method \Rbac\Model\Entity\UsersPermission newEmptyEntity()
 * @method \Rbac\Model\Entity\UsersPermission newEntity(array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\UsersPermission> newEntities(array $data, array $options = [])
 * @method \Rbac\Model\Entity\UsersPermission get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Rbac\Model\Entity\UsersPermission findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Rbac\Model\Entity\UsersPermission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\UsersPermission> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Rbac\Model\Entity\UsersPermission|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Rbac\Model\Entity\UsersPermission saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersPermissionsTable extends Table
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

        $this->setTable('users_permissions');
        // Note: Composite primary key (user_id, permission_id) handled at database level

        // Behaviors
        $this->addBehavior('Timestamp');

        // BelongsTo Users (CakeDC/Users)
        $this->belongsTo('Users', [
            'className' => 'CakeDC/Users.Users',
            'foreignKey' => 'user_id',
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
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->integer('permission_id')
            ->requirePresence('permission_id', 'create')
            ->notEmptyString('permission_id');

        return $validator;
    }
}
