# Phase 1: Database Foundation - Research

**Researched:** 2026-02-05
**Domain:** CakePHP 5 Migrations, ORM, and Database Schema Design
**Confidence:** HIGH

## Summary

CakePHP 5 uses the cakephp/migrations plugin (v4.9.3+, PHP 8.1+), which wraps Phinx for database schema management. As of Migrations 5.0, Phinx has been fully removed and the plugin is a zero-dependency system built entirely on CakePHP ORM. Migrations use reversible `change()` methods for most operations, stored in `config/Migrations/` with timestamp-based filenames.

For RBAC schema, the standard pattern uses:
- **Integer-keyed tables** for roles and permissions (auto-incrementing primary keys)
- **UUID foreign keys** for user references (matching CakeDC/Users convention)
- **belongsToMany associations** with explicit `through` option for pivot tables
- **Composite unique constraints** on pivot tables to prevent duplicates
- **Selective CASCADE/RESTRICT** constraints based on data importance

**Primary recommendation:** Use CakePHP 5 Migrations with reversible migrations, explicit `through` associations for pivot tables, composite unique indexes on all pivots, and idempotent seed data via `BaseSeed` classes with dependency ordering.

## Standard Stack

The established libraries/tools for this domain:

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| cakephp/migrations | 4.9.3+ | Database schema management | Official CakePHP plugin, zero dependencies in 5.x, replaces Phinx |
| cakephp/orm | 5.x | ORM and associations | Core CakePHP framework component |
| CakeDC/Users | 16.0+ | User management with UUID keys | Already integrated, provides users table schema |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Muffin/Trash | 3.x+ | Soft delete behavior | If implementing `deleted_at` column (optional per CONTEXT.md) |
| cakephp/bake | 3.x+ | Migration generation CLI | Speeds up migration file creation |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Migrations 5.x | Raw SQL migrations | Lose database portability and ORM integration |
| belongsToMany | Manual pivot queries | Lose automatic eager loading and save strategies |
| Composite unique indexes | Application-level validation only | Risk duplicate data, harder to enforce constraints |

**Installation:**
```bash
composer require cakephp/migrations:^4.9
# Already installed in CakePHP 5 projects by default
```

## Architecture Patterns

### Recommended Project Structure
```
plugins/Rbac/
├── config/
│   ├── Migrations/        # Migration files (timestamp-prefixed)
│   └── Seeds/             # Seed data files
├── src/
│   └── Model/
│       ├── Table/         # Table classes with associations
│       └── Entity/        # Entity classes
└── tests/
    ├── TestCase/Model/Table/   # Table tests
    └── Fixture/                # Test fixtures
```

### Pattern 1: Reversible Migration with change()
**What:** Single method that auto-generates down() logic
**When to use:** For all table/column creation and non-destructive changes
**Example:**
```php
// Source: https://book.cakephp.org/migrations/5/en/
use Migrations\BaseMigration;

class CreateRoles extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('roles');
        $table->addColumn('name', 'string', [
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('description', 'text', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('is_active', 'boolean', [
            'default' => true,
            'null' => false,
        ])
        ->addColumn('created', 'datetime', [
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'null' => false,
        ])
        ->addIndex(['name'], ['unique' => true])
        ->create();
    }
}
```

### Pattern 2: Pivot Table with Composite Unique Index
**What:** Junction table with compound foreign keys and unique constraint
**When to use:** For all many-to-many relationships (users_roles, users_permissions, roles_permissions)
**Example:**
```php
// Source: https://book.cakephp.org//phinx/0/en/migrations.html
public function change(): void
{
    $table = $this->table('users_roles', ['id' => false, 'primary_key' => ['user_id', 'role_id']]);
    $table->addColumn('user_id', 'uuid', ['null' => false])
          ->addColumn('role_id', 'integer', ['null' => false])
          ->addColumn('created', 'datetime', ['null' => false])
          ->addColumn('modified', 'datetime', ['null' => false])
          ->addIndex(['user_id', 'role_id'], ['unique' => true])
          ->addForeignKey('user_id', 'users', 'id', [
              'delete' => 'CASCADE',
              'update' => 'CASCADE'
          ])
          ->addForeignKey('role_id', 'roles', 'id', [
              'delete' => 'RESTRICT',
              'update' => 'CASCADE'
          ])
          ->create();
}
```

**Note:** Composite primary key OR composite unique index prevents duplicates. Using `id: false` with `primary_key: ['col1', 'col2']` is cleaner for true junction tables.

### Pattern 3: belongsToMany with Explicit Through
**What:** ORM association that explicitly names the pivot table
**When to use:** Always for pivot tables with extra columns (timestamps, metadata)
**Example:**
```php
// Source: https://book.cakephp.org/5.x/orm/associations.html
// In src/Model/Table/UsersTable.php
public function initialize(array $config): void
{
    parent::initialize($config);

    $this->belongsToMany('Rbac.Roles', [
        'through' => 'Rbac.UsersRoles',
        'foreignKey' => 'user_id',
        'targetForeignKey' => 'role_id',
        'saveStrategy' => 'append',  // Don't replace existing links
    ]);
}

// In plugins/Rbac/src/Model/Table/UsersRolesTable.php
public function initialize(array $config): void
{
    parent::initialize($config);

    $this->belongsTo('Users', [
        'foreignKey' => 'user_id',
        'className' => 'CakeDC/Users.Users'
    ]);
    $this->belongsTo('Roles', [
        'foreignKey' => 'role_id',
        'className' => 'Rbac.Roles'
    ]);
}
```

### Pattern 4: Idempotent Seed Data
**What:** Seed files that check for existence before inserting
**When to use:** For all default/system data (roles, base permissions)
**Example:**
```php
// Source: https://book.cakephp.org/migrations/4/en/seeding.html
use Migrations\BaseSeed;

class RolesSeed extends BaseSeed
{
    public function getDependencies(): array
    {
        return []; // No dependencies for roles
    }

    public function run(): void
    {
        $rolesTable = $this->table('roles');

        // Check if data already exists (idempotent)
        $existing = $rolesTable->getAdapter()
            ->fetchAll('SELECT COUNT(*) as count FROM roles');

        if ($existing[0]['count'] > 0) {
            return; // Skip if data exists
        }

        $data = [
            [
                'name' => 'superadmin',
                'description' => 'System administrator with full access',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
            // ... more roles
        ];

        $rolesTable->insert($data)->saveData();
    }
}
```

### Pattern 5: Seed Dependencies with getDependencies()
**What:** Ordering seed execution to respect foreign key constraints
**When to use:** When seed data has dependencies (e.g., roles_permissions needs roles and permissions first)
**Example:**
```php
// Source: https://book.cakephp.org/migrations/4/en/seeding.html
class RolesPermissionsSeed extends BaseSeed
{
    public function getDependencies(): array
    {
        return [
            'RolesSeed',
            'PermissionsSeed',
        ];
    }

    public function run(): void
    {
        // Roles and Permissions guaranteed to exist
    }
}
```

### Anti-Patterns to Avoid
- **Removing columns in change():** Not reversible - use separate up()/down() methods
- **Skipping index on pivot foreign keys:** Slows down joins significantly
- **Using saveStrategy: 'replace' on user assignments:** Destroys existing role assignments
- **Non-idempotent seeds:** Will error on re-run or create duplicates
- **Mixing string and int primary keys:** CakeDC/Users uses UUID, keep consistent within entity types

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Soft deletes | Custom deleted_at logic | Muffin/Trash behavior | Handles queries, scoping, and restore logic automatically |
| Migration rollbacks | Manual down() methods | Reversible change() method | Auto-generates reverse operations for common changes |
| Seed ordering | Manual execution order | getDependencies() | Framework ensures correct execution order |
| Duplicate prevention | Application validation only | Database unique constraints | Enforced at DB level, prevents race conditions |
| Timestamp management | Manual created/modified | TimestampBehavior | Automatic, tested, handles edge cases |
| Association loading | Raw SQL joins | belongsToMany with contain() | Automatic eager loading, query optimization, lazy loading support |
| Idempotency checks | Custom tracking tables | INSERT ... ON DUPLICATE KEY UPDATE or existence checks | Database-native patterns, atomic operations |

**Key insight:** CakePHP's convention-over-configuration means "rolling your own" often loses automatic functionality like counter caches, validation, callbacks, and query optimization.

## Common Pitfalls

### Pitfall 1: Foreign Key Order Mismatch in Pivot Tables
**What goes wrong:** Pivot table names follow alphabetical order (articles_tags not tags_articles), but developers reverse foreign key order in associations, breaking convention-based lookups.
**Why it happens:** Not understanding CakePHP's alphabetical naming convention for junction tables.
**How to avoid:** Always name pivot tables alphabetically and match foreignKey order to table name order. Use explicit `joinTable` option if deviating.
**Warning signs:** Association queries return empty results despite data existing; EXPLAIN shows table scan instead of index use.

### Pitfall 2: Missing Composite Index on Pivot Tables
**What goes wrong:** Individual foreign key indexes exist but no composite index covering both columns. Queries checking "does user X have role Y" scan entire table.
**Why it happens:** Assuming individual column indexes are sufficient; not understanding left-to-right index usage.
**How to avoid:** Always create composite index on pivot tables: `->addIndex(['user_id', 'role_id'], ['unique' => true])`. This serves dual purpose: uniqueness constraint AND query optimization.
**Warning signs:** EXPLAIN shows "Using where" without "Using index"; slow permission checks; increasing query time as pivot table grows.

### Pitfall 3: Non-Reversible Operations in change()
**What goes wrong:** Adding `->removeColumn()` in change() method causes migration rollback to fail because reverse operation (addColumn) needs column type information.
**Why it happens:** Assuming all operations are auto-reversible; not reading documentation about change() limitations.
**How to avoid:** Use separate up()/down() methods for destructive operations (DROP COLUMN, DELETE, ALTER TYPE). Keep change() for additive operations only.
**Warning signs:** `bin/cake migrations rollback` fails with "Cannot reverse removeColumn" error.

### Pitfall 4: Seed Data Without Idempotency
**What goes wrong:** Running `bin/cake migrations seed` multiple times creates duplicate records, violates unique constraints, or errors out.
**Why it happens:** Unlike migrations, seeds don't track execution. Developers assume seeds auto-skip if already run.
**How to avoid:** Always check for existence before inserting: `SELECT COUNT(*) FROM table WHERE unique_field = ?`. Use INSERT IGNORE or INSERT ... ON DUPLICATE KEY UPDATE patterns.
**Warning signs:** "Duplicate entry" errors on second seed run; test environment has doubled records; CI fails intermittently.

### Pitfall 5: UUID vs Integer Key Confusion
**What goes wrong:** Using `->addColumn('user_id', 'integer')` when CakeDC/Users uses UUID primary keys. Foreign key constraint fails or queries return no results.
**Why it happens:** Not checking the target table's primary key type before creating foreign key.
**How to avoid:** Verify primary key type: Users table uses 'uuid', Roles/Permissions use 'integer' (auto-increment). Match foreign key type exactly.
**Warning signs:** Foreign key creation fails with type mismatch; ORM associations return empty despite seed data; DESCRIBE shows type mismatch.

### Pitfall 6: Forgetting Timestamps in Pivot Tables
**What goes wrong:** Pivot tables lack `created`/`modified` columns. TimestampBehavior errors on save, or audit trail is lost.
**Why it happens:** Assuming junction tables don't need timestamps because they're "just links."
**How to avoid:** Always add `created` and `modified` datetime columns to pivot tables, even if not using them immediately. Future audit requirements will thank you.
**Warning signs:** "Column 'created' not found" errors when saving associations; no audit trail for when role assignments changed.

### Pitfall 7: CASCADE DELETE on Critical Relationships
**What goes wrong:** Deleting a role CASCADE deletes all roles_permissions entries, but RESTRICT would have caught that role is still in use.
**Why it happens:** Defaulting to CASCADE for convenience without considering data importance.
**How to avoid:** Use RESTRICT for relationships where child data is independently valuable (user assignments, financial records). Use CASCADE only for truly dependent data (session tokens, cache entries).
**Warning signs:** Silent data loss; "Why did all these permissions disappear?" questions; inability to restore deleted role's permission set.

## Code Examples

Verified patterns from official sources:

### Complete Migration: Roles Table
```php
// Source: https://book.cakephp.org/migrations/5/en/ and CakeDC/Users pattern
use Migrations\BaseMigration;

class CreateRoles extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('roles');
        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('is_system', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'System roles cannot be deleted',
            ])
            ->addColumn('is_default', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Auto-assigned to new users',
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('sort_order', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'null' => true,
                'comment' => 'Future: role hierarchy',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['is_active'])
            ->addIndex(['sort_order'])
            ->create();
    }
}
```

### Complete Migration: Permissions Table
```php
// Source: https://book.cakephp.org/migrations/5/en/
use Migrations\BaseMigration;

class CreatePermissions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('permissions');
        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Dotted format: resource.action',
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['is_active'])
            ->create();
    }
}
```

### Complete Migration: UsersRoles Pivot Table
```php
// Source: https://book.cakephp.org/migrations/5/en/ and CakeDC/Users foreign key pattern
use Migrations\BaseMigration;

class CreateUsersRoles extends BaseMigration
{
    public function change(): void
    {
        // Option 1: Composite primary key (no separate id)
        $table = $this->table('users_roles', [
            'id' => false,
            'primary_key' => ['user_id', 'role_id']
        ]);

        $table
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('role_id', 'integer', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['user_id', 'role_id'], ['unique' => true])
            ->addIndex(['role_id']) // Speeds up reverse lookups
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',  // User deleted = remove assignments
                'update' => 'CASCADE'
            ])
            ->addForeignKey('role_id', 'roles', 'id', [
                'delete' => 'RESTRICT', // Role in use = cannot delete
                'update' => 'CASCADE'
            ])
            ->create();
    }
}
```

### Complete Table Class: RolesTable
```php
// Source: https://book.cakephp.org/5.x/orm/associations.html and CakeDC/Users pattern
namespace Rbac\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class RolesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        // Behaviors
        $this->addBehavior('Timestamp');

        // Associations
        $this->belongsToMany('Users', [
            'className' => 'CakeDC/Users.Users',
            'through' => 'Rbac.UsersRoles',
            'foreignKey' => 'role_id',
            'targetForeignKey' => 'user_id',
            'saveStrategy' => 'append',
        ]);

        $this->belongsToMany('Permissions', [
            'className' => 'Rbac.Permissions',
            'through' => 'Rbac.RolesPermissions',
            'foreignKey' => 'role_id',
            'targetForeignKey' => 'permission_id',
            'saveStrategy' => 'append',
        ]);

        $this->belongsTo('ParentRoles', [
            'className' => 'Rbac.Roles',
            'foreignKey' => 'parent_id',
        ]);

        $this->hasMany('ChildRoles', [
            'className' => 'Rbac.Roles',
            'foreignKey' => 'parent_id',
        ]);
    }

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
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->boolean('is_system')
            ->notEmptyString('is_system');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

        $validator
            ->integer('sort_order')
            ->notEmptyString('sort_order');

        return $validator;
    }
}
```

### Complete Seed: RolesSeed
```php
// Source: https://book.cakephp.org/migrations/4/en/seeding.html
use Migrations\BaseSeed;

class RolesSeed extends BaseSeed
{
    public function run(): void
    {
        $adapter = $this->getAdapter();

        // Idempotent: Check if roles already seeded
        $count = $adapter->fetchRow('SELECT COUNT(*) as count FROM roles WHERE name = ?', ['superadmin']);
        if ($count['count'] > 0) {
            return; // Skip if already seeded
        }

        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name' => 'superadmin',
                'description' => 'System administrator with unrestricted access',
                'is_system' => true,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 1,
                'parent_id' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'admin',
                'description' => 'RBAC administrator - manages roles and permissions',
                'is_system' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
                'parent_id' => null,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'name' => 'user',
                'description' => 'Standard user with basic access',
                'is_system' => false,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 3,
                'parent_id' => null,
                'created' => $now,
                'modified' => $now,
            ],
        ];

        $this->table('roles')->insert($data)->saveData();
    }
}
```

### Complete Seed: PermissionsSeed with Dependencies
```php
// Source: https://book.cakephp.org/migrations/4/en/seeding.html
use Migrations\BaseSeed;

class PermissionsSeed extends BaseSeed
{
    public function run(): void
    {
        $adapter = $this->getAdapter();

        // Idempotent: Check if permissions already seeded
        $count = $adapter->fetchRow('SELECT COUNT(*) as count FROM permissions WHERE name LIKE ?', ['rbac.%']);
        if ($count['count'] > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $data = [
            // RBAC Management Permissions
            ['name' => 'rbac.roles.view', 'description' => 'View roles list', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.roles.create', 'description' => 'Create new roles', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.roles.edit', 'description' => 'Edit existing roles', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.roles.delete', 'description' => 'Delete roles', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.permissions.view', 'description' => 'View permissions list', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.permissions.create', 'description' => 'Create permissions', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.permissions.edit', 'description' => 'Edit permissions', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.permissions.delete', 'description' => 'Delete permissions', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'rbac.users.assign', 'description' => 'Assign roles to users', 'is_active' => true, 'created' => $now, 'modified' => $now],

            // Basic User Permissions
            ['name' => 'dashboard.view', 'description' => 'View dashboard', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'profile.view', 'description' => 'View own profile', 'is_active' => true, 'created' => $now, 'modified' => $now],
            ['name' => 'profile.edit', 'description' => 'Edit own profile', 'is_active' => true, 'created' => $now, 'modified' => $now],
        ];

        $this->table('permissions')->insert($data)->saveData();
    }
}
```

### Complete Seed: RolesPermissionsSeed with Dependencies
```php
// Source: https://book.cakephp.org/migrations/4/en/seeding.html
use Migrations\BaseSeed;

class RolesPermissionsSeed extends BaseSeed
{
    public function getDependencies(): array
    {
        return [
            'RolesSeed',
            'PermissionsSeed',
        ];
    }

    public function run(): void
    {
        $adapter = $this->getAdapter();

        // Idempotent: Check if already seeded
        $count = $adapter->fetchRow('SELECT COUNT(*) as count FROM roles_permissions');
        if ($count['count'] > 0) {
            return;
        }

        // Fetch role IDs
        $adminRole = $adapter->fetchRow('SELECT id FROM roles WHERE name = ?', ['admin']);
        $userRole = $adapter->fetchRow('SELECT id FROM roles WHERE name = ?', ['user']);

        // Fetch permission IDs
        $rbacPerms = $adapter->fetchAll('SELECT id, name FROM permissions WHERE name LIKE ?', ['rbac.%']);
        $userPerms = $adapter->fetchAll('SELECT id FROM permissions WHERE name IN (?, ?, ?)',
            ['dashboard.view', 'profile.view', 'profile.edit']);

        $now = date('Y-m-d H:i:s');
        $data = [];

        // Admin gets all RBAC permissions
        foreach ($rbacPerms as $perm) {
            $data[] = [
                'role_id' => $adminRole['id'],
                'permission_id' => $perm['id'],
                'created' => $now,
                'modified' => $now,
            ];
        }

        // User gets basic permissions
        foreach ($userPerms as $perm) {
            $data[] = [
                'role_id' => $userRole['id'],
                'permission_id' => $perm['id'],
                'created' => $now,
                'modified' => $now,
            ];
        }

        // Note: Superadmin gets NO permissions (bypass handled in service layer)

        $this->table('roles_permissions')->insert($data)->saveData();
    }
}
```

### Test Example: Testing Association Loading
```php
// Source: https://book.cakephp.org/5.x/development/testing.html
namespace Rbac\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Rbac\Model\Table\RolesTable;

class RolesTableTest extends TestCase
{
    protected array $fixtures = [
        'plugin.Rbac.Roles',
        'plugin.Rbac.Permissions',
        'plugin.Rbac.RolesPermissions',
        'plugin.CakeDC/Users.Users',
        'plugin.Rbac.UsersRoles',
    ];

    public function testBelongsToManyPermissions(): void
    {
        $roles = $this->getTableLocator()->get('Rbac.Roles');

        // Load role with permissions
        $role = $roles->get(1, ['contain' => ['Permissions']]);

        // Assert association loaded
        $this->assertNotEmpty($role->permissions);
        $this->assertInstanceOf('Rbac\Model\Entity\Permission', $role->permissions[0]);
    }

    public function testUniqueConstraint(): void
    {
        $roles = $this->getTableLocator()->get('Rbac.Roles');

        // Try to create duplicate role name
        $role = $roles->newEntity(['name' => 'admin', 'is_active' => true]);
        $result = $roles->save($role);

        // Should fail due to unique constraint
        $this->assertFalse($result);
        $this->assertNotEmpty($role->getErrors());
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Phinx dependency | Zero-dependency Migrations 5.0 | 2024 | Simpler installation, better CakePHP integration |
| Manual timestamp handling | TimestampBehavior automatic | CakePHP 3.0+ | Less boilerplate, fewer bugs |
| HABTM association type | belongsToMany | CakePHP 3.0+ | More explicit, better junction table control |
| String-based seed class names | Short names (UsersSeed → Users) | Migrations 5.0 | Cleaner CLI commands |
| --seed flag | Argument-based seeds | Migrations 5.0 | `bin/cake seeds run User` instead of `--seed UsersSeed` |
| Manual idempotency | Framework encouragement | Ongoing | More reliable multi-environment deploys |

**Deprecated/outdated:**
- **$hasAndBelongsToMany (HABTM):** Replaced by belongsToMany in CakePHP 3.0+. More explicit junction table control.
- **Phinx as standalone dependency:** Migrations 5.0 removed Phinx entirely. Use CakePHP Migrations native methods.
- **Non-reversible migrations as default:** Prefer change() method over separate up()/down() for all reversible operations.
- **Seeds without getDependencies():** Modern approach uses explicit dependency declaration for clarity.

## Open Questions

Things that couldn't be fully resolved:

1. **Soft Delete Implementation**
   - What we know: Muffin/Trash is the standard plugin; `deleted_at` column is nullable datetime
   - What's unclear: Whether soft delete should apply to permissions (probably yes) and roles (depends on use case)
   - Recommendation: Implement `deleted_at` columns in migrations but make behavior attachment configurable via plugin config. Default to OFF initially (YAGNI principle).

2. **Composite Primary Key vs Unique Index for Pivot Tables**
   - What we know: Both prevent duplicates; composite PK saves 4 bytes per row (no separate id column)
   - What's unclear: CakePHP ORM preference - some developers prefer id column for simplicity
   - Recommendation: Use composite primary key (`'id' => false, 'primary_key' => ['col1', 'col2']`) for true junction tables with no additional business logic. Cleaner and follows CakePHP 5 conventions visible in official examples.

3. **Permission Name Validation Strictness**
   - What we know: Alphanumeric + dots regex: `[a-zA-Z0-9.]+`
   - What's unclear: Should underscores be allowed? Should minimum segments be enforced (e.g., require at least one dot)?
   - Recommendation: Start strict (alphanumeric + dots only, minimum 1 dot) and relax if host app needs different format. Easier to relax than tighten later.

## Sources

### Primary (HIGH confidence)
- [CakePHP Migrations 5.x Official Documentation](https://book.cakephp.org/migrations/5/en/) - Migration file structure, reversible migrations, plugin conventions
- [CakePHP 5.x ORM Associations](https://book.cakephp.org/5.x/orm/associations.html) - belongsToMany patterns, through option, foreignKey configuration
- [CakePHP Migrations 4.x Database Seeding](https://book.cakephp.org/migrations/4/en/seeding.html) - Seed classes, getDependencies(), idempotency patterns
- [Phinx Writing Migrations](https://book.cakephp.org//phinx/0/en/migrations.html) - Composite indexes, foreign key syntax (still relevant for Migrations API)
- [CakePHP 5.x Testing](https://book.cakephp.org/5.x/development/testing.html) - Fixture patterns, test structure
- CakeDC/Users source code - UUID foreign keys, migration patterns, association examples (/home/atul/Documents/Projects/claudepe-admin/vendor/cakedc/users/)

### Secondary (MEDIUM confidence)
- [MySQL Cascading Changes Article](https://www.artie.com/blogs/mysql-cascading-changes-and-why-you-shouldnt-use-them) - CASCADE vs RESTRICT best practices, verified with official MySQL docs
- [SQL Indexing Best Practices](https://ai2sql.io/sql-indexing-best-practices-speed-up-your-queries) - Composite index performance, verified with multiple database documentation sources
- [Database Migration Idempotency](https://www.red-gate.com/hub/product-learning/flyway/creating-idempotent-ddl-scripts-for-database-migrations) - Idempotency patterns, cross-verified with EF Core documentation
- [PlanetScale MySQL Composite Indexes](https://planetscale.com/learn/courses/mysql-for-developers/indexes/composite-indexes) - Index ordering, left-to-right rule

### Tertiary (LOW confidence)
- [Muffin/Trash Plugin GitHub](https://github.com/UseMuffin/Trash) - Soft delete patterns (not fully verified for CakePHP 5 compatibility)
- [Best Practices for Seeds and Fixtures](https://moldstud.com/articles/p-best-practices-for-managing-seeds-and-fixtures-in-cakephp-migrations) - General patterns (not authoritative source)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Official CakePHP plugins and documented patterns
- Architecture: HIGH - Patterns verified in official documentation and CakeDC source code
- Pitfalls: MEDIUM - Based on common issues found in GitHub issues and community discussions, not all personally verified

**Research date:** 2026-02-05
**Valid until:** March 2026 (30 days) - CakePHP 5 is stable; migrations API unlikely to change significantly in short term
