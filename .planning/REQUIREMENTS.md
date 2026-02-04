# Requirements: CakePHPMitra/RBAC

## v1 Requirements

### Database Foundation

- [ ] **DB-01**: Roles table with name, description, is_active, timestamps
- [ ] **DB-02**: Permissions table supporting both string-based (`settings.view`) and controller/action-based permissions
- [ ] **DB-03**: `users_roles` pivot table (user_id UUID, role_id integer) with unique constraint
- [ ] **DB-04**: `users_permissions` pivot table for direct user permissions with unique constraint
- [ ] **DB-05**: `roles_permissions` pivot table with unique constraint
- [ ] **DB-06**: Composite indexes on all pivot tables for performance
- [ ] **DB-07**: Migrations for all tables (reversible)
- [ ] **DB-08**: Default seed data: superadmin, admin, user roles with common permissions

### Authorization Core

- [ ] **AUTH-01**: `RbacService::can()` for programmatic permission checking
- [ ] **AUTH-02**: Superadmin role is system-managed — has all permissions (bypass), hidden from roles CRUD, cannot be created manually, only assignable during user create/edit
- [ ] **AUTH-03**: CakePHP Authorization integration with custom DatabaseResolver
- [ ] **AUTH-04**: RbacPolicy implementing RequestPolicyInterface for middleware enforcement
- [ ] **AUTH-05**: Middleware for automatic controller/action permission enforcement (overridable from host app)
- [ ] **AUTH-06**: `$this->Identity->can()` integration with DB-backed permissions
- [ ] **AUTH-07**: Multi-role support (single role default, `multiRoles=true` to enable)
- [ ] **AUTH-08**: Permission caching with auto-invalidation on role/permission changes

### Admin UI

- [ ] **UI-01**: Role management (list, create, edit, delete) with CakePHP 5 default layout — superadmin role hidden
- [ ] **UI-02**: Permission management (list, create, edit, delete)
- [ ] **UI-03**: Permission matrix (role-permission assignment checkbox grid) — superadmin excluded (has all permissions)
- [ ] **UI-04**: User role assignment interface — superadmin assignable only during user create/edit
- [ ] **UI-05**: User direct permission assignment interface
- [ ] **UI-06**: Admin UI protected by RBAC permissions (`rbac.*` namespace)

### View & Helpers

- [ ] **VIEW-01**: RbacHelper with `$this->Rbac->can()` for template permission checks
- [ ] **VIEW-02**: `$this->Rbac->hasRole()` for role checks in templates
- [ ] **VIEW-03**: Request-level caching in view helper (avoid repeated DB queries)

### Developer Tools

- [ ] **DEV-01**: Controller/action auto-discovery from routes
- [ ] **DEV-02**: CLI command for permission sync (`bin/cake rbac discover`)
- [ ] **DEV-03**: Seed command (`bin/cake rbac seed`)

### Quality

- [ ] **QA-01**: Unit tests for core authorization logic (RbacService, policies)
- [ ] **QA-02**: Integration tests for middleware and admin UI
- [ ] **QA-03**: PHPDoc documentation for all public APIs
- [ ] **QA-04**: README with installation, configuration, and usage documentation
- [ ] **QA-05**: `docs/**/*.md` detailed documentation for each feature area

---

## v2 Requirements (Deferred)

- Resource-level permissions (e.g., "can edit THIS article") — requires policy classes
- Hierarchical roles (role inheritance) — niche use case
- Permission wildcards (`posts.*`) — nice-to-have
- API/REST endpoints for RBAC management — web UI first
- Audit log for permission changes — valuable but not blocking
- Import/export permissions — operational convenience
- Team/tenant isolation — multi-tenant is a separate concern

## Out of Scope

- User management CRUD — CakeDC/Users handles this
- Authentication — CakeDC/Users handles this
- Custom UI themes — host app responsibility
- Frontend framework dependency — plain PHP templates only

## Traceability

| Requirement | Phase |
|-------------|-------|
| (Filled by roadmapper) | |

---
*Last updated: 2026-02-04*
