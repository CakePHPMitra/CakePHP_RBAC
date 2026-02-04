# Technology Stack

**Project:** CakePHPMitra/RBAC
**Researched:** 2026-02-04
**Confidence:** HIGH (based on CakeDC ecosystem patterns and existing CloudPE Admin implementation)

## Recommended Stack

### Core Framework

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| CakePHP | ^5.0 | Core framework | Current stable version, required for host app compatibility |
| PHP | >=8.1 | Runtime | Minimum for CakePHP 5, enables typed properties, enums, fibers |

### Authentication & Authorization

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| cakedc/users | ^16.0 | User identity provider | Industry standard for CakePHP authentication, provides OAuth, 2FA, handles user lifecycle |
| cakedc/auth | ^10.1 | Authorization helpers | Provides RbacPolicy, SuperuserPolicy, CollectionPolicy patterns that integrate with CakePHP Authorization |
| cakephp/authorization | ^3.0 | Authorization framework | Official CakePHP authorization library, provides middleware, policies, resolvers |
| cakephp/authentication | ^3.0 | Authentication framework | Transitive dependency via cakedc/users, ensures compatibility |

**Rationale:** CakeDC stack is the de facto standard for CakePHP auth. CakeDC/Auth already provides RBAC patterns (RbacPolicy, Rbac class) that we'll extend, not replace. This plugin replaces the static config-file backend with database-backed resolver.

### Database

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| cakephp/migrations | ^4.0 | Schema management | Official migration tool, Phinx-based, handles up/down migrations cleanly |
| MariaDB/MySQL | 10.11+ / 8.0+ | Primary database | Most common CakePHP deployment target, supports foreign keys, transactions |

**Rationale:** CakePHP Migrations is standard. MariaDB/MySQL as primary target because it's the most common, but schema should work with PostgreSQL (host app responsibility).

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| None required | - | - | Plugin is self-contained, uses only CakePHP core + auth ecosystem |

**Note:** NO additional dependencies. Cache backend uses CakePHP's Cache component (host app configures). No custom UI framework - use CakePHP's default bake templates.

### Development Tools

| Tool | Version | Purpose | Notes |
|------|---------|---------|-------|
| phpunit/phpunit | ^10.0 | Testing framework | CakePHP 5 standard test framework |
| cakephp/cakephp-codesniffer | ^5.0 | Code standards | Official CakePHP coding standards (PSR-12 + CakePHP conventions) |
| cakephp/bake | ^3.0 | Code generation | Generate admin UI templates, controllers (dev dependency) |
| cakephp/debug_kit | ^5.0 | Debugging | Development toolbar (dev dependency, optional) |

## Integration Architecture

### Authorization Flow

```
Request → AuthenticationMiddleware → AuthorizationMiddleware → RbacMiddleware (ours)
                ↓                              ↓                        ↓
         CakeDC/Users              CakePHP Authorization       DB Permission Checker
         (identity)                (framework)                 (our custom resolver)
```

**Key Integration Points:**

1. **Authorization Service Loader** - Custom loader replaces CakeDC/Auth's RbacPolicy with our DatabaseRbacResolver
2. **Middleware Queue** - Plugin provides its own middleware that hooks into CakePHP Authorization
3. **Policy Resolver** - Custom resolver that queries database instead of reading config files
4. **Identity Integration** - Works with any Authentication identity, checks permissions via `$identity->can()`

### Database Resolver Pattern

Based on CakeDC/Auth's RbacPolicy pattern, extended for database:

```php
// CakeDC/Auth pattern (config-based):
$resolver = new ResolverCollection([
    new MapResolver([
        ServerRequest::class => new CollectionPolicy([
            SuperuserPolicy::class,
            new RbacPolicy(['permissions' => $configArray])
        ])
    ])
]);

// Our pattern (database-based):
$resolver = new ResolverCollection([
    new MapResolver([
        ServerRequest::class => new CollectionPolicy([
            SuperuserPolicy::class,  // Keep superuser bypass
            new DatabaseRbacResolver()  // Our custom resolver
        ])
    ])
]);
```

**Why this approach:**
- Follows CakeDC ecosystem conventions (SuperuserPolicy, CollectionPolicy pattern)
- Minimal divergence from standard CakeDC/Users + Authorization setup
- Host apps can still add their own policies to the collection
- Superadmin bypass works identically to CakeDC/Auth

## Database Schema Design

### Tables

**roles**
- `id` (int, PK, auto-increment)
- `name` (string 100, unique, indexed)
- `description` (text, nullable)
- `is_active` (boolean, default true)
- `created` (datetime)
- `modified` (datetime)

**permissions**
- `id` (int, PK, auto-increment)
- `name` (string 255, unique, indexed)
- `description` (text, nullable)
- `controller` (string 100, nullable, indexed) - for controller/action-based
- `action` (string 100, nullable, indexed) - for controller/action-based
- `plugin` (string 100, nullable) - for plugin routes
- `prefix` (string 100, nullable) - for prefix routes
- `is_active` (boolean, default true)
- `created` (datetime)
- `modified` (datetime)
- **Composite index:** `(controller, action, plugin, prefix)` for fast lookups

**roles_permissions** (pivot)
- `id` (int, PK, auto-increment)
- `role_id` (int, FK to roles, indexed)
- `permission_id` (int, FK to permissions, indexed)
- `created` (datetime)
- **Unique constraint:** `(role_id, permission_id)`

**users_roles** (pivot)
- `id` (int, PK, auto-increment)
- `user_id` (uuid, FK to users, indexed) - UUID because CakeDC/Users uses UUIDs
- `role_id` (int, FK to roles, indexed)
- `created` (datetime)
- **Unique constraint:** `(user_id, role_id)` - only if multi-role enabled
- **Note:** Single-role mode enforced at service layer, not database

**users_permissions** (pivot, direct user permissions)
- `id` (int, PK, auto-increment)
- `user_id` (uuid, FK to users, indexed)
- `permission_id` (int, FK to permissions, indexed)
- `created` (datetime)
- **Unique constraint:** `(user_id, permission_id)`

**Why integers for most IDs:** Fast joins, smaller indexes. UUIDs only for `user_id` to match CakeDC/Users schema.

**Why nullable controller/action:** Permissions can be string-based (`settings.view`) OR controller/action-based (`Settings::view`). Nullable fields support both patterns.

### Migration Strategy

**Approach:** Single initial migration + incremental feature migrations

```
config/Migrations/
├── 20260204000001_CreateRbacTables.php     # Core schema (roles, permissions, pivots)
├── 20260204000002_AddRbacIndexes.php       # Performance indexes
└── 20260204000003_SeedDefaultRoles.php     # Optional seed data
```

**Migration patterns:**
- Use `change()` method for reversible operations
- Use `up()`/`down()` only when rollback logic differs
- Foreign keys with `CASCADE DELETE` for clean orphan removal
- Timestamps use CakePHP's `Timestamp` behavior convention

**Installation command:**
```bash
bin/cake migrations migrate --plugin=Rbac
```

## Configuration Approach

### Plugin Configuration (config/rbac.php in host app)

```php
return [
    'Rbac' => [
        // Role configuration
        'multiRoles' => false,  // Single role per user by default
        'superadminRole' => 'superadmin',  // Bypass all checks

        // Permission checking
        'cache' => [
            'enabled' => true,
            'config' => 'default',  // Use host app's cache config
            'prefix' => 'rbac_',
            'duration' => '+1 hour',
        ],

        // Auto-discovery
        'discovery' => [
            'enabled' => true,  // Auto-register controller/action permissions
            'skipPrefixes' => ['Admin'],  // Don't auto-register these
        ],

        // Authorization integration
        'Authorization' => [
            'enable' => true,  // Enable Authorization middleware
            'skipCallback' => null,  // Callable to skip authorization for specific requests
        ],
    ],
];
```

**Why config over database:**
- Runtime toggles (multiRoles, cache) shouldn't require migrations
- Cache config references host app's cache setup
- Discovery settings are developer-facing, not admin-facing

### Cache Invalidation Strategy

**When to invalidate:**
- Role created/updated/deleted → clear all `rbac_*` keys
- Permission created/updated/deleted → clear all `rbac_*` keys
- User role assigned/revoked → clear `rbac_user_{$userId}` key
- User permission assigned/revoked → clear `rbac_user_{$userId}` key

**Cache key patterns:**
```
rbac_user_{$userId}              → User's full permission set
rbac_role_{$roleId}              → Role's permissions
rbac_permission_{$controller}_{$action}  → Permission lookup
```

**Implementation:** Use CakePHP's `Cache::delete()` with event listeners on Table models.

## Testing Approach

### Test Structure

```
tests/
├── TestCase/
│   ├── Controller/
│   │   ├── RolesControllerTest.php        # Admin UI tests
│   │   ├── PermissionsControllerTest.php
│   │   └── UsersRolesControllerTest.php
│   ├── Model/
│   │   ├── Table/
│   │   │   ├── RolesTableTest.php
│   │   │   ├── PermissionsTableTest.php
│   │   │   └── UsersRolesTableTest.php
│   │   └── Behavior/
│   │       └── RbacBehaviorTest.php       # If we add a behavior
│   ├── View/
│   │   └── Helper/
│   │       └── RbacHelperTest.php         # Permission checks in views
│   ├── Policy/
│   │   └── DatabaseRbacResolverTest.php   # Core authorization logic
│   ├── Service/
│   │   └── RbacServiceTest.php            # Manual permission checks
│   └── Integration/
│       ├── AuthorizationFlowTest.php      # End-to-end auth tests
│       └── CacheInvalidationTest.php      # Cache behavior tests
├── Fixture/
│   ├── RolesFixture.php
│   ├── PermissionsFixture.php
│   ├── UsersRolesFixture.php
│   ├── UsersPermissionsFixture.php
│   └── UsersFixture.php                   # From CakeDC/Users
└── bootstrap.php
```

### Testing Patterns

**Controller tests** - Use `IntegrationTestTrait`
```php
class RolesControllerTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'plugin.Rbac.Roles',
        'plugin.Rbac.Permissions',
        'plugin.CakeDC/Users.Users',
    ];

    public function testIndexRequiresPermission(): void
    {
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 'user-without-permission']]);

        $this->get('/admin/rbac/roles');
        $this->assertResponseCode(403);
    }
}
```

**Model tests** - Use `TestCase`
```php
class RolesTableTest extends TestCase
{
    protected array $fixtures = ['plugin.Rbac.Roles'];

    public function testValidationFailsForDuplicateName(): void
    {
        $role = $this->Roles->newEntity(['name' => 'admin']);
        $result = $this->Roles->save($role);
        $this->assertFalse($result);
        $this->assertNotEmpty($role->getError('name'));
    }
}
```

**Authorization tests** - Integration testing with mocked identity
```php
class AuthorizationFlowTest extends IntegrationTestCase
{
    public function testSuperadminBypassesAllChecks(): void
    {
        $user = $this->Users->get('superadmin-uuid');
        $this->session(['Auth' => $user]);

        $this->get('/admin/rbac/permissions/delete/1');
        $this->assertResponseOk();  // No explicit permission needed
    }
}
```

### Test Coverage Goals

- **Core authorization logic:** 100% (DatabaseRbacResolver, RbacService)
- **Models:** 95% (validation, associations, finders)
- **Controllers:** 85% (admin UI, CRUD operations)
- **Integration:** 90% (middleware, cache, identity integration)

## Installation & Setup

### Composer Installation

```bash
composer require cakephpmitra/rbac:^1.0
```

Auto-installs via cakephp/plugin-installer.

### Host App Setup

**1. Load plugin in Application.php:**
```php
public function bootstrap(): void
{
    parent::bootstrap();

    // Load RBAC plugin
    $this->addPlugin('Rbac', [
        'bootstrap' => true,  // Load plugin bootstrap
        'routes' => true,     // Load admin routes
    ]);
}
```

**2. Run migrations:**
```bash
bin/cake migrations migrate --plugin=Rbac
```

**3. Configure authorization (if not using default):**
```php
// config/rbac.php (optional)
return [
    'Rbac' => [
        'multiRoles' => true,  // Enable multiple roles per user
        'superadminRole' => 'admin',  // Change superadmin role name
    ],
];
```

**4. Seed default data:**
```bash
bin/cake rbac seed  # Creates superadmin, admin, user roles + common permissions
```

### Plugin Bootstrap (src/Plugin.php)

```php
class Plugin extends BasePlugin
{
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        // Load default config
        Configure::load('Rbac.rbac', 'default', false);

        // Register event listeners for cache invalidation
        $eventManager = EventManager::instance();
        $eventManager->on(new RbacCacheInvalidationListener());
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Add our authorization service loader to replace default
        // This happens in MiddlewareQueueLoader similar to CakeDC/Users pattern
        return $middlewareQueue;
    }
}
```

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Authorization | cakephp/authorization ^3.0 + custom resolver | Build from scratch | Official library provides middleware, policies, identity integration - no need to reinvent |
| User Identity | cakedc/users ^16.0 | cakephp/authentication only | CakeDC/Users is already project requirement, provides OAuth, 2FA, user management |
| RBAC Pattern | Extend CakeDC/Auth patterns | casbin/casbin-php | CakeDC/Auth is CakePHP-native, already integrated with Users plugin, simpler for ecosystem |
| Database IDs | Integer PKs for roles/permissions | UUIDs everywhere | Integer PKs are faster for joins, smaller indexes; UUIDs only needed for user_id compatibility |
| Permissions Storage | Both string-based + controller/action | Controller/action only | Flexibility for custom permissions like `settings.view` that don't map to routes |
| Multi-role | Configurable (default single) | Always multi-role | Single role is simpler, covers 80% of use cases; multi-role adds complexity |
| Cache Strategy | CakePHP Cache component | Redis-specific or custom | Host app controls cache backend; plugin shouldn't force specific cache engine |

## Version Compatibility Matrix

| CakePHP | PHP | CakeDC/Users | CakePHP Authorization | Status |
|---------|-----|--------------|----------------------|--------|
| 5.0.x   | 8.1+ | 16.0.x       | 3.0.x                | Tested |
| 5.1.x   | 8.1+ | 16.0.x       | 3.0.x                | Compatible |
| 5.2.x   | 8.1+ | 16.0.x       | 3.0.x                | Compatible |
| 5.3.x   | 8.2+ | 16.0.x       | 3.0.x                | Compatible |
| 4.x     | -    | -            | -                    | Not supported |

**Support policy:** Target current stable CakePHP 5.x. No backward compatibility with CakePHP 4.

## Sources & References

**HIGH CONFIDENCE sources:**

1. **CakeDC/Users 16.0.0** - `/vendor/cakedc/users/src/Loader/`
   - AuthorizationServiceLoader.php - Shows ResolverCollection + MapResolver + RbacPolicy pattern
   - MiddlewareQueueLoader.php - Shows middleware integration pattern

2. **CakeDC/Auth 10.1.4** - `/vendor/cakedc/auth/src/`
   - Policy/RbacPolicy.php - Config-based RBAC pattern we're extending
   - Rbac/Rbac.php - Permission checking logic (ConfigProvider → we'll make DatabaseProvider)

3. **CloudPE Admin** - Working reference implementation
   - Uses CakeDC/Users 16.0 + CakePHP Authorization 3.0
   - Custom MiddlewareQueueLoader extends CakeDC base loader
   - Permissions defined in config/permissions.php (we'll replace with DB)

4. **CakePHP Documentation** - Official patterns
   - Migrations: Phinx-based migration patterns
   - Authorization: Middleware, resolvers, policies
   - Testing: IntegrationTestTrait, fixtures, TestCase patterns

**Pattern confidence:**
- Authorization integration: HIGH (verified in CloudPE Admin + CakeDC/Users source)
- Database schema: HIGH (standard many-to-many with pivot tables, indexed for performance)
- CakeDC ecosystem compatibility: HIGH (follows CakeDC/Auth patterns exactly)
- Migration patterns: HIGH (CakePHP Migrations is standard, verified in CloudPE Admin)

**Unknowns requiring validation during development:**
- Performance at scale (thousands of permissions) - may need query optimization
- Cache hit ratio in production - tuning may be needed
- Multi-role edge cases - potential conflicts when user has overlapping permissions
- Plugin route prefix conflicts - need to test with host apps using different route patterns
