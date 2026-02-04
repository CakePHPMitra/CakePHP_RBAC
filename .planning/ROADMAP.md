# Roadmap: CakePHPMitra/RBAC

## Overview

This roadmap delivers a database-backed RBAC plugin for CakePHP 5.x with CakeDC/Users integration. The plugin extends the CakePHP Authorization framework with a custom DatabaseResolver, providing runtime-manageable permissions through an admin UI. The 8-phase structure follows strict dependency ordering: database foundation → service logic → authorization integration → middleware bootstrap → view helpers → admin UI → developer tools → documentation.

**Total Requirements:** 33 v1 requirements
**Phases:** 8
**Depth:** Standard (5-8 phases)

---

## Phases

### Phase 1: Database Foundation

**Goal:** Database schema established with all tables, associations, indexes, and seed data ready for service layer queries.

**Dependencies:** None (foundation phase)

**Requirements:**
- DB-01: Roles table with name, description, is_active, timestamps
- DB-02: Permissions table supporting string-based and controller/action-based permissions
- DB-03: `users_roles` pivot table with unique constraint
- DB-04: `users_permissions` pivot table with unique constraint
- DB-05: `roles_permissions` pivot table with unique constraint
- DB-06: Composite indexes on all pivot tables for performance
- DB-07: Migrations for all tables (reversible)
- DB-08: Default seed data (superadmin, admin, user roles with common permissions)
- QA-01: Unit tests for model layer (Table classes, associations)

**Success Criteria:**
1. All five tables can be created via migrations and rolled back cleanly
2. Seed data creates three roles (superadmin, admin, user) with permissions protected by unique constraints
3. Model associations load related records correctly (roles with permissions, users with roles)
4. Composite indexes on pivot tables improve query performance (verified via EXPLAIN)
5. Unit tests validate model associations and constraints

---

### Phase 2: Service Layer

**Goal:** Core business logic for permission checking, caching, and superadmin bypass isolated in reusable service class.

**Dependencies:** Phase 1 (requires database tables and models)

**Requirements:**
- AUTH-01: `RbacService::can()` for programmatic permission checking
- AUTH-02: Superadmin role is system-managed (has all permissions, hidden from roles CRUD, only assignable during user create/edit)
- AUTH-08: Permission caching with auto-invalidation on role/permission changes
- QA-01: Unit tests for RbacService (permission resolution, caching, superadmin bypass)

**Success Criteria:**
1. `RbacService::can($user, $permission)` returns true/false based on user's roles and direct permissions
2. Superadmin role bypasses all permission checks (always returns true)
3. Permission lookups are cached per-request (second call uses cache, verified via test mocks)
4. Cache invalidates immediately when role-permission assignments change
5. Unit tests cover permission resolution with multiple roles, direct permissions, and superadmin bypass

---

### Phase 3: Authorization Integration

**Goal:** CakePHP Authorization framework integrated with custom DatabaseResolver for middleware-enforced permission checks.

**Dependencies:** Phase 2 (requires RbacService for permission resolution)

**Requirements:**
- AUTH-03: CakePHP Authorization integration with custom DatabaseResolver
- AUTH-04: RbacPolicy implementing RequestPolicyInterface for middleware enforcement
- AUTH-05: Middleware for automatic controller/action permission enforcement (overridable from host app)
- AUTH-06: `$this->Identity->can()` integration with DB-backed permissions
- AUTH-07: Multi-role support (single role default, `multiRoles=true` to enable)
- QA-01: Unit tests for DatabaseResolver and RbacPolicy

**Success Criteria:**
1. DatabaseResolver queries RbacService for permission checks (delegates to service layer)
2. RbacPolicy intercepts requests and checks permissions via `controller.action` format
3. `$this->Identity->can('posts.edit')` returns true/false based on database permissions
4. Multi-role config controls whether users can have one role (default) or multiple roles
5. Unit tests validate resolver integration with Authorization framework

---

### Phase 4: Plugin Bootstrap & Middleware

**Goal:** Plugin fully bootstrapped with correct middleware order, event listeners for cache invalidation, and host app configuration.

**Dependencies:** Phase 3 (requires authorization components to bootstrap)

**Requirements:**
- QA-02: Integration tests for middleware enforcement (verify requests are blocked/allowed correctly)

**Success Criteria:**
1. Plugin.php registers middleware in correct order (after Authentication, before application code)
2. Event listeners invalidate cache when roles/permissions change (verified via integration test)
3. Host app can load plugin via `addPlugin()` without additional middleware configuration
4. Integration tests verify unauthorized requests return 403, authorized requests succeed
5. Middleware order validation throws clear error if Authentication not loaded first

---

### Phase 5: View Layer

**Goal:** Template permission checks available via RbacHelper with request-level caching to avoid N+1 queries.

**Dependencies:** Phase 2 (requires RbacService for permission checks)

**Requirements:**
- VIEW-01: RbacHelper with `$this->Rbac->can()` for template permission checks
- VIEW-02: `$this->Rbac->hasRole()` for role checks in templates
- VIEW-03: Request-level caching in view helper (avoid repeated DB queries)

**Success Criteria:**
1. `$this->Rbac->can('posts.edit')` in templates returns true/false without direct service access
2. `$this->Rbac->hasRole('admin')` checks if current user has specific role
3. Helper caches permission checks within single request (verified via test query count)
4. Helper works in loops without N+1 queries (50 permission checks = 1 DB query)

---

### Phase 6: Admin UI

**Goal:** Full CRUD interface for roles, permissions, role-permission matrix, and user assignments with CakePHP default layout.

**Dependencies:** Phase 5 (requires RbacHelper for UI permission checks), Phase 4 (requires middleware to protect admin UI)

**Requirements:**
- UI-01: Role management (list, create, edit, delete) with CakePHP 5 default layout — superadmin role hidden
- UI-02: Permission management (list, create, edit, delete)
- UI-03: Permission matrix (role-permission assignment checkbox grid) — superadmin excluded
- UI-04: User role assignment interface — superadmin assignable only during user create/edit
- UI-05: User direct permission assignment interface
- UI-06: Admin UI protected by RBAC permissions (`rbac.*` namespace)
- QA-02: Integration tests for admin UI controllers

**Success Criteria:**
1. Admins can create/edit/delete roles via web UI (superadmin role hidden from list)
2. Admins can create/edit/delete permissions via web UI
3. Permission matrix displays checkbox grid for bulk role-permission assignment (superadmin excluded)
4. User role assignment interface allows assigning superadmin only (not creating new superadmin role)
5. All admin UI routes require `rbac.*` permissions (verified via integration test of unauthorized access)
6. Integration tests verify CRUD operations and permission protection

---

### Phase 7: Developer Tools

**Goal:** CLI commands for permission auto-discovery and seeding to simplify deployment and maintenance.

**Dependencies:** Phase 6 (requires full system to be functional)

**Requirements:**
- DEV-01: Controller/action auto-discovery from routes
- DEV-02: CLI command for permission sync (`bin/cake rbac discover`)
- DEV-03: Seed command (`bin/cake rbac seed`)

**Success Criteria:**
1. `bin/cake rbac discover` scans routes and creates missing permissions in database
2. `bin/cake rbac seed` creates default roles and permissions (safe to run multiple times)
3. Auto-discovery skips internal/plugin routes via whitelist configuration
4. Commands provide clear output showing created/skipped/updated permissions

---

### Phase 8: Documentation & Polish

**Goal:** Complete documentation for installation, configuration, usage, and API reference ready for public release.

**Dependencies:** Phase 7 (requires all features complete to document)

**Requirements:**
- QA-03: PHPDoc documentation for all public APIs
- QA-04: README with installation, configuration, and usage documentation
- QA-05: `docs/**/*.md` detailed documentation for each feature area

**Success Criteria:**
1. All public methods have PHPDoc blocks with parameter/return types and examples
2. README covers installation (composer require), basic configuration, quick start example
3. Documentation includes separate guides for: Configuration, Admin UI, View Helper, Service Layer, CLI Commands
4. Code examples in documentation are tested and working
5. Documentation covers superadmin role restrictions and security considerations

---

## Progress

| Phase | Status | Requirements | Completion |
|-------|--------|--------------|------------|
| 1 - Database Foundation | Pending | 9 | 0% |
| 2 - Service Layer | Pending | 4 | 0% |
| 3 - Authorization Integration | Pending | 6 | 0% |
| 4 - Plugin Bootstrap & Middleware | Pending | 1 | 0% |
| 5 - View Layer | Pending | 3 | 0% |
| 6 - Admin UI | Pending | 7 | 0% |
| 7 - Developer Tools | Pending | 3 | 0% |
| 8 - Documentation & Polish | Pending | 3 | 0% |

**Overall:** 0/33 requirements complete (0%)

---

*Last updated: 2026-02-04*
