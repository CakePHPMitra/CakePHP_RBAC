<?php
declare(strict_types=1);

namespace Rbac\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UsersRoles Model (Pivot Table)
 *
 * Junction table linking Users and Roles in a many-to-many relationship.
 * Uses composite primary key (user_id, role_id) defined at database level.
 *
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Rbac\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 * @method \Rbac\Model\Entity\UsersRole newEmptyEntity()
 * @method \Rbac\Model\Entity\UsersRole newEntity(array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\UsersRole> newEntities(array $data, array $options = [])
 * @method \Rbac\Model\Entity\UsersRole get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Rbac\Model\Entity\UsersRole findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Rbac\Model\Entity\UsersRole patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\UsersRole> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Rbac\Model\Entity\UsersRole|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Rbac\Model\Entity\UsersRole saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersRolesTable extends Table
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

        $this->setTable('users_roles');
        // Note: Composite primary key (user_id, role_id) handled at database level

        // Behaviors
        $this->addBehavior('Timestamp');

        // BelongsTo Users (CakeDC/Users)
        $this->belongsTo('Users', [
            'className' => 'CakeDC/Users.Users',
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        // BelongsTo Roles
        $this->belongsTo('Roles', [
            'className' => 'Rbac.Roles',
            'foreignKey' => 'role_id',
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
            ->integer('role_id')
            ->requirePresence('role_id', 'create')
            ->notEmptyString('role_id');

        return $validator;
    }
}
