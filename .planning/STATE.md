# Project State: CakePHPMitra/RBAC

## Project Reference

**Core Value:** Runtime-manageable, granular permission control through database-backed RBAC system with admin UI — replacing CakeDC/Users' single-column role and static config-file permissions.

**Current Focus:** Phase 1 - Database Foundation (establish schema, models, associations, indexes, seed data)

---

## Current Position

**Phase:** 1 of 8 (Database Foundation)
**Plan:** 2 of 3 complete
**Status:** In progress
**Last activity:** 2026-02-05 - Completed 01-02-PLAN.md (ORM models)

**Progress:** [######....] 67% (2/3 plans in phase)

**Next Actions:**
1. Execute 01-03-PLAN.md (seed data)
2. Run migrations to verify schema
3. Begin Phase 2 (Service Layer)

---

## Performance Metrics

| Metric | Current | Target | Status |
|--------|---------|--------|--------|
| Phases Complete | 0/8 | 8/8 | In Progress |
| Requirements Complete | 4/33 | 33/33 | In Progress |
| Plans Complete | 2/~24 | ~24 | In Progress |
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
| - [ ] Cache strategy selected | -- | Pending |
| - [ ] Middleware integration approach | -- | Pending |
| - [ ] Multi-role resolution strategy | -- | Pending |

### Active TODOs

- Run `bin/cake migrations migrate --plugin Rbac` to apply schema (after seed data complete)
- Execute 01-03-PLAN.md for seed data
- Verify foreign keys work with CakeDC/Users users table

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

**Last Session:** 2026-02-05
**Session Summary:** Completed 01-02-PLAN.md - created 5 Table classes (RolesTable, PermissionsTable, UsersRolesTable, UsersPermissionsTable, RolesPermissionsTable) and 5 Entity classes with ORM associations and validation rules.

**Stopped at:** Completed 01-02-PLAN.md
**Resume file:** .planning/phases/01-database-foundation/01-03-PLAN.md

**For Next Session:**
1. Execute 01-03-PLAN.md to create seed data (default roles and permissions)
2. Run migrations and seeds in host app
3. Verify ORM associations work with contain()

**Context to Preserve:**
- Superadmin role must be system-managed: hidden from CRUD, cannot be created manually, only assignable
- Plugin must extend (not replace) CakePHP Authorization framework with DatabaseResolver
- CakeDC/Users integration critical: plugin must not conflict with existing middleware
- Performance critical: composite indexes on pivot tables from initial migrations (not added later)
- Testing strategy: Unit tests in each phase, integration tests after middleware/UI complete
- ORM pattern established: belongsToMany with through, saveStrategy: append

---

*Last updated: 2026-02-05*
