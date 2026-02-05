<?php
declare(strict_types=1);

namespace Rbac\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Roles Model
 *
 * Manages roles in the RBAC system. Roles can be assigned to users and
 * can have multiple permissions associated with them.
 *
 * @property \Rbac\Model\Table\UsersRolesTable&\Cake\ORM\Association\BelongsToMany $Users
 * @property \Rbac\Model\Table\PermissionsTable&\Cake\ORM\Association\BelongsToMany $Permissions
 * @property \Rbac\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $ParentRoles
 * @property \Rbac\Model\Table\RolesTable&\Cake\ORM\Association\HasMany $ChildRoles
 * @method \Rbac\Model\Entity\Role newEmptyEntity()
 * @method \Rbac\Model\Entity\Role newEntity(array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\Role> newEntities(array $data, array $options = [])
 * @method \Rbac\Model\Entity\Role get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Rbac\Model\Entity\Role findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Rbac\Model\Entity\Role patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\Role> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Rbac\Model\Entity\Role|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Rbac\Model\Entity\Role saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Role>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Role> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Role>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Role> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RolesTable extends Table
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

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        // Behaviors
        $this->addBehavior('Timestamp');

        // BelongsToMany Users (via CakeDC/Users)
        $this->belongsToMany('Users', [
            'className' => 'CakeDC/Users.Users',
            'through' => 'Rbac.UsersRoles',
            'foreignKey' => 'role_id',
            'targetForeignKey' => 'user_id',
            'saveStrategy' => 'append',
        ]);

        // BelongsToMany Permissions
        $this->belongsToMany('Permissions', [
            'className' => 'Rbac.Permissions',
            'through' => 'Rbac.RolesPermissions',
            'foreignKey' => 'role_id',
            'targetForeignKey' => 'permission_id',
            'saveStrategy' => 'append',
        ]);

        // Self-referential for future role hierarchy
        $this->belongsTo('ParentRoles', [
            'className' => 'Rbac.Roles',
            'foreignKey' => 'parent_id',
        ]);

        $this->hasMany('ChildRoles', [
            'className' => 'Rbac.Roles',
            'foreignKey' => 'parent_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->boolean('is_system')
            ->notEmptyString('is_system');

        $validator
            ->boolean('is_default')
            ->notEmptyString('is_default');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

        $validator
            ->integer('sort_order')
            ->notEmptyString('sort_order');

        $validator
            ->integer('parent_id')
            ->allowEmptyString('parent_id');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['name']), 'uniqueName', [
            'errorField' => 'name',
            'message' => __('This role name already exists.'),
        ]);

        $rules->add($rules->existsIn(['parent_id'], 'ParentRoles'), 'validParent', [
            'errorField' => 'parent_id',
            'message' => __('Invalid parent role.'),
        ]);

        return $rules;
    }
}
