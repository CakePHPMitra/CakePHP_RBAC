# Project State: CakePHPMitra/RBAC

## Project Reference

**Core Value:** Runtime-manageable, granular permission control through database-backed RBAC system with admin UI — replacing CakeDC/Users' single-column role and static config-file permissions.

**Current Focus:** Phase 1 - Database Foundation (establish schema, models, associations, indexes, seed data)

---

## Current Position

**Phase:** 1 of 8 (Database Foundation)
**Plan:** 3 of 3 complete
**Status:** Phase complete
**Last activity:** 2026-02-09 - Completed 01-03-PLAN.md (seed data)

**Progress:** [##########] 100% (3/3 plans in phase)

**Next Actions:**
1. Run migrations and seeds to verify schema: `bin/cake migrations migrate --plugin Rbac && bin/cake migrations seed --plugin Rbac`
2. Verify ORM associations work with seeded data
3. Begin Phase 2 (Service Layer)

---

## Performance Metrics

| Metric | Current | Target | Status |
|--------|---------|--------|--------|
| Phases Complete | 1/8 | 8/8 | In Progress |
| Requirements Complete | 6/33 | 33/33 | In Progress |
| Plans Complete | 3/~24 | ~24 | In Progress |
| Tests Passing | 0 | TBD | Not Started |
| Documentation Complete | 0/3 | 3/3 | Not Started |

---

## Accumulated Context

### Key Decisions

| Decision | Phase | Rationale |
|----------|-------|-----------|
| Composite primary keys on pivot tables | 01-01 | Cleaner schema, saves space, CakePHP 5 convention |
| UUID type for user_id foreign keys | 01-01 | Matches CakeDC/Users primary key exactly |
| RESTRICT delete on roles/permissions when users assigned | 01-01 | Prevents orphaned assignments |
| Soft delete columns (deleted_at) included | 01-01 | Future-proofing, behavior configurable |
| belongsToMany with explicit through option | 01-02 | Enables pivot tables with timestamps and ORM containment |
| Permission name regex /^[a-zA-Z0-9.]+$/ | 01-02 | Enforces dotted format for consistent naming |
| buildRules for unique constraints | 01-02 | Database-level enforcement prevents race conditions |
| saveStrategy: 'append' on belongsToMany | 01-02 | Prevents accidental removal of existing associations |
| Superadmin gets zero permission assignments | 01-03 | Bypass logic handled in service layer, not via permission grants |
| Idempotency via SELECT COUNT checks | 01-03 | Simple, reliable check - seeds can run multiple times safely |
| Seed dependency ordering | 01-03 | RolesPermissionsSeed requires role/permission IDs from previous seeds |
| - [ ] Cache strategy selected | -- | Pending |
| - [ ] Middleware integration approach | -- | Pending |
| - [ ] Multi-role resolution strategy | -- | Pending |

### Active TODOs

- Run `bin/cake migrations migrate --plugin Rbac` to apply schema in host app
- Run `bin/cake migrations seed --plugin Rbac` to populate default data
- Verify foreign keys work with CakeDC/Users users table
- Test ORM associations with contain() using seeded data

### Blockers

*No blockers identified*

### Deferred Items

*Items from v2 scope (see REQUIREMENTS.md):*
- Resource-level permissions (policy classes required)
- Hierarchical roles (role inheritance)
- Permission wildcards (`posts.*`)
- API/REST endpoints for RBAC management
- Audit log for permission changes
- Import/export permissions

---

## Session Continuity

**Last Session:** 2026-02-09
**Session Summary:** Completed 01-03-PLAN.md - created 3 idempotent seed files (RolesSeed, PermissionsSeed, RolesPermissionsSeed) providing default roles, permissions, and role-permission assignments. Phase 1 (Database Foundation) now complete.

**Stopped at:** Completed 01-03-PLAN.md (Phase 1 complete)
**Resume file:** .planning/phases/02-service-layer/02-01-PLAN.md (if exists)

**For Next Session:**
1. Verify Phase 1 in host app: run migrations and seeds, test ORM associations
2. Begin Phase 2 (Service Layer): Create RbacService with permission checking logic
3. Implement superadmin bypass (check is_system flag)

**Context to Preserve:**
- Superadmin role must be system-managed: hidden from CRUD, cannot be created manually, only assignable
- Plugin must extend (not replace) CakePHP Authorization framework with DatabaseResolver
- CakeDC/Users integration critical: plugin must not conflict with existing middleware
- Performance critical: composite indexes on pivot tables from initial migrations (not added later)
- Testing strategy: Unit tests in each phase, integration tests after middleware/UI complete
- ORM pattern established: belongsToMany with through, saveStrategy: append

---

*Last updated: 2026-02-09*
