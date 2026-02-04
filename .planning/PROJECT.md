# CakePHPMitra/RBAC

## What This Is

A standalone CakePHP 5 plugin providing database-backed Role-Based Access Control with CakeDC/Users integration. Manages roles, permissions, and user assignments via pivot tables with a full admin UI. Designed to be reusable across any CakePHP 5 project that uses CakeDC/Users for authentication.

## Core Value

Enable runtime-manageable, granular permission control through a database-backed system with admin UI — replacing CakeDC/Users' single-column role approach and static config-file permissions.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Database tables: roles, permissions, users_roles, users_permissions (pivot tables)
- [ ] Role model with CRUD operations
- [ ] Permission model supporting both string-based (`settings.view`) and controller/action-based permissions
- [ ] Users can be assigned roles (many-to-many via users_roles)
- [ ] Users can be assigned direct permissions (many-to-many via users_permissions)
- [ ] Configurable multi-role support (single role default, `multiRoles=true` to enable)
- [ ] Configurable superadmin bypass role (defaults to 'superadmin' if not configured)
- [ ] CakePHP Authorization plugin integration with custom DB-backed resolver
- [ ] Middleware for automatic controller/action permission enforcement (overridable from host app)
- [ ] Service class for manual permission checks (`RbacService::can()`)
- [ ] View helper for permission checks in templates (`$this->Rbac->can()`)
- [ ] Integration with CakePHP Identity (`$this->Identity->can()`)
- [ ] Permission caching with auto-invalidation (enabled by default, configurable)
- [ ] Controller/action auto-discovery for permission registration
- [ ] Admin UI: Role management (list, create, edit, delete)
- [ ] Admin UI: Permission management (list, create, edit, delete)
- [ ] Admin UI: Permission matrix (role-permission assignment grid)
- [ ] Admin UI: User role/permission assignment
- [ ] Default seed data: superadmin, admin, user roles with common permissions
- [ ] Migrations for all database tables
- [ ] CakePHP 5 default frontend layout for admin UI (standard bake-style templates)

### Out of Scope (This Milestone)

- Custom UI themes or Tailwind styling — host app responsibility
- User management (CRUD) — CakeDC/Users handles this
- Authentication — CakeDC/Users handles this
- API/REST endpoints for RBAC management — future milestone
- Hierarchical roles (role inheritance) — future milestone
- Resource-level permissions (e.g., "can edit THIS article") — future milestone, plugin handles action-level only

## Context

**Plugin ecosystem position:**
```
CakeDC/Users (authentication) → CakePHPMitra/RBAC (authorization) → Host App
                                      ↑
                              CakePHP Authorization plugin (framework)
```

**CakeDC/Users limitations this solves:**
- Single `role` column on users table — no multiple roles per user
- `config/permissions.php` is static — no runtime changes without deploy
- No granular permissions — only role-level access control
- No admin UI for permission management

**Integration points:**
- CakeDC/Users provides user identity (authentication)
- CakePHP Authorization plugin provides the framework (policies, middleware)
- This plugin provides the DB-backed resolver that checks permissions against database tables
- CakePHPMitra/dbconfig can integrate for permission checking on its settings pages

**Host app setup pattern:**
- Install plugin, run migrations, load in Application.php
- Plugin registers its Authorization middleware and resolver
- Host app can override middleware behavior
- Permissions discoverable from routes or manually registered

## Constraints

- **Tech Stack**: CakePHP 5.x, PHP 8.1+, CakePHP Authorization ^3.0, CakeDC/Users ^16.0
- **UI**: Must use CakePHP 5 default frontend layout (standard bake-style templates)
- **Compatibility**: Must work alongside CakeDC/Users without conflicts
- **Reusability**: Plugin must be fully standalone — no host-app-specific code
- **Performance**: Permission checks happen on every request — caching is essential

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| CakePHP Authorization + DB resolver | Ecosystem compatible, other plugins integrate with it | -- Pending |
| Both string-based and controller/action permissions | Flexibility for custom perms + automatic route-based enforcement | -- Pending |
| Multi-role configurable (default: single) | Simple default, advanced when needed | -- Pending |
| Superadmin bypass configurable (default: 'superadmin') | Convention over configuration, overridable | -- Pending |
| Caching enabled by default | Performance critical for per-request checks | -- Pending |
| Both role-based and direct user permissions | Maximum flexibility for edge cases | -- Pending |
| CakePHP default frontend layout | Reusable across projects, no custom CSS dependency | -- Pending |

---
*Last updated: 2026-02-04 after initialization*
