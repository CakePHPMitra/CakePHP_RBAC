# Architecture Patterns for CakePHP 5 RBAC Plugin

**Domain:** CakePHP 5 Authorization/RBAC Plugin
**Researched:** 2026-02-04
**Confidence:** HIGH (verified via CakeDC/Users, CakePHP Authorization source code examination)

## Executive Summary

CakePHP 5 RBAC plugins integrate with the **CakePHP Authorization** library (^3.0) ecosystem. The architecture follows a layered pattern where Authorization middleware injects services into requests, custom resolvers map resources to policies, and policies make authorization decisions based on database-backed rules.

**Key architectural principles:**
1. **Integration over replacement** - Extend Authorization framework, don't rebuild it
2. **Database-backed resolver** - Custom resolver replaces file-based policy classes
3. **Service-oriented** - RbacService provides manual checks outside request cycle
4. **Middleware-driven** - Automatic enforcement via Authorization middleware stack
5. **Cache-first** - Permission checks cached per request to minimize DB queries

## Recommended Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                          Host Application                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  HTTP Request                                                         │
│      ↓                                                                │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │ Middleware Stack (Application.php)                          │    │
│  │  1. ErrorHandlerMiddleware                                  │    │
│  │  2. RoutingMiddleware                                       │    │
│  │  3. AuthenticationMiddleware (CakeDC/Users)                 │    │
│  │  4. AuthorizationMiddleware (CakePHP Authorization)         │    │
│  │     - Injects AuthorizationService as request attribute     │    │
│  │     - Wraps identity with can() decorator                   │    │
│  │  5. RequestAuthorizationMiddleware (optional auto-check)    │    │
│  └──────────────────────────┬──────────────────────────────────┘    │
│                             ↓                                         │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │ Controller Layer                                            │    │
│  │  - $this->Authorization->authorize($request)                │    │
│  │  - $this->Identity->can('action', $resource)                │    │
│  │  - AuthorizationComponent->skipAuthorization()              │    │
│  └──────────────────────────┬──────────────────────────────────┘    │
│                                                                       │
└───────────────────────────────┬───────────────────────────────────────┘
                                ↓
┌─────────────────────────────────────────────────────────────────────┐
│                    CakePHPMitra/RBAC Plugin                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ Plugin Bootstrap (Plugin.php)                                │   │
│  │  - Load middleware via middleware()                          │   │
│  │  - Register services via services()                          │   │
│  │  - Register event listeners via bootstrap()                 │   │
│  └────────────────────────────┬─────────────────────────────────┘   │
│                               ↓                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ AuthorizationServiceLoader                                   │   │
│  │  - Creates AuthorizationService                              │   │
│  │  - Configures ResolverCollection with:                       │   │
│  │    1. MapResolver (for ServerRequest → RbacPolicy)           │   │
│  │    2. DatabaseResolver (custom - for DB-backed permissions)  │   │
│  │    3. OrmResolver (fallback for entity-level policies)       │   │
│  └────────────────────────────┬─────────────────────────────────┘   │
│                               ↓                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ DatabaseResolver (custom policy resolver)                    │   │
│  │  - Implements ResolverInterface                              │   │
│  │  - Maps ServerRequest → RbacPolicy                           │   │
│  │  - Provides policy instance to AuthorizationService          │   │
│  └────────────────────────────┬─────────────────────────────────┘   │
│                               ↓                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ RbacPolicy (implements RequestPolicyInterface)               │   │
│  │  - canAccess(IdentityInterface, ServerRequest): bool         │   │
│  │  - Delegates to RbacService for permission checks            │   │
│  │  - Handles superadmin bypass                                 │   │
│  └────────────────────────────┬─────────────────────────────────┘   │
│                               ↓                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ RbacService (core business logic)                            │   │
│  │  - can(user, controller, action): bool                       │   │
│  │  - getUserPermissions(userId): array (cached)                │   │
│  │  - getUserRoles(userId): array (cached)                      │   │
│  │  - checkPermission(permissions, controller, action): bool    │   │
│  │  - Cache management (invalidation on permission changes)     │   │
│  └────────────────────────────┬─────────────────────────────────┘   │
│                               ↓                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ Model Layer (ORM Tables)                                     │   │
│  │  ┌────────────────┬──────────────────┬───────────────────┐   │   │
│  │  │ RolesTable     │ PermissionsTable │ UsersRolesTable   │   │   │
│  │  ├────────────────┼──────────────────┼───────────────────┤   │   │
│  │  │ - findActive   │ - findByType     │ - findByUser      │   │   │
│  │  │ - getName      │ - buildKey       │ - assignRole      │   │   │
│  │  └────────────────┴──────────────────┴───────────────────┘   │   │
│  │  ┌────────────────────────┬──────────────────────────────┐   │   │
│  │  │ UsersPermissionsTable  │ RolesPermissionsTable        │   │   │
│  │  ├────────────────────────┼──────────────────────────────┤   │   │
│  │  │ - findByUser           │ - findByRole                 │   │   │
│  │  │ - grantPermission      │ - assignPermissionToRole     │   │   │
│  │  └────────────────────────┴──────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ View Layer                                                   │   │
│  │  ┌───────────────────────┬──────────────────────────────┐   │   │
│  │  │ RbacHelper            │ Admin UI Templates           │   │   │
│  │  ├───────────────────────┼──────────────────────────────┤   │   │
│  │  │ - can($action)        │ - roles/index.php            │   │   │
│  │  │ - hasRole($role)      │ - permissions/index.php      │   │   │
│  │  │ - ifCan() wrapper     │ - matrix/index.php (grid)    │   │   │
│  │  └───────────────────────┴──────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ Event Listeners                                              │   │
│  │  - CacheInvalidationListener (on role/permission changes)    │   │
│  │  - PermissionDiscoveryListener (on route cache clear)        │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                       │
└───────────────────────────────────────────────────────────────────────┘
```

## Component Boundaries

### 1. Plugin Bootstrap Layer

**Responsibility:** Register plugin with CakePHP application lifecycle

**File:** `src/Plugin.php`

**Interfaces:**
- Extends `Cake\Core\BasePlugin`
- Implements `middleware()`, `bootstrap()`, `services()`, `routes()`

**Key Methods:**
```php
public function middleware(MiddlewareQueue $queue): MiddlewareQueue
{
    // Conditionally add RequestAuthorizationMiddleware
    // Only if config('Rbac.autoCheck') === true
}

public function bootstrap(BootstrapInterface $app): void
{
    // Load config/rbac.php
    // Register event listeners
    // Load plugin services
}

public function services(ContainerInterface $container): void
{
    // Register RbacService for DI
    // Register DatabaseResolver
    // Register AuthorizationServiceLoader
}
```

**Communicates With:**
- Application bootstrap chain
- Event manager (to register listeners)
- DI Container (to register services)

---

### 2. Authorization Service Layer

**Responsibility:** Bridge between CakePHP Authorization framework and RBAC logic

**Files:**
- `src/Provider/AuthorizationServiceProvider.php`
- `src/Loader/AuthorizationServiceLoader.php`

**Pattern:** Service Provider Pattern (PSR-11 compatible)

**Key Implementation:**
```php
class AuthorizationServiceLoader
{
    public function __invoke(ServerRequestInterface $request)
    {
        $map = new MapResolver();
        $map->map(
            ServerRequest::class,
            new RbacPolicy()  // Custom policy for request authorization
        );

        $database = new DatabaseResolver();  // Custom resolver
        $orm = new OrmResolver();  // CakePHP default for entities

        $resolver = new ResolverCollection([
            $map,      // Priority 1: Request-level permissions
            $database, // Priority 2: Database-backed entity/resource permissions
            $orm,      // Priority 3: Fallback to convention-based policies
        ]);

        return new AuthorizationService($resolver);
    }
}
```

**Communicates With:**
- AuthorizationMiddleware (provides service instance)
- Resolvers (delegates policy resolution)
- Plugin configuration (reads config/rbac.php)

---

### 3. Policy Resolver Layer

**Responsibility:** Map resources to authorization policies

#### DatabaseResolver (Custom)

**File:** `src/Policy/DatabaseResolver.php`

**Implements:** `Authorization\Policy\ResolverInterface`

**Purpose:** Resolve database-backed policies for runtime permission checks

**Key Methods:**
```php
public function getPolicy(mixed $resource): mixed
{
    // Handle ServerRequest (controller/action checks)
    if ($resource instanceof ServerRequestInterface) {
        return new RbacPolicy();
    }

    // Handle ORM entities with permission attributes
    if ($resource instanceof EntityInterface) {
        return new EntityPermissionPolicy();
    }

    throw new MissingPolicyException();
}
```

**Communicates With:**
- AuthorizationService (called during `can()` checks)
- RbacPolicy (returns policy instances)

---

### 4. Policy Layer

**Responsibility:** Make authorization decisions

#### RbacPolicy (Request-level)

**File:** `src/Policy/RbacPolicy.php`

**Implements:** `Authorization\Policy\RequestPolicyInterface`

**Key Methods:**
```php
public function canAccess(?IdentityInterface $identity, ServerRequestInterface $request): bool
{
    // Extract controller/action from request
    $controller = $request->getParam('controller');
    $action = $request->getParam('action');
    $plugin = $request->getParam('plugin');

    // Get user from identity
    $user = $identity ? $identity->getOriginalData() : null;

    // Delegate to RbacService
    return $this->rbacService->can($user, $controller, $action, $plugin);
}
```

**Communicates With:**
- AuthorizationService (invoked during authorization checks)
- RbacService (delegates permission logic)

---

### 5. Service Layer (Core Business Logic)

**Responsibility:** Permission checking, caching, and business rules

#### RbacService

**File:** `src/Service/RbacService.php`

**Purpose:** Centralized permission checking logic, reusable outside request cycle

**Key Methods:**
```php
class RbacService
{
    public function can(
        ?array $user,
        string $controller,
        string $action,
        ?string $plugin = null
    ): bool {
        // 1. Check superadmin bypass
        if ($this->isSuperadmin($user)) {
            return true;
        }

        // 2. Get cached user permissions
        $permissions = $this->getUserPermissions($user['id']);

        // 3. Check permission match
        return $this->checkPermission($permissions, $controller, $action, $plugin);
    }

    public function getUserPermissions(string $userId): array
    {
        // Cache key: rbac_user_permissions_{userId}
        return Cache::remember("rbac_user_permissions_{$userId}", function() use ($userId) {
            $roles = $this->getUserRoles($userId);
            $directPerms = $this->UsersPermissions->findByUser($userId)->all();
            $rolePerms = $this->RolesPermissions->findByRoles($roles)->all();

            return array_merge($directPerms, $rolePerms);
        });
    }

    public function invalidateUserCache(string $userId): void
    {
        Cache::delete("rbac_user_permissions_{$userId}");
        Cache::delete("rbac_user_roles_{$userId}");
    }

    protected function isSuperadmin(?array $user): bool
    {
        if (!$user) return false;

        $superadminRole = Configure::read('Rbac.superadminRole', 'superadmin');
        $roles = $this->getUserRoles($user['id']);

        return in_array($superadminRole, $roles, true);
    }

    protected function checkPermission(
        array $permissions,
        string $controller,
        string $action,
        ?string $plugin
    ): bool {
        // Check for exact match
        $key = $this->buildPermissionKey($controller, $action, $plugin);

        foreach ($permissions as $perm) {
            if ($perm->permission_key === $key) {
                return true;
            }

            // Check wildcard matches
            if ($this->matchesWildcard($perm->permission_key, $key)) {
                return true;
            }
        }

        return false;
    }
}
```

**Communicates With:**
- RbacPolicy (called during authorization)
- Model layer (fetches roles/permissions)
- Cache layer (reads/writes permission cache)
- Configuration (reads superadmin role)

---

### 6. Model Layer (Database Access)

**Responsibility:** ORM interactions, data validation, associations

#### Tables Structure

| Table | Associations | Key Methods |
|-------|--------------|-------------|
| **RolesTable** | hasMany UsersRoles, hasMany RolesPermissions | `findActive()`, `findBySlug()` |
| **PermissionsTable** | hasMany UsersPermissions, hasMany RolesPermissions | `findByType()`, `buildKey()`, `parseKey()` |
| **UsersRolesTable** | belongsTo Users, belongsTo Roles | `findByUser()`, `assignRole()`, `removeRole()` |
| **UsersPermissionsTable** | belongsTo Users, belongsTo Permissions | `findByUser()`, `grantPermission()` |
| **RolesPermissionsTable** | belongsTo Roles, belongsTo Permissions | `findByRole()`, `assignToRole()` |

#### Key Patterns

**Soft validation in tables:**
```php
class PermissionsTable extends Table
{
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->scalar('permission_key')
            ->maxLength('permission_key', 255)
            ->requirePresence('permission_key', 'create')
            ->notEmptyString('permission_key')
            ->add('permission_key', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
            ]);
    }

    // Custom finder for permission type
    public function findByType(Query $query, array $options): Query
    {
        $type = $options['type'] ?? 'action'; // 'action' or 'string'
        return $query->where(['type' => $type]);
    }
}
```

**Communicates With:**
- RbacService (queried for permission data)
- Event listeners (triggers on save/delete)

---

### 7. View Layer

**Responsibility:** Template rendering, permission checks in views

#### RbacHelper

**File:** `src/View/Helper/RbacHelper.php`

**Extends:** `Cake\View\Helper`

**Key Methods:**
```php
class RbacHelper extends Helper
{
    public function can(string $action, ?string $controller = null): bool
    {
        $identity = $this->getView()->getRequest()->getAttribute('identity');
        $controller = $controller ?? $this->getView()->getRequest()->getParam('controller');

        return $identity->can($action, $this->buildRequest($controller, $action));
    }

    public function hasRole(string $role): bool
    {
        $identity = $this->getView()->getRequest()->getAttribute('identity');
        $user = $identity ? $identity->getOriginalData() : null;

        $roles = $this->rbacService->getUserRoles($user['id']);
        return in_array($role, $roles, true);
    }

    // Template wrapper for cleaner syntax
    public function ifCan(string $action, callable $callback): ?string
    {
        if ($this->can($action)) {
            return $callback();
        }
        return null;
    }
}
```

**Usage in templates:**
```php
// In templates/Users/index.php
<?php if ($this->Rbac->can('edit', 'Users')): ?>
    <?= $this->Html->link('Edit', ['action' => 'edit', $user->id]) ?>
<?php endif; ?>

// Or with wrapper
<?= $this->Rbac->ifCan('delete', fn() =>
    $this->Form->postLink('Delete', ['action' => 'delete', $user->id])
) ?>
```

**Communicates With:**
- Request identity (via `$request->getAttribute('identity')`)
- RbacService (for role checks)
- Authorization service (via identity decorator)

---

### 8. Event Listener Layer

**Responsibility:** React to system events for cache invalidation and discovery

#### CacheInvalidationListener

**File:** `src/Event/CacheInvalidationListener.php`

**Implements:** `Cake\Event\EventListenerInterface`

```php
class CacheInvalidationListener implements EventListenerInterface
{
    public function implementedEvents(): array
    {
        return [
            'Model.afterSave' => 'invalidateCache',
            'Model.afterDelete' => 'invalidateCache',
        ];
    }

    public function invalidateCache(EventInterface $event): void
    {
        $table = $event->getSubject();
        $entity = $event->getData('entity');

        // Invalidate affected users when roles/permissions change
        if ($table instanceof UsersRolesTable) {
            $this->rbacService->invalidateUserCache($entity->user_id);
        }

        if ($table instanceof RolesPermissionsTable) {
            $this->invalidateRoleUsers($entity->role_id);
        }
    }
}
```

**Communicates With:**
- Model layer (listens to save/delete events)
- RbacService (calls invalidation methods)

---

## Data Flow

### Request Authorization Flow

```
1. HTTP Request arrives
   ↓
2. AuthenticationMiddleware sets identity
   ↓
3. AuthorizationMiddleware injects AuthorizationService
   ↓
4. RequestAuthorizationMiddleware calls authorize($request)
   ↓
5. AuthorizationService->can($identity, $request)
   ↓
6. ResolverCollection->getPolicy($request)
   ↓
7. DatabaseResolver->getPolicy($request) → RbacPolicy
   ↓
8. RbacPolicy->canAccess($identity, $request)
   ↓
9. RbacService->can($user, $controller, $action)
   ↓
10. RbacService->getUserPermissions($userId) [CACHED]
    ↓
11. Cache hit? → Return cached permissions
    Cache miss? → Query database → Cache result
    ↓
12. RbacService->checkPermission($permissions, $controller, $action)
    ↓
13. Return boolean → Policy → Service → Middleware
    ↓
14. If false → Throw ForbiddenException
    If true → Continue to controller
```

### Manual Permission Check Flow (via RbacService)

```
1. Controller/Command/Job needs permission check
   ↓
2. $rbacService = $this->fetchTable('CakePHPMitra/Rbac.Roles')->getRbacService()
   // Or via DI: RbacService injected in constructor
   ↓
3. $rbacService->can($user, 'Users', 'edit')
   ↓
4. Same flow as #9-13 above
```

### Cache Invalidation Flow

```
1. Admin assigns role to user
   ↓
2. UsersRolesTable->save($userRole)
   ↓
3. Model.afterSave event fired
   ↓
4. CacheInvalidationListener->invalidateCache($event)
   ↓
5. Extract user_id from entity
   ↓
6. RbacService->invalidateUserCache($userId)
   ↓
7. Cache::delete("rbac_user_permissions_{$userId}")
   Cache::delete("rbac_user_roles_{$userId}")
   ↓
8. Next request for this user will rebuild cache
```

---

## CakePHP Authorization Integration Points

### 1. Middleware Registration

**Where:** Plugin's `middleware()` method or host app's `Application::middleware()`

```php
// In plugin
public function middleware(MiddlewareQueue $queue): MiddlewareQueue
{
    // Only add if config enabled
    if (Configure::read('Rbac.autoCheck', true)) {
        $queue->add(new RequestAuthorizationMiddleware());
    }
    return $queue;
}

// In host app - plugin handles this automatically
// No manual middleware registration needed unless customizing
```

### 2. Service Provider Registration

**Where:** Plugin's `bootstrap()` method

```php
public function bootstrap(BootstrapInterface $app): void
{
    // Configure how AuthorizationService is created
    Configure::write('Auth.Authorization.serviceLoader', [
        'className' => AuthorizationServiceLoader::class,
    ]);
}
```

### 3. Identity Decorator Integration

**Automatic via Authorization middleware:**

```php
// The middleware wraps identity with can() method
$identity = $request->getAttribute('identity');
$identity->can('edit', $user);  // Calls AuthorizationService->can()

// In controllers (via AuthorizationComponent)
$this->Authorization->authorize($request);  // For request-level check
```

### 4. Policy Resolution Order

CakePHP Authorization's `ResolverCollection` tries resolvers in order:

```php
// Priority 1: MapResolver (ServerRequest → RbacPolicy)
$map->map(ServerRequest::class, new RbacPolicy());

// Priority 2: DatabaseResolver (custom - for DB permissions)
// Only invoked if MapResolver returns null

// Priority 3: OrmResolver (convention-based Entity policies)
// Looks for App\Policy\UserPolicy for App\Model\Entity\User
```

**When to use which:**
- **MapResolver:** Request-level authorization (controller/action)
- **DatabaseResolver:** Resource-level with DB-backed rules (future: "can edit THIS article")
- **OrmResolver:** Entity ownership checks (current user owns entity)

---

## Plugin File Structure

Following CakePHP conventions:

```
CakePHPMitra/RBAC/
├── config/
│   ├── rbac.php                    # Plugin configuration
│   ├── bootstrap.php               # Plugin bootstrap (loads config)
│   ├── routes.php                  # Admin UI routes
│   └── Migrations/
│       ├── 20260204000001_CreateRoles.php
│       ├── 20260204000002_CreatePermissions.php
│       ├── 20260204000003_CreateUsersRoles.php
│       ├── 20260204000004_CreateUsersPermissions.php
│       └── 20260204000005_CreateRolesPermissions.php
├── src/
│   ├── Plugin.php                  # Plugin bootstrap class
│   ├── Controller/
│   │   ├── Admin/
│   │   │   ├── RolesController.php
│   │   │   ├── PermissionsController.php
│   │   │   ├── MatrixController.php
│   │   │   └── UsersController.php (role/perm assignment)
│   ├── Model/
│   │   ├── Table/
│   │   │   ├── RolesTable.php
│   │   │   ├── PermissionsTable.php
│   │   │   ├── UsersRolesTable.php
│   │   │   ├── UsersPermissionsTable.php
│   │   │   └── RolesPermissionsTable.php
│   │   └── Entity/
│   │       ├── Role.php
│   │       ├── Permission.php
│   │       ├── UserRole.php
│   │       ├── UserPermission.php
│   │       └── RolePermission.php
│   ├── Policy/
│   │   ├── DatabaseResolver.php    # Custom resolver
│   │   └── RbacPolicy.php          # Request authorization policy
│   ├── Service/
│   │   └── RbacService.php         # Core permission logic
│   ├── Loader/
│   │   └── AuthorizationServiceLoader.php
│   ├── Provider/
│   │   └── AuthorizationServiceProvider.php
│   ├── Event/
│   │   ├── CacheInvalidationListener.php
│   │   └── PermissionDiscoveryListener.php
│   ├── View/
│   │   └── Helper/
│   │       └── RbacHelper.php
│   └── Command/
│       ├── DiscoverPermissionsCommand.php
│       └── SyncPermissionsCommand.php
├── templates/
│   ├── Admin/
│   │   ├── Roles/
│   │   │   ├── index.php
│   │   │   ├── add.php
│   │   │   ├── edit.php
│   │   │   └── view.php
│   │   ├── Permissions/
│   │   │   ├── index.php
│   │   │   ├── add.php
│   │   │   ├── edit.php
│   │   │   └── view.php
│   │   ├── Matrix/
│   │   │   └── index.php (role-permission grid)
│   │   └── Users/
│   │       ├── roles.php (assign roles to user)
│   │       └── permissions.php (assign direct perms)
│   └── element/
│       └── Admin/
│           └── sidebar.php
├── tests/
│   ├── TestCase/
│   │   ├── Controller/
│   │   ├── Model/
│   │   ├── Policy/
│   │   └── Service/
│   └── Fixture/
├── webroot/
│   └── css/
│       └── rbac-admin.css (minimal styling)
└── README.md
```

---

## Suggested Build Order (Dependency-Based)

### Phase 1: Foundation (Database + Core Models)
**Why first:** Everything depends on database schema and ORM

1. Migrations (all 5 tables)
2. Entity classes (5 entities)
3. Table classes (5 tables with associations)
4. Seed data (superadmin, admin, user roles + basic permissions)

**Validation:** Can query roles/permissions via bake console

---

### Phase 2: Service Layer (Business Logic)
**Why second:** Policy and helpers depend on this

1. RbacService (core permission checking logic)
2. Unit tests for RbacService
3. Cache integration
4. Superadmin bypass logic

**Validation:** Can call `RbacService->can()` in tests

---

### Phase 3: Authorization Integration (Policies + Resolvers)
**Why third:** Middleware depends on this

1. RbacPolicy (RequestPolicyInterface implementation)
2. DatabaseResolver (ResolverInterface implementation)
3. AuthorizationServiceLoader
4. AuthorizationServiceProvider
5. Plugin.php (services() registration)

**Validation:** Authorization checks work via `$identity->can()`

---

### Phase 4: Middleware + Plugin Bootstrap
**Why fourth:** Admin UI depends on authorization working

1. Plugin.php (middleware(), bootstrap())
2. config/rbac.php (plugin configuration)
3. Event listeners (cache invalidation)
4. Integration tests for request authorization

**Validation:** Requests are authorized automatically

---

### Phase 5: View Layer (Helpers + Templates)
**Why fifth:** Admin UI uses helpers

1. RbacHelper
2. Admin layout (if needed, or use app layout)
3. Basic template structure

**Validation:** `$this->Rbac->can()` works in templates

---

### Phase 6: Admin UI (Controllers + Templates)
**Why sixth:** Depends on all previous layers

1. RolesController (CRUD)
2. PermissionsController (CRUD)
3. MatrixController (role-permission grid)
4. UsersController (role/permission assignment)
5. Routes (admin prefix routing)
6. Templates (using CakePHP default layout)

**Validation:** Full CRUD via browser

---

### Phase 7: Commands + Discovery
**Why last:** Optional tooling, depends on everything

1. DiscoverPermissionsCommand (scan routes)
2. SyncPermissionsCommand (update DB from routes)
3. CLI tests

**Validation:** `bin/cake rbac discover` works

---

## Build Dependencies Diagram

```
Phase 1: Database + Models
         ↓
Phase 2: Service Layer ←────────┐
         ↓                      │
Phase 3: Policies + Resolvers   │
         ↓                      │
Phase 4: Middleware + Bootstrap │
         ↓                      │
Phase 5: View Helpers           │
         ↓                      │
Phase 6: Admin UI (Controllers) │
         ↓                      │
Phase 7: Commands ──────────────┘
```

**Critical path:** 1 → 2 → 3 → 4 (minimum for plugin to function)
**Full feature:** 1 → 2 → 3 → 4 → 5 → 6 (admin UI)
**Optional tooling:** 7 (can be deferred)

---

## Architecture Patterns to Follow

### Pattern 1: Service Locator via DI Container

**What:** RbacService registered in container, injected where needed

**When:** Controllers, Commands, Listeners need permission checks

**Example:**
```php
// In Plugin.php
public function services(ContainerInterface $container): void
{
    $container->add(RbacService::class)
        ->addArgument(TableLocator::class);
}

// In controller
public function initialize(): void
{
    $this->rbacService = $this->getTableLocator()->get('CakePHPMitra/Rbac.Roles')->getRbacService();
    // Or via DI if controller supports it
}
```

### Pattern 2: Cache-Aside (Lazy Loading)

**What:** Check cache first, query DB on miss, populate cache

**When:** getUserPermissions(), getUserRoles()

**Example:**
```php
public function getUserPermissions(string $userId): array
{
    return Cache::remember("rbac_user_permissions_{$userId}", function() use ($userId) {
        // Expensive DB queries here
    }, 'rbac'); // Use 'rbac' cache config
}
```

### Pattern 3: Wildcard Matching for Permissions

**What:** Support `Users.*` to match all Users actions

**When:** Checking permissions in `checkPermission()`

**Example:**
```php
protected function matchesWildcard(string $pattern, string $target): bool
{
    // Convert 'Users.*' to regex '/^Users\..+$/'
    $regex = '/^' . str_replace(['*', '.'], ['.+', '\.'], $pattern) . '$/';
    return (bool)preg_match($regex, $target);
}
```

### Pattern 4: Configuration Convention over Configuration

**What:** Sensible defaults, minimal required config

**When:** Plugin configuration in config/rbac.php

**Example:**
```php
return [
    'Rbac' => [
        'autoCheck' => true,              // Auto-add middleware
        'superadminRole' => 'superadmin', // Bypass role
        'multiRoles' => false,            // Single role default
        'cache' => [
            'config' => 'default',
            'prefix' => 'rbac_',
        ],
    ],
];
```

---

## Anti-Patterns to Avoid

### Anti-Pattern 1: Direct Database Queries in Policies

**What goes wrong:** Policies called on every request; N+1 queries kill performance

**Prevention:** Always use cached RbacService, never query in `canAccess()`

**Instead:**
```php
// BAD
public function canAccess(?IdentityInterface $identity, ServerRequestInterface $request): bool
{
    $rolesTable = TableRegistry::getTableLocator()->get('Roles');
    $roles = $rolesTable->find()->where(['user_id' => $user['id']])->all(); // N+1!
}

// GOOD
public function canAccess(?IdentityInterface $identity, ServerRequestInterface $request): bool
{
    return $this->rbacService->can($user, $controller, $action); // Uses cache
}
```

### Anti-Pattern 2: Storing Permissions in Session

**What goes wrong:** Stale permissions after role changes, session bloat

**Prevention:** Always check fresh (cached) permissions from database

**Instead:** Use request-scoped cache (automatically cleared between requests)

### Anti-Pattern 3: Magic String Permission Keys

**What goes wrong:** Typos (`'Users.edit'` vs `'Users.Edit'`), no IDE autocomplete

**Prevention:** Use constants or builder methods

**Instead:**
```php
// In PermissionsTable
class PermissionsTable extends Table
{
    public static function buildKey(string $controller, string $action, ?string $plugin = null): string
    {
        $parts = array_filter([$plugin, $controller, $action]);
        return implode('.', $parts);
    }

    public static function parseKey(string $key): array
    {
        // Returns ['plugin' => null, 'controller' => 'Users', 'action' => 'edit']
    }
}

// Usage
$key = PermissionsTable::buildKey('Users', 'edit'); // 'Users.edit'
```

### Anti-Pattern 4: Bypassing Authorization Middleware

**What goes wrong:** Inconsistent enforcement, security holes

**Prevention:** Always let middleware run, use `skipAuthorization()` explicitly if needed

**Instead:**
```php
// In controller action that should bypass
public function publicAction()
{
    $this->Authorization->skipAuthorization();
    // Action logic
}
```

---

## Scalability Considerations

| Concern | At 100 users | At 10K users | At 1M users |
|---------|--------------|--------------|-------------|
| **Permission cache** | Default cache (files) | Redis/Memcached | Redis cluster, TTL tuning |
| **Database queries** | Standard indexing | Index on user_id, role_id | Partitioning, read replicas |
| **Cache invalidation** | Per-user invalidation | Batch invalidation | Event-driven background jobs |
| **Permission discovery** | Manual sync | Automated on deploy | Pre-computed permission matrix |

---

## Sources

**HIGH confidence sources:**

1. **CakePHP Authorization 3.x source code** (examined directly)
   - `/vendor/cakephp/authorization/src/AuthorizationService.php`
   - `/vendor/cakephp/authorization/src/Middleware/AuthorizationMiddleware.php`
   - `/vendor/cakephp/authorization/src/Policy/OrmResolver.php`
   - `/vendor/cakephp/authorization/src/Policy/ResolverCollection.php`

2. **CakeDC/Users plugin source code** (examined directly)
   - `/vendor/cakedc/users/src/Plugin.php` (plugin bootstrap pattern)
   - `/vendor/cakedc/users/src/Loader/AuthorizationServiceLoader.php` (service loader pattern)
   - `/vendor/cakedc/users/src/Provider/AuthorizationServiceProvider.php` (provider pattern)

3. **CakeDC/Auth library source code** (examined directly)
   - `/vendor/cakedc/auth/src/Policy/RbacPolicy.php` (RBAC policy implementation pattern)

4. **CloudPE Admin application** (examined directly)
   - `/src/Application.php` (middleware stack pattern)
   - `/config/permissions.php` (permission configuration pattern)
   - `/src/Listener/SocialAuthListener.php` (event listener pattern)

All findings verified via direct source code inspection of installed CakePHP 5.x codebase.

---

**Confidence Assessment:** HIGH
- Authorization integration patterns verified via official library source
- Plugin structure patterns verified via CakeDC/Users source
- All architectural decisions based on actual CakePHP 5 conventions
- Database-backed resolver pattern inferred from MapResolver/OrmResolver extension points
