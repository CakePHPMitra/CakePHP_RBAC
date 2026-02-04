# Domain Pitfalls: CakePHP RBAC Plugin

**Domain:** CakePHP 5.x RBAC / Authorization Plugin Integration
**Researched:** 2026-02-04
**Confidence:** MEDIUM (based on training data + CloudPE Admin codebase analysis)

## Critical Pitfalls

These mistakes cause security vulnerabilities, rewrites, or major architectural issues.

### Pitfall 1: CakeDC/Users Permission System Collision

**What goes wrong:** CakeDC/Users ships with its own permissions system (`config/permissions.php` + `CakeDC/Auth.permissions`). Installing both creates two competing authorization layers that fight for control.

**Why it happens:**
- CakeDC/Users' `AuthorizationMiddleware` loads first via `MiddlewareQueueLoader`
- Plugin adds its own Authorization middleware → middleware executes twice
- Both systems try to check permissions → first one to deny wins (unpredictable)
- CakeDC/Users reads `CakeDC/Auth.permissions` config key, plugin needs different config structure

**Consequences:**
- Middleware order bugs: authentication works but authorization randomly fails
- Permission checks execute twice (performance hit)
- Host app must choose ONE system or carefully orchestrate both
- Config file collision: which permissions.php wins?

**Prevention:**
1. **Option A (Recommended):** Replace CakeDC/Users authorization entirely
   - Plugin provides `RbacMiddlewareQueueLoader` that extends CakeDC's loader
   - Override Authentication/Social middleware but skip CakeDC's Authorization middleware
   - Load plugin's DB-backed Authorization middleware instead

2. **Option B:** Coexist as fallback system
   - CakeDC/Users for basic auth rules (login/logout bypass)
   - Plugin for granular DB permissions (only authenticated routes)
   - Document clearly which system handles what

**Detection:**
- Multiple authorization middleware in `bin/cake middleware` output
- Logs showing "Authorization check failed" immediately after "User authenticated"
- Permissions work in development but fail in production (config caching differences)

**Phase:** Phase 1 (Middleware Integration) must resolve this.

---

### Pitfall 2: Permission Cache Invalidation Race Conditions

**What goes wrong:** Admin updates role permissions → cache invalidates → but user's active request is mid-flight with old cached permissions → user has stale permissions for duration of request lifecycle.

**Why it happens:**
- CakePHP request lifecycle: middleware loads permissions once at start
- Identity object built with permissions array from cache
- Cache invalidation happens in separate admin request
- User request using old Identity object completes with stale permissions

**Consequences:**
- **Security risk:** User loses permission but can still access resource for ~1 second
- **UX confusion:** "I removed their access but they're still in!"
- Race conditions on permission matrix updates (what order do role-permission assignments save?)

**Prevention:**

1. **Cache invalidation strategy:**
   ```php
   // On permission change, invalidate ALL user caches
   $cacheKey = "rbac.permissions.user.{$userId}";
   $cacheKey = "rbac.permissions.role.{$roleId}"; // Invalidate role cache
   $cacheKey = "rbac.permissions.user.*"; // Wildcard clear
   ```

2. **Two-tier caching:**
   - Short TTL (1-5 seconds) for user-specific permission lists
   - Longer TTL (1 hour) for role definitions
   - Trade-off: security vs performance

3. **Version-based cache keys:**
   ```php
   $version = Configure::read('RBAC.permissions_version'); // Increment on change
   $cacheKey = "rbac.permissions.user.{$userId}.v{$version}";
   ```

4. **Immediate invalidation with fallback:**
   - Write to cache with `INVALIDATED` flag
   - Next permission check sees flag → reloads from DB
   - Background process clears invalidated entries

**Detection:**
- User reports "I changed permissions but they didn't take effect immediately"
- Logs show permission cache hits during permission update operations
- Race condition symptoms: sometimes works, sometimes doesn't

**Phase:** Phase 3 (Permission Caching) must implement version-based or immediate invalidation strategy.

---

### Pitfall 3: Middleware Order Dependency Hell

**What goes wrong:** RBAC middleware loads before Authentication middleware → `Identity` not yet set → permission check throws `IdentityNotFoundException` → white screen of death.

**Why it happens:**
- CakePHP middleware is order-dependent (queue processes sequentially)
- Authorization requires Identity from Authentication
- Plugin loads in Application.php `bootstrap()` but CakeDC/Users controls middleware order via `MiddlewareQueueLoader`
- Host app can add middlewares out of order

**Correct order:**
```
1. ErrorHandlerMiddleware
2. AssetMiddleware
3. RoutingMiddleware
4. BodyParserMiddleware
5. CsrfProtectionMiddleware
6. AuthenticationMiddleware ← Must come before Authorization
7. AuthorizationMiddleware ← RBAC plugin's middleware
8. Custom app middleware
```

**Consequences:**
- Fatal errors on every request if middleware order wrong
- Hard to debug: "Identity not found" doesn't mention middleware
- Host app breaking changes if they modify middleware order

**Prevention:**

1. **Plugin provides MiddlewareQueueLoader:**
   ```php
   namespace CakePHPMitra\Rbac\Loader;

   class RbacMiddlewareQueueLoader extends \CakeDC\Users\Loader\MiddlewareQueueLoader {
       public function __invoke(...) {
           $queue = parent::__invoke(...); // Gets Authentication
           $queue->add(new RbacAuthorizationMiddleware()); // After Authentication
           return $queue;
       }
   }
   ```

2. **Documentation must be explicit:**
   - README: "IMPORTANT: Do not add Authorization middleware manually"
   - README: "Load plugin BEFORE adding custom middleware"

3. **Runtime validation:**
   ```php
   // In RbacAuthorizationMiddleware::process()
   if (!$request->getAttribute('identity')) {
       throw new RuntimeException(
           'RBAC middleware must run after AuthenticationMiddleware. ' .
           'Check your middleware order in Application.php'
       );
   }
   ```

**Detection:**
- `bin/cake middleware` shows middlewares out of order
- Error logs: "Call to a member function can() on null" (Identity is null)
- Works for some routes, fails for others (prefix-specific middleware)

**Phase:** Phase 1 (Middleware Integration) must validate order and provide clear error messages.

---

### Pitfall 4: Superadmin Bypass Security Hole

**What goes wrong:** Superadmin role bypasses ALL permission checks → host app adds admin UI to manage roles → accidentally makes ANY role into superadmin → privilege escalation vulnerability.

**Why it happens:**
- Plugin allows configuring superadmin role name (flexibility)
- Host app doesn't validate before saving role changes
- UI allows editing role name or assigning superadmin role to users
- No protection against creating new roles named same as superadmin

**Consequences:**
- **Security vulnerability:** User promotes themselves to superadmin
- Audit logs don't capture escalation (looks like normal role assignment)
- Mass privilege escalation if attacker creates "superadmin" role assignments

**Prevention:**

1. **Hardcode superadmin role name (Don't make it configurable):**
   ```php
   const SUPERADMIN_ROLE = 'superadmin'; // Not configurable
   ```

2. **Protect superadmin role in database:**
   ```php
   // In RolesTable::beforeSave()
   if ($entity->name === RbacService::SUPERADMIN_ROLE && $entity->isDirty('name')) {
       throw new ForbiddenException('Cannot modify superadmin role');
   }
   ```

3. **Require explicit authorization to assign superadmin:**
   ```php
   // In UsersRolesTable::beforeSave()
   $role = $this->Roles->get($entity->role_id);
   if ($role->name === 'superadmin') {
       if (!$this->canAssignSuperadmin($currentUser)) {
           throw new ForbiddenException('Only superadmin can assign superadmin role');
       }
   }
   ```

4. **Audit log all superadmin assignments:**
   ```php
   // Separate audit trail for privilege escalation events
   ```

**Detection:**
- User with non-superadmin role suddenly has full access
- Audit logs show role assignment but no approval workflow
- Multiple users with superadmin role when expecting only 1-2

**Phase:** Phase 4 (Admin UI Security) must implement protections before shipping admin UI.

---

### Pitfall 5: Database Schema Performance Cliff at Scale

**What goes wrong:** Works fine with 100 users, 10 roles, 50 permissions. At 10K users, 50 roles, 500 permissions → permission check queries take 2-3 seconds → site unusable.

**Why it happens:**
- Query joins 4 tables: `users` → `users_roles` → `roles` → `roles_permissions` → `permissions`
- No composite indexes on pivot tables
- Query fetches ALL permissions for user (including inherited from all roles)
- N+1 queries if checking permissions in loops

**Consequences:**
- Site becomes unusable at scale
- Database CPU spikes on login (loading all permissions)
- Caching becomes mandatory, not optional
- Adding more roles/permissions makes problem worse

**Prevention:**

1. **Critical indexes on pivot tables:**
   ```php
   // users_roles
   $table->addIndex(['user_id', 'role_id'], ['unique' => true]);
   $table->addIndex(['role_id']); // For reverse lookups

   // roles_permissions
   $table->addIndex(['role_id', 'permission_id'], ['unique' => true]);
   $table->addIndex(['permission_id']); // For reverse lookups

   // users_permissions (direct user permissions)
   $table->addIndex(['user_id', 'permission_id'], ['unique' => true]);
   $table->addIndex(['permission_id']);

   // permissions table
   $table->addIndex(['resource', 'action'], ['unique' => true]); // Fast permission lookups
   ```

2. **Single optimized query for all user permissions:**
   ```php
   // UNION query: role permissions + direct user permissions
   SELECT DISTINCT p.* FROM permissions p
   INNER JOIN roles_permissions rp ON rp.permission_id = p.id
   INNER JOIN users_roles ur ON ur.role_id = rp.role_id
   WHERE ur.user_id = ?
   UNION
   SELECT DISTINCT p.* FROM permissions p
   INNER JOIN users_permissions up ON up.permission_id = p.id
   WHERE up.user_id = ?
   ```

3. **Eager loading strategy:**
   ```php
   // Load all permissions at authentication time (1 query)
   // Store in Identity object
   // Never query again during request lifecycle
   ```

4. **Performance testing from Day 1:**
   - Seed database with 1000 users, 20 roles, 200 permissions
   - Test permission check performance (target: <50ms)
   - Test role assignment at scale

**Detection:**
- Slow query log shows pivot table joins
- EXPLAIN ANALYZE shows full table scans
- Response times increase linearly with number of roles/permissions
- Database monitoring shows CPU spikes during authentication

**Phase:** Phase 2 (Database Schema) must include composite indexes in migrations. Phase 5 (Performance Testing) validates.

---

### Pitfall 6: Multi-Role Permission Resolution Ambiguity

**What goes wrong:** User has roles ['editor', 'viewer']. Editor allows 'posts.delete', Viewer denies 'posts.delete'. Which wins?

**Why it happens:**
- Plugin configuration allows multi-roles but doesn't specify conflict resolution
- Developer assumes "most permissive wins" (union) but implements "most restrictive wins" (intersection)
- No explicit deny permissions (only allow via role assignment)

**Consequences:**
- Unpredictable authorization behavior
- User complains "I have Editor role but can't edit!"
- Security risk if "most permissive" when expecting "most restrictive"

**Prevention:**

1. **Document clearly in README:**
   ```
   Multi-role resolution: UNION (most permissive wins)
   - If ANY role grants permission → ALLOW
   - User must be denied by ALL roles → DENY
   ```

2. **Implement explicit deny if needed:**
   ```php
   // permissions table
   $table->addColumn('effect', 'enum', [
       'values' => ['allow', 'deny'],
       'default' => 'allow'
   ]);

   // Resolution order:
   // 1. Explicit deny (any role) → DENY
   // 2. Explicit allow (any role) → ALLOW
   // 3. No permission → DENY (default deny)
   ```

3. **Prefer UNION (most permissive) for simplicity:**
   - Easier to reason about
   - Matches user expectations ("I have Editor so I can edit")
   - No explicit deny needed (just don't assign permission)

4. **Add configuration option:**
   ```php
   'RBAC' => [
       'multiRoleResolution' => 'union', // or 'intersection'
   ]
   ```

**Detection:**
- User bug reports: "I have the role but can't access"
- Inconsistent permission behavior with multi-role users
- Security audit finds users with conflicting role assignments

**Phase:** Phase 2 (Permission Model) must decide and document resolution strategy.

---

## Moderate Pitfalls

These cause delays, technical debt, or require refactoring.

### Pitfall 7: String-Based Permission Keys Without Namespace Convention

**What goes wrong:** Developer creates permission `view` in plugin and `view` in host app → collision → unpredictable behavior.

**Why it happens:**
- No enforced namespacing convention
- String keys seem simple but cause conflicts at scale
- Copy-paste errors: `settings.edit` vs `setting.edit` (typo)

**Prevention:**

1. **Enforce dot-notation convention:**
   ```
   Format: {resource}.{action}
   Examples:
   - posts.view
   - posts.create
   - users.delete
   - settings.manage
   ```

2. **Validation in Permissions model:**
   ```php
   public function validationDefault(Validator $validator): Validator {
       $validator->add('name', 'format', [
           'rule' => ['custom', '/^[a-z0-9_]+\.[a-z0-9_]+$/'],
           'message' => 'Permission must be format: resource.action'
       ]);
       return $validator;
   }
   ```

3. **Plugin-namespaced permissions:**
   ```
   For plugin permissions: {plugin}.{resource}.{action}
   Example: rbac.roles.manage
   ```

**Detection:**
- Permission collision errors in logs
- Unexpected authorization failures
- Permissions list shows duplicates

**Phase:** Phase 2 (Permission Model) must implement validation.

---

### Pitfall 8: No Permission Auto-Discovery Validation

**What goes wrong:** Plugin claims to auto-discover controller/actions → discovers `_privateMethod()` or `beforeFilter()` as permissions → permission list polluted with invalid entries.

**Why it happens:**
- Reflection API finds ALL public methods
- No filtering for actual action methods
- Traits and parent class methods discovered

**Prevention:**

1. **Whitelist via Router reflection:**
   ```php
   // Only discover routes that exist in Router
   $routes = Router::routes();
   foreach ($routes as $route) {
       $controller = $route->defaults['controller'];
       $action = $route->defaults['action'];
       // Create permission: {controller}.{action}
   }
   ```

2. **Blacklist common non-action methods:**
   ```php
   const IGNORED_ACTIONS = [
       'beforeFilter', 'beforeRender', 'afterFilter',
       'initialize', 'implementedEvents'
   ];
   ```

3. **Respect `@internal` docblocks:**
   ```php
   /**
    * @internal
    */
   public function _privateHelper() {} // Skip this
   ```

**Detection:**
- Permissions list includes `initialize`, `beforeFilter`
- Permission count much higher than expected
- Permissions for parent class methods appear

**Phase:** Phase 3 (Auto-Discovery) must filter correctly.

---

### Pitfall 9: Admin UI Doesn't Check Own Permissions

**What goes wrong:** Plugin ships admin UI to manage roles/permissions → but doesn't protect the admin UI itself → any authenticated user can modify permissions.

**Why it happens:**
- Circular dependency thinking: "How do I protect the permission manager before permissions exist?"
- Developer forgets to add permission checks to admin controllers
- Assume CakeDC/Users admin role is enough

**Prevention:**

1. **Require explicit permission for RBAC admin:**
   ```php
   // Default seed permissions:
   - rbac.roles.view
   - rbac.roles.manage
   - rbac.permissions.view
   - rbac.permissions.manage
   - rbac.users.assign_roles
   ```

2. **AdminController checks on initialize:**
   ```php
   public function initialize(): void {
       parent::initialize();
       $this->Authorization->authorize($this, 'accessRbacAdmin');
   }
   ```

3. **Seed data assigns RBAC permissions only to superadmin initially:**
   ```php
   // migrations/seed/RbacDefaultSeeder.php
   $superadmin = $roles->findByName('superadmin')->first();
   $rbacPermissions = $permissions->find()->where(['name LIKE' => 'rbac.%']);
   // Assign all rbac.* permissions to superadmin only
   ```

**Detection:**
- Any authenticated user can access `/admin/rbac/roles`
- No authorization exceptions in admin UI
- Test user without admin role can edit permissions

**Phase:** Phase 4 (Admin UI) must implement authorization checks.

---

### Pitfall 10: Permission Seeding Race Condition

**What goes wrong:** Host app runs migrations → seeds default roles → plugin tries to seed permissions → foreign key violation because roles don't exist yet.

**Why it happens:**
- Plugin migrations run independently from host app
- Seed data assumes certain roles exist
- No coordination between plugin and host app seeds

**Prevention:**

1. **Plugin provides seed via Command, not automatic:**
   ```bash
   bin/cake rbac seed_defaults
   ```

2. **Seed command is idempotent:**
   ```php
   // Check if already seeded
   if ($this->Roles->exists(['name' => 'superadmin'])) {
       $this->out('Already seeded, skipping...');
       return;
   }
   ```

3. **README documents seed order:**
   ```
   Installation:
   1. bin/cake migrations migrate -p CakePHPMitra/Rbac
   2. bin/cake rbac seed_defaults
   ```

4. **Seed data uses findOrCreate pattern:**
   ```php
   $role = $this->Roles->findOrCreate(['name' => 'superadmin'], function ($entity) {
       $entity->description = 'Full system access';
   });
   ```

**Detection:**
- Foreign key constraint errors during installation
- Migrations succeed but seed fails
- Default roles/permissions missing after install

**Phase:** Phase 2 (Database Schema) must include idempotent seeding.

---

### Pitfall 11: View Helper Performance in Loops

**What goes wrong:** Template loops over 100 items checking `$this->Rbac->can()` for each → 100 permission checks → page load slow.

**Why it happens:**
- View helper implemented naively (query on each call)
- Developer doesn't realize cost of permission checks
- No caching at view layer

**Prevention:**

1. **View helper caches within request:**
   ```php
   // RbacHelper.php
   protected $_permissionCache = [];

   public function can($permission) {
       if (!isset($this->_permissionCache[$permission])) {
           $this->_permissionCache[$permission] = $this->_service->can($permission);
       }
       return $this->_permissionCache[$permission];
   }
   ```

2. **Bulk permission check method:**
   ```php
   // Check multiple permissions at once
   $permissions = $this->Rbac->canMultiple(['posts.edit', 'posts.delete', 'posts.publish']);
   ```

3. **Template guidance:**
   ```php
   // BAD
   <?php foreach ($posts as $post): ?>
       <?php if ($this->Rbac->can('posts.edit')): ?> // 100 checks

   // GOOD
   <?php $canEdit = $this->Rbac->can('posts.edit'); ?> // 1 check
   <?php foreach ($posts as $post): ?>
       <?php if ($canEdit): ?>
   ```

**Detection:**
- Slow page rendering with permission checks in templates
- Profiler shows many identical permission check queries
- Page load time proportional to item count

**Phase:** Phase 3 (View Helper) must implement request-level caching.

---

## Minor Pitfalls

These cause annoyance but are easily fixable.

### Pitfall 12: Migration Rollback Leaves Orphaned Data

**What goes wrong:** Developer runs `migrations rollback` → drops tables → but doesn't clean up users' cached permissions → users have phantom permissions in cache.

**Prevention:**

1. **Rollback clears all RBAC caches:**
   ```php
   // In down() method of migrations
   public function down() {
       Cache::clear('rbac');
       $this->table('roles')->drop()->save();
   }
   ```

2. **Document cache clearing in README:**
   ```
   After rollback:
   bin/cake cache clear_all
   ```

**Phase:** Phase 2 (Database Schema) migrations should handle cleanup.

---

### Pitfall 13: No Soft Deletes for Audit Trail

**What goes wrong:** Admin deletes role → hard delete → audit trail lost → can't answer "who had this role before deletion?"

**Prevention:**

1. **Add soft delete behavior:**
   ```php
   // In RolesTable
   $this->addBehavior('Timestamp');
   $this->addBehavior('SoftDelete', ['field' => 'deleted_at']);
   ```

2. **Keep assignments even after soft delete:**
   ```php
   // users_roles rows remain even if role soft-deleted
   // Query includes deleted_at IS NULL filter
   ```

**Detection:**
- Admin complaints about inability to restore deleted roles
- Audit questions can't be answered

**Phase:** Phase 2 (Database Schema) should include soft delete fields.

---

### Pitfall 14: Permission Names Not Translatable

**What goes wrong:** Plugin ships with English-only permission display names → international users see `posts.create` instead of `Crear Publicación`.

**Prevention:**

1. **Store translation key, not display text:**
   ```php
   // permissions.name = 'posts.create'
   // permissions.label = 'permissions.posts_create' (translation key)
   ```

2. **Use __() in views:**
   ```php
   <?= h(__($permission->label)) ?>
   ```

**Phase:** Phase 4 (Admin UI) should use translation keys.

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Middleware Integration (Phase 1) | Middleware order dependency, CakeDC/Users collision | Provide MiddlewareQueueLoader, runtime validation, clear error messages |
| Database Schema (Phase 2) | Missing composite indexes, no soft deletes | Include all indexes in initial migration, add deleted_at fields |
| Permission Model (Phase 2) | Multi-role resolution ambiguity, no namespace validation | Document UNION strategy, validate permission name format |
| Auto-Discovery (Phase 3) | Discovering non-action methods | Filter via Router reflection, blacklist internal methods |
| Caching (Phase 3) | Race conditions on cache invalidation | Version-based cache keys or immediate invalidation flags |
| View Helper (Phase 3) | Permission checks in loops | Request-level caching in helper |
| Admin UI (Phase 4) | Admin UI not protected by permissions | Require rbac.*.manage permissions for all admin actions |
| Admin UI Security (Phase 4) | Superadmin privilege escalation | Protect superadmin role from modification, audit superadmin assignments |
| Seeding (Phase 2) | Race condition with host app seeds | Provide seed Command (not automatic), use findOrCreate |
| Testing (Phase 5) | No scale testing | Seed 1000 users, 20 roles, 200 permissions for performance tests |

---

## Sources

**Confidence Assessment:**

| Source | Confidence | Notes |
|--------|------------|-------|
| CakePHP Authorization patterns | MEDIUM | Based on training knowledge (Jan 2025), could not verify with official docs |
| CakeDC/Users integration | MEDIUM | Based on CloudPE Admin codebase analysis + training knowledge |
| Database performance | HIGH | Standard RDBMS pivot table optimization patterns |
| Security patterns | HIGH | Standard RBAC security principles (least privilege, audit trail) |
| CakePHP 5 middleware | MEDIUM | Based on CloudPE Admin Application.php analysis + training |

**Known gaps:**
- Could not access Context7 for CakePHP Authorization ^3.0 current documentation
- WebSearch unavailable for recent 2026 community patterns
- WebFetch denied for official CakePHP book verification
- Pitfalls derived from first principles + training knowledge + codebase analysis

**Recommendations:**
- Phase 1: Verify CakePHP Authorization middleware requirements with official docs
- Phase 1: Test CakeDC/Users ^16.0 compatibility in sandbox
- Phase 3: Benchmark permission check performance with realistic data volume
- Phase 4: Security audit admin UI before Phase 5 completion

---

## Research Methodology Note

This research was conducted using:
1. **Training data analysis** (Claude training through Jan 2025) - MEDIUM confidence
2. **Codebase analysis** (CloudPE Admin patterns for CakeDC/Users integration) - HIGH confidence
3. **First principles** (RBAC security patterns, database optimization) - HIGH confidence

**Limitations:**
- External tools unavailable (Context7, WebSearch, WebFetch blocked)
- Recommendations based on training data hypothesis + architectural analysis
- All pitfalls should be validated during Phase 1-2 implementation

**Honest assessment:**
These are high-probability pitfalls based on:
- Common CakePHP Authorization integration mistakes (training data)
- Observable patterns in CloudPE Admin codebase
- Standard RBAC system failure modes

Treat as informed hypotheses requiring validation during implementation, not absolute facts.
