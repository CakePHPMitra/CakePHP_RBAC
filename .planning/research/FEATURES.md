# Feature Landscape: RBAC Systems

**Domain:** PHP RBAC/Authorization Plugins
**Researched:** 2026-02-04
**Confidence:** MEDIUM (based on training data through January 2025, external sources unavailable)

## Table Stakes

Features users expect from an RBAC system. Missing these = plugin feels incomplete.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **Roles table** | Foundation of RBAC | Low | Standard CakePHP table with name, slug, description |
| **Permissions table** | Foundation of RBAC | Low | String-based permissions (e.g., 'posts.edit') |
| **Many-to-many pivot tables** | Standard RBAC pattern | Low | `users_roles`, `roles_permissions` for maximum flexibility |
| **Role assignment via UI** | Admin must manage without DB access | Medium | CRUD interface for assigning roles to users |
| **Permission assignment via UI** | Admin must manage without DB access | Medium | Grid/matrix for role-permission assignment |
| **Check if user has permission** | Core authorization logic | Medium | `$user->can('posts.edit')` or `Identity::can()` |
| **Check if user has role** | Common authorization pattern | Low | `$user->hasRole('admin')` |
| **Middleware for route protection** | Automatic enforcement | Medium | Deny access if permission missing |
| **View helpers** | Template-level checks | Low | `<?php if ($this->Rbac->can('posts.edit')): ?>` |
| **Superadmin bypass** | Admin needs full access | Low | Configurable role that bypasses all checks |
| **Permission caching** | Per-request checks are expensive | High | Cache user permissions in session/memory |
| **Migrations** | Easy installation | Low | Database schema setup |
| **Seed data** | Quick start for developers | Low | Example roles/permissions |
| **Integration with auth system** | Must work with existing auth | Medium | CakeDC/Users integration for CakePHP |
| **Direct user permissions** | Override role permissions | Medium | `users_permissions` for edge cases |

## Differentiators

Features that set this plugin apart. Not expected, but highly valued.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **Controller/action auto-discovery** | Zero-config permission registration | High | Parse routes to generate permission list automatically |
| **Dual permission types** | Flexibility for different use cases | Medium | String-based ('posts.edit') AND controller/action ('Posts::edit') |
| **Permission matrix UI** | Visual bulk assignment | High | Checkbox grid for role-permission assignment (like Laravel Nova) |
| **Multi-role support (configurable)** | Simple default, powerful when needed | Medium | Default: single role. Enable multi-role via config |
| **Permission wildcards** | Bulk permission grants | Medium | `posts.*` grants all post permissions |
| **Permission groups/categories** | Organize permissions logically | Low | Group by resource (Posts, Users, Settings) |
| **Audit log** | Track who changed permissions when | Medium | Log role/permission changes with timestamps |
| **Permission dependencies** | Enforce logical permission hierarchies | High | `posts.delete` requires `posts.edit` |
| **Conditional permissions** | Context-aware authorization | High | `can('edit', $post)` checks ownership |
| **API for external systems** | Headless admin or integrations | Medium | JSON API for role/permission management |
| **CLI commands** | DevOps automation | Low | `bin/cake rbac assign admin user@example.com` |
| **Import/export permissions** | Environment sync (dev → prod) | Medium | JSON/YAML export of role-permission config |
| **Permission search/filter** | Manage large permission lists | Medium | Search by name, filter by role in admin UI |
| **Blade-style directives** | Cleaner template syntax | Low | `@can('posts.edit')` instead of `<?php if()` |
| **Gate/Ability pattern** | Define permissions in code | Medium | Laravel-style Gates for complex logic |
| **Team/tenant isolation** | Multi-tenant RBAC | High | Scope permissions to teams/organizations |

## Anti-Features

Features to explicitly NOT build in v1. Common mistakes in RBAC plugins.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| **User management CRUD** | Scope creep — CakeDC/Users handles this | Provide role assignment UI only, link to Users plugin for user CRUD |
| **Custom UI themes** | Maintenance burden, preference variety | Use CakePHP default layout, let host app override |
| **Authentication** | Out of scope — CakeDC/Users handles this | Assume authentication is handled, focus on authorization |
| **Resource-level permissions (v1)** | Complexity explosion | Action-level only. "Can edit posts" not "can edit THIS post". Defer to v2 with policy classes |
| **Role hierarchy/inheritance (v1)** | Complex edge cases, rarely needed | Single-level roles. Defer to v2 if demand exists |
| **Dynamic permission generation** | Too clever, hard to debug | Permissions registered explicitly or via discovery, not runtime generation |
| **Frontend framework dependency** | Limits reusability | Plain PHP templates with CakePHP helpers |
| **GraphQL/REST API (v1)** | Premature complexity | Focus on web UI first, API in v2 if needed |
| **Permission versioning** | Over-engineering for v1 | Audit log is enough, versioning adds complexity |
| **SSO/OAuth integration** | Out of scope | CakeDC/Users handles authentication providers |
| **Custom policy engines** | Framework already has this | Use CakePHP Authorization policies for complex logic |

## Feature Dependencies

**Permission checking depends on:**
```
Authentication (CakeDC/Users)
  → Identity available
    → Roles table
      → Permissions table
        → Pivot tables (users_roles, roles_permissions)
          → Cache layer
            → Authorization check
```

**Admin UI depends on:**
```
Authentication (CakeDC/Users)
  → Superadmin role
    → Roles CRUD
      → Permissions CRUD
        → Permission matrix (requires both)
          → User role assignment (requires Roles + Users)
```

**Auto-discovery depends on:**
```
CakePHP Routing
  → Routes parsed
    → Controllers identified
      → Actions enumerated
        → Permissions generated
```

## MVP Recommendation

For v1 (greenfield), prioritize these features to ship a complete, usable RBAC system:

### Phase 1: Foundation (ship to validate)
1. **Database schema** — roles, permissions, pivot tables
2. **Basic models** — RolesTable, PermissionsTable with associations
3. **Permission checking** — `RbacService::can()` method
4. **Superadmin bypass** — Configurable role that skips all checks
5. **Seed data** — Example roles/permissions for quick start

### Phase 2: Admin UI (make it usable)
6. **Roles CRUD** — Create, edit, delete roles
7. **Permissions CRUD** — Create, edit, delete permissions
8. **Role-permission assignment** — Checkboxes or simple form
9. **User-role assignment** — Assign roles to users

### Phase 3: Integration (make it automatic)
10. **Middleware** — Automatic route protection
11. **View helpers** — Template permission checks
12. **Identity integration** — `$this->Identity->can()`
13. **Permission caching** — Session-based cache

### Phase 4: Developer Experience (make it delightful)
14. **Controller/action discovery** — Auto-generate permissions from routes
15. **Permission matrix UI** — Visual grid for bulk assignment
16. **Multi-role support** — Configurable for advanced use cases

### Defer to post-MVP (v2):

- **Resource-level permissions** — Requires policy classes, significant complexity
- **Role hierarchy** — Niche use case, adds conceptual overhead
- **Permission wildcards** — Nice-to-have, not critical
- **Audit log** — Valuable but not blocking launch
- **API endpoints** — Focus on web UI first
- **Teams/tenants** — Multi-tenant is a separate concern
- **Import/export** — Operational convenience, not core feature
- **CLI commands** — Convenience feature, web UI is primary

## Comparison: CakePHP vs Laravel vs Symfony

### Laravel Spatie Permissions (reference standard)

**Strengths:**
- Elegant API: `$user->givePermissionTo()`, `$user->hasPermissionTo()`
- Blade directives: `@can`, `@role`, `@hasrole`
- Wildcard permissions: `posts.*`
- Team support: Multi-tenant permissions
- Middleware: `role:admin|editor`, `permission:posts.edit`
- Cache: Built-in permission caching

**What CakePHP RBAC should match:**
- Fluent API for assignment (`assignRole()`, `givePermission()`)
- Template helpers equivalent to Blade directives
- Middleware for route protection
- Permission caching

**What CakePHP RBAC should improve:**
- Controller/action auto-discovery (Spatie requires manual registration)
- Tighter framework integration (Spatie is Laravel-specific, we're CakePHP-specific)
- Default admin UI (Spatie has none, requires Laravel Nova or custom)

### Symfony Security

**Strengths:**
- Voters: Complex authorization logic in classes
- Role hierarchy: `ROLE_ADMIN` inherits `ROLE_USER`
- Security annotations: `#[IsGranted('ROLE_ADMIN')]`
- Expression language: `is_granted('ROLE_ADMIN') and is_granted('ROLE_EDITOR')`

**What CakePHP RBAC should match:**
- Complex authorization via CakePHP Authorization policies
- Attribute-based route protection

**What Symfony does differently (not goals):**
- Role hierarchy (defer to v2)
- Expression language (CakePHP Authorization has policies)

### CakePHP Authorization Plugin

**Current state:**
- Policy-based authorization (like Symfony Voters)
- Middleware for automatic enforcement
- Identity integration: `$this->Authorization->can()`
- Request authorization: `$this->Authorization->applyScope()`

**What CakePHPMitra/RBAC adds:**
- Database-backed permissions (Authorization is code-only)
- Admin UI for runtime permission management
- Role-based access control (Authorization is policy-based)
- Permission caching (Authorization checks policies every time)
- Auto-discovery (Authorization requires manual policy creation)

### CakeDC Users

**Current RBAC:**
- Single `role` column on users table
- `config/permissions.php` for static rules
- Simple allow/deny rules: `['plugin' => 'CakeDC/Users', 'controller' => 'Users', 'action' => 'index', 'allowed' => ['admin']]`

**What CakePHPMitra/RBAC improves:**
- Multiple roles per user (configurable)
- Runtime permission changes (no deploy needed)
- Granular permissions beyond controller/action
- Direct user permissions (override role)
- Admin UI (CakeDC/Users has none for permissions)

## Feature Priority Matrix

| Feature | Table Stakes? | Differentiator? | Complexity | MVP Priority |
|---------|---------------|-----------------|------------|--------------|
| Roles table | YES | - | Low | P0 |
| Permissions table | YES | - | Low | P0 |
| Pivot tables | YES | - | Low | P0 |
| Role assignment UI | YES | - | Medium | P1 |
| Permission assignment UI | YES | - | Medium | P1 |
| `can()` method | YES | - | Medium | P0 |
| `hasRole()` method | YES | - | Low | P0 |
| Middleware | YES | - | Medium | P2 |
| View helpers | YES | - | Low | P2 |
| Superadmin bypass | YES | - | Low | P0 |
| Permission caching | YES | - | High | P2 |
| Migrations | YES | - | Low | P0 |
| Seed data | YES | - | Low | P0 |
| CakeDC/Users integration | YES | - | Medium | P0 |
| Direct user permissions | YES | - | Medium | P1 |
| Controller/action discovery | - | YES | High | P3 |
| Dual permission types | - | YES | Medium | P0 |
| Permission matrix UI | - | YES | High | P3 |
| Multi-role support | - | YES | Medium | P3 |
| Wildcards | - | YES | Medium | Defer |
| Permission groups | - | YES | Low | P1 |
| Audit log | - | YES | Medium | Defer |
| Permission dependencies | - | YES | High | Defer |
| Conditional permissions | - | YES | High | Defer (use policies) |
| API endpoints | - | YES | Medium | Defer |
| CLI commands | - | YES | Low | Defer |
| Import/export | - | YES | Medium | Defer |
| Search/filter | - | YES | Medium | P3 |

**Priority definitions:**
- **P0** = Foundation — cannot ship without this
- **P1** = Usability — technically works but annoying without this
- **P2** = Integration — connects to framework properly
- **P3** = Polish — makes it delightful to use
- **Defer** = Post-MVP — valuable but not required for launch

## Complexity Analysis

### Low Complexity (< 1 day)
- `hasRole()` method
- View helpers (basic `can()` wrapper)
- Seed data
- Migrations
- Permission groups (just a field on permissions table)
- CLI commands (basic Cake command class)

### Medium Complexity (1-3 days)
- `can()` method (query pivot tables + cache)
- Role assignment UI (form with user selection)
- Permission assignment UI (checkboxes)
- Direct user permissions (additional pivot table)
- Middleware (integrate with CakePHP Authorization)
- Dual permission types (string vs controller/action)
- Multi-role support (config flag + query adjustments)
- API endpoints (standard REST CRUD)
- Import/export (JSON serialization)
- Audit log (table + behavior)
- Search/filter (query conditions + UI)

### High Complexity (3-7 days)
- Permission caching (session + invalidation strategy)
- Controller/action auto-discovery (parse routes + handle edge cases)
- Permission matrix UI (dynamic grid + bulk save)
- Permission dependencies (enforce on assignment + runtime)
- Conditional permissions (requires policy integration)

## Feature Interaction Map

**Simple (no dependencies):**
- Roles CRUD
- Permissions CRUD
- Seed data
- Migrations

**Sequential (B requires A):**
- Permission checking → Roles + Permissions + Pivot tables
- Permission caching → Permission checking
- Middleware → Permission checking
- View helpers → Permission checking
- User role assignment → Roles + CakeDC/Users
- Permission matrix → Roles + Permissions
- Auto-discovery → Permissions table exists
- Audit log → Roles + Permissions

**Parallel (independent):**
- Role assignment UI + Permission assignment UI (both use pivot tables)
- View helpers + Middleware (both use permission checking)
- Wildcards + Direct user permissions (both modify checking logic)

## Design Decisions Required

### Multi-role default?

**Option A: Single role default, multi-role opt-in (RECOMMENDED)**
- Simpler mental model for most users
- Config flag: `'multiRoles' => false` (default)
- Most projects need simple RBAC

**Option B: Multi-role always**
- More flexible but more complex
- Forces users to think about role conflicts
- Overkill for simple use cases

**Recommendation:** Option A. Single role is simpler, advanced users can enable multi-role.

### Permission string format?

**Option A: Dot notation (RECOMMENDED)**
- `posts.create`, `posts.edit`, `settings.view`
- Matches Laravel Spatie
- Easy to wildcard: `posts.*`

**Option B: Colon notation**
- `posts:create`, `posts:edit`, `settings:view`
- Symfony style
- Less common in PHP

**Option C: Slash notation**
- `posts/create`, `posts/edit`, `settings/view`
- Matches URLs
- Confusing with actual routes

**Recommendation:** Option A. Dot notation is most common in PHP RBAC systems.

### Controller/action permission format?

**Option A: Plugin::Controller::action (RECOMMENDED)**
- `CakePHPMitra/Dbconfig.Configurations.edit`
- Fully qualified
- No ambiguity

**Option B: Controller.action**
- `Configurations.edit`
- Shorter but ambiguous with plugins

**Recommendation:** Option A. Explicit plugin namespace prevents collisions.

### Cache invalidation strategy?

**Option A: Session-based (RECOMMENDED)**
- Cache in session on login
- Invalidate on role/permission change
- Fast, simple

**Option B: Memory cache (Redis/Memcached)**
- Faster than session
- Requires infrastructure
- Complex invalidation across servers

**Option C: Database every time**
- No cache complexity
- Slow for every request
- Not viable

**Recommendation:** Option A for MVP. Option B for v2 if performance demands.

## Sources

**Confidence: MEDIUM** — Based on training data through January 2025. External verification unavailable due to tool restrictions.

**Primary references (training data):**
- Laravel Spatie Permissions v5/v6 (reference standard for PHP RBAC)
- Symfony Security component (mature enterprise RBAC)
- CakePHP Authorization plugin v3 (framework integration)
- CakeDC/Users plugin v16 (authentication provider)
- General RBAC best practices (NIST RBAC model)

**What's verified:**
- Feature expectations for RBAC systems (table stakes)
- Laravel Spatie API patterns (as of training cutoff)
- CakePHP Authorization architecture (as of training cutoff)
- CakeDC/Users capabilities (as of training cutoff)

**What needs validation:**
- Current Spatie Permissions version (may be v7+ by Feb 2026)
- CakePHP Authorization plugin updates (may have new features)
- Competitor CakePHP RBAC plugins (not in training data)

**Recommendation:** Treat this as hypothesis. Validate key decisions (permission format, cache strategy) with official documentation before implementation.
