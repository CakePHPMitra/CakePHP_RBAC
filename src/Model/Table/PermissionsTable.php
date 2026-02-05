<?php
declare(strict_types=1);

namespace Rbac\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Permissions Model
 *
 * Manages permissions in the RBAC system. Permissions can be assigned to roles
 * or directly to users. Permission names use dotted format (e.g., 'posts.edit').
 *
 * @property \Rbac\Model\Table\RolesTable&\Cake\ORM\Association\BelongsToMany $Roles
 * @property \Rbac\Model\Table\UsersPermissionsTable&\Cake\ORM\Association\BelongsToMany $Users
 * @method \Rbac\Model\Entity\Permission newEmptyEntity()
 * @method \Rbac\Model\Entity\Permission newEntity(array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\Permission> newEntities(array $data, array $options = [])
 * @method \Rbac\Model\Entity\Permission get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Rbac\Model\Entity\Permission findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Rbac\Model\Entity\Permission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Rbac\Model\Entity\Permission> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Rbac\Model\Entity\Permission|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Rbac\Model\Entity\Permission saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Permission>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Permission> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Permission>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\Rbac\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\Rbac\Model\Entity\Permission> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PermissionsTable extends Table
{
    /**
     * Permission name pattern - alphanumeric characters and dots only
     *
     * @var string
     */
    public const NAME_PATTERN = '/^[a-zA-Z0-9.]+$/';

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('permissions');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        // Behaviors
        $this->addBehavior('Timestamp');

        // BelongsToMany Roles
        $this->belongsToMany('Roles', [
            'className' => 'Rbac.Roles',
            'through' => 'Rbac.RolesPermissions',
            'foreignKey' => 'permission_id',
            'targetForeignKey' => 'role_id',
            'saveStrategy' => 'append',
        ]);

        // BelongsToMany Users (direct permission assignment)
        $this->belongsToMany('Users', [
            'className' => 'CakeDC/Users.Users',
            'through' => 'Rbac.UsersPermissions',
            'foreignKey' => 'permission_id',
            'targetForeignKey' => 'user_id',
            'saveStrategy' => 'append',
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
            ->notEmptyString('name')
            ->add('name', 'validFormat', [
                'rule' => ['custom', self::NAME_PATTERN],
                'message' => __('Permission name must contain only alphanumeric characters and dots (e.g., posts.edit).'),
            ]);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

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
            'message' => __('This permission name already exists.'),
        ]);

        return $rules;
    }
}
