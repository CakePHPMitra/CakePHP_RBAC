# Project Research Summary

**Project:** CakePHPMitra/RBAC
**Domain:** CakePHP 5.x Plugin - Role-Based Access Control System
**Researched:** 2026-02-04
**Confidence:** HIGH

## Executive Summary

This project creates a database-backed RBAC plugin for CakePHP 5.x that integrates with the CakeDC/Users and CakePHP Authorization ecosystem. Unlike static config-file-based permissions, this plugin allows runtime permission management through an admin UI while maintaining the authorization patterns established by CakeDC/Auth. The architecture extends (not replaces) the Authorization framework with a custom DatabaseResolver that checks permissions against a relational database schema with intelligent caching.

The recommended approach follows the CakeDC ecosystem patterns: build a custom policy resolver that plugs into CakePHP's Authorization middleware stack, use a service layer for permission logic, and provide admin UI for runtime management. The stack is minimal (CakePHP 5 + Authorization + CakeDC/Users), the database schema is standard many-to-many (roles, permissions, pivot tables), and the integration point is a custom AuthorizationServiceLoader that replaces CakeDC/Auth's config-based RbacPolicy with a database-backed resolver.

The critical risks center on middleware integration (must load after Authentication), cache invalidation (race conditions on permission updates), and superadmin security (privilege escalation if not protected). Mitigations include providing a MiddlewareQueueLoader to ensure correct middleware order, using version-based cache keys for immediate invalidation, and hardcoding superadmin role protection at the database layer.

## Key Findings

### Recommended Stack

The plugin uses CakePHP 5's official Authorization library as its foundation, extending it with a custom database resolver. This approach minimizes dependencies (only CakePHP core + Authorization + CakeDC/Users) and follows established CakeDC patterns. The database-backed resolver replaces static config files while maintaining compatibility with the Authorization middleware, policies, and identity integration that host applications already use.

**Core technologies:**
- **CakePHP 5.0+**: Core framework - required for host app compatibility, provides ORM, middleware, and plugin architecture
- **CakePHP Authorization 3.0**: Official authorization framework - provides middleware, resolvers, policies, identity integration; plugin extends with custom resolver
- **CakeDC/Users 16.0**: Authentication provider - industry standard for CakePHP, provides OAuth, 2FA, user lifecycle; already required by host apps
- **CakeDC/Auth 10.1**: Authorization helpers - provides RbacPolicy, SuperuserPolicy patterns that we extend with database backend
- **MariaDB/MySQL 10.11+/8.0+**: Primary database - most common deployment target, plugin schema works with PostgreSQL too

**Critical decision:** Extend CakeDC/Auth patterns (config-based RbacPolicy → database-backed resolver) rather than build from scratch. This ensures ecosystem compatibility and follows patterns host apps already understand.

### Expected Features

**Must have (table stakes):**
- Roles and permissions tables with many-to-many relationships - foundation of RBAC
- User role assignment UI - admins must manage without DB access
- Permission assignment UI - role-permission matrix for bulk operations
- Permission checking via `$identity->can()` - automatic middleware enforcement
- Superadmin bypass - configurable role that skips all permission checks
- Permission caching - per-request checks are expensive, must cache user permissions
- Direct user permissions - override role permissions for edge cases
- Integration with CakeDC/Users - seamless identity/authentication integration

**Should have (competitive):**
- Controller/action auto-discovery - scan routes to generate permissions automatically
- Dual permission types - string-based ('posts.edit') AND controller/action based
- Permission matrix UI - visual checkbox grid for bulk role-permission assignment
- Multi-role support (configurable) - single role default, enable multi-role via config
- Permission groups/categories - organize permissions logically by resource

**Defer (v2+):**
- Permission wildcards (`posts.*`) - nice-to-have, adds complexity
- Resource-level permissions - "can edit THIS post" requires policy classes
- Role hierarchy/inheritance - niche use case, complex edge cases
- Audit log - valuable but not blocking
- API endpoints - focus on web UI first
- CLI commands - convenience feature

### Architecture Approach

The architecture follows a layered integration pattern where the plugin provides a custom AuthorizationServiceLoader that configures a DatabaseResolver to replace file-based policies. At runtime, Authorization middleware checks permissions via the custom RbacPolicy, which delegates to RbacService for database queries. RbacService caches user permissions (roles + direct permissions) to minimize database overhead. The admin UI is a standard CakePHP CRUD interface protected by RBAC permissions.

**Major components:**
1. **DatabaseResolver + RbacPolicy** - Custom policy resolver that queries database instead of config files; implements CakePHP Authorization interfaces
2. **RbacService** - Core business logic for permission checking, caching, superadmin bypass; reusable outside request cycle
3. **AuthorizationServiceLoader** - Replaces CakeDC's loader to inject database-backed resolver into Authorization middleware stack
4. **Model Layer** - Five tables (roles, permissions, users_roles, users_permissions, roles_permissions) with standard CakePHP associations
5. **View Layer** - RbacHelper for template permission checks; admin UI controllers for CRUD operations
6. **Event Listeners** - Cache invalidation on permission changes; auto-discovery on route updates

**Data flow:** Request → Authentication sets identity → Authorization middleware injects service → RbacPolicy checks permission → RbacService queries cached permissions → returns boolean → middleware allows/denies

### Critical Pitfalls

1. **CakeDC/Users collision** - CakeDC/Users ships with its own authorization middleware; installing both creates competing systems. Solution: Provide RbacMiddlewareQueueLoader that extends CakeDC's loader but replaces authorization middleware with plugin's database-backed version.

2. **Cache invalidation race conditions** - User permissions cached at request start; admin updates permissions mid-request; active requests complete with stale permissions. Solution: Use version-based cache keys (increment version on permission change) or immediate invalidation flags.

3. **Middleware order dependency** - Authorization requires Authentication to run first; incorrect order causes `IdentityNotFoundException`. Solution: Plugin provides MiddlewareQueueLoader to enforce order, adds runtime validation with clear error messages.

4. **Superadmin privilege escalation** - If superadmin role name is configurable and admin UI allows editing roles, users can promote themselves. Solution: Hardcode superadmin role name, protect at database layer (prevent name changes), require superadmin permission to assign superadmin role.

5. **Performance cliff at scale** - Works with 100 users but 10K users with 50 roles causes 2-3 second queries. Solution: Composite indexes on all pivot tables (`user_id, role_id`), optimized UNION query for all permissions, eager loading at authentication time.

## Implications for Roadmap

Based on research, suggested phase structure follows dependency order: database foundation → service logic → authorization integration → middleware bootstrap → view helpers → admin UI → optional tooling.

### Phase 1: Database Foundation
**Rationale:** Everything depends on schema; models must exist before service layer can query them. Critical indexes must be in initial migrations (not added later) to avoid performance issues.
**Delivers:** Five tables with associations, composite indexes, seed data (superadmin, admin, user roles)
**Addresses:** Table stakes (roles, permissions, pivot tables), performance pitfall (indexes from day 1)
**Avoids:** Performance cliff at scale (pitfall #5)
**Research flag:** Standard CakePHP migration patterns, skip research

### Phase 2: Service Layer
**Rationale:** Policy and helpers depend on RbacService; must exist before authorization integration. Core business logic isolated from framework concerns.
**Delivers:** RbacService with permission checking, caching, superadmin bypass
**Uses:** CakePHP Cache component, ORM queries
**Implements:** Cache-aside pattern (check cache, query DB on miss)
**Avoids:** Direct database queries in policies (anti-pattern)
**Research flag:** Standard service pattern, skip research

### Phase 3: Authorization Integration
**Rationale:** Middleware depends on policies/resolvers working correctly. This is the critical integration point with CakePHP Authorization framework.
**Delivers:** DatabaseResolver, RbacPolicy, AuthorizationServiceLoader
**Uses:** CakePHP Authorization 3.0 (ResolverInterface, RequestPolicyInterface)
**Implements:** Custom resolver pattern extending CakeDC/Auth patterns
**Avoids:** CakeDC/Users collision (pitfall #1) - custom loader replaces competing middleware
**Research flag:** May need deeper research on Authorization 3.0 resolver API if docs unclear

### Phase 4: Middleware + Plugin Bootstrap
**Rationale:** Admin UI depends on authorization working; must be tested end-to-end before building UI
**Delivers:** Plugin.php with middleware(), bootstrap(), services(); config/rbac.php; event listeners
**Uses:** MiddlewareQueueLoader to ensure correct middleware order
**Implements:** Cache invalidation via event listeners
**Avoids:** Middleware order dependency (pitfall #3) - runtime validation with clear errors
**Research flag:** Standard CakePHP plugin bootstrap, skip research

### Phase 5: View Layer
**Rationale:** Admin UI templates use RbacHelper for permission checks; must exist before UI
**Delivers:** RbacHelper with request-level caching, basic admin layout
**Uses:** CakePHP View component
**Implements:** Request-scoped cache to avoid N+1 permission checks in loops
**Avoids:** View helper performance in loops (pitfall #11)
**Research flag:** Standard view helper pattern, skip research

### Phase 6: Admin UI
**Rationale:** Depends on all previous layers; provides runtime management capabilities
**Delivers:** CRUD controllers for roles/permissions, role-permission matrix, user role assignment
**Uses:** Standard CakePHP scaffolding patterns
**Implements:** Permission protection on admin UI itself (rbac.roles.manage, rbac.permissions.manage)
**Avoids:** Admin UI not protected (pitfall #9) - seed rbac.* permissions for superadmin only
**Research flag:** Standard CRUD + permission matrix UI may need research for grid implementation

### Phase 7: Auto-Discovery (Optional)
**Rationale:** Nice-to-have feature; defer until core functionality proven
**Delivers:** Command to scan routes and generate permissions, sync permissions on deploy
**Uses:** CakePHP Router reflection API
**Implements:** Whitelist-based discovery (only registered routes, skip internal methods)
**Avoids:** Invalid permission discovery (pitfall #8)
**Research flag:** May need research on Router API for route introspection

### Phase Ordering Rationale

- **Sequential dependencies:** 1 → 2 → 3 → 4 (cannot parallelize, each builds on previous)
- **Parallel opportunity:** Phase 5 (View Layer) could start once Phase 2 completes (doesn't depend on middleware)
- **MVP cutoff:** Phases 1-6 required for usable plugin; Phase 7 is polish/convenience
- **Risk mitigation:** Phase 3 (Authorization Integration) is highest risk (CakeDC collision); resolve early
- **Testing gates:** Phase 2 must have unit tests before Phase 3; Phase 4 must have integration tests before Phase 6

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 3 (Authorization Integration):** Complex integration with CakePHP Authorization 3.0; resolver API patterns may need verification with official docs
- **Phase 6 (Admin UI - Matrix):** Permission matrix checkbox grid implementation; may need UI pattern research for bulk assignment

Phases with standard patterns (skip research-phase):
- **Phase 1 (Database Foundation):** Standard CakePHP migrations, ORM associations, indexing patterns
- **Phase 2 (Service Layer):** Standard service class with cache-aside pattern
- **Phase 4 (Middleware + Bootstrap):** Standard plugin bootstrap, event listeners
- **Phase 5 (View Layer):** Standard view helper with caching
- **Phase 7 (Auto-Discovery):** Standard command class with Router API

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Verified via CakeDC/Users, CakePHP Authorization source code; CloudPE Admin reference implementation |
| Features | MEDIUM | Based on training data through Jan 2025; Laravel Spatie comparison, RBAC best practices |
| Architecture | HIGH | Verified via CakeDC/Users AuthorizationServiceLoader, CakeDC/Auth RbacPolicy patterns |
| Pitfalls | MEDIUM | Inferred from CakePHP middleware patterns + CloudPE Admin analysis; needs validation during implementation |

**Overall confidence:** HIGH for architecture/stack, MEDIUM for feature priorities/pitfalls

### Gaps to Address

**During Phase 1-2 implementation:**
- Validate CakePHP Authorization 3.0 resolver API hasn't changed (research based on 3.0 initial release)
- Test CakeDC/Users 16.0 middleware integration in sandbox before committing to loader pattern
- Benchmark permission query performance with realistic data (1000 users, 20 roles, 200 permissions)

**During Phase 3 planning:**
- Research CakePHP Authorization 3.0 documentation for custom resolver requirements
- Validate MiddlewareQueueLoader extension pattern with CakeDC/Users source code
- Document exact middleware order requirements for host apps

**During Phase 4-6 implementation:**
- Validate cache invalidation strategy under load (test race conditions)
- Security audit superadmin protection before shipping admin UI
- Test multi-role permission resolution (union vs intersection)

**Open questions requiring validation:**
- Does CakeDC/Users 16.0 support custom AuthorizationServiceLoader override?
- What's the correct way to skip CakeDC's Authorization middleware but keep Authentication?
- How to handle host apps with existing custom Authorization policies?
- What cache backend do most CakePHP 5 apps use (files, Redis, Memcached)?

## Sources

### Primary (HIGH confidence)
- **CakePHP Authorization 3.x source** - `/vendor/cakephp/authorization/src/` - middleware patterns, resolver interfaces, policy patterns
- **CakeDC/Users 16.0 source** - `/vendor/cakedc/users/src/` - AuthorizationServiceLoader pattern, MiddlewareQueueLoader pattern
- **CakeDC/Auth 10.1 source** - `/vendor/cakedc/auth/src/` - RbacPolicy implementation, config-based permission checking
- **CloudPE Admin codebase** - Working reference implementation using CakeDC/Users + Authorization stack

### Secondary (MEDIUM confidence)
- **CakePHP 5 documentation** - Official patterns for migrations, plugins, testing (training data through Jan 2025)
- **Laravel Spatie Permissions** - Reference standard for PHP RBAC features (training data, version may have advanced)
- **RBAC best practices** - NIST RBAC model, standard security patterns (least privilege, audit trails)

### Tertiary (LOW confidence)
- **Feature priorities** - Inferred from training data on RBAC systems; needs validation with CakePHP community
- **Pitfall likelihood** - Based on first principles + architectural analysis; should be validated during Phase 1-2

---
*Research completed: 2026-02-04*
*Ready for roadmap: yes*
