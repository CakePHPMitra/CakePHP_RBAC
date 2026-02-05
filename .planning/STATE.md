# Project State: CakePHPMitra/RBAC

## Project Reference

**Core Value:** Runtime-manageable, granular permission control through database-backed RBAC system with admin UI — replacing CakeDC/Users' single-column role and static config-file permissions.

**Current Focus:** Phase 1 - Database Foundation (establish schema, models, associations, indexes, seed data)

---

## Current Position

**Phase:** 1 of 8 (Database Foundation)
**Plan:** 1 of 3 complete
**Status:** In progress
**Last activity:** 2026-02-05 - Completed 01-01-PLAN.md (migrations)

**Progress:** [###.......] 33% (1/3 plans in phase)

**Next Actions:**
1. Execute 01-02-PLAN.md (Table classes with ORM associations)
2. Execute 01-03-PLAN.md (seed data)
3. Run migrations to verify schema

---

## Performance Metrics

| Metric | Current | Target | Status |
|--------|---------|--------|--------|
| Phases Complete | 0/8 | 8/8 | In Progress |
| Requirements Complete | 2/33 | 33/33 | In Progress |
| Plans Complete | 1/~24 | ~24 | In Progress |
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
| - [ ] Cache strategy selected | -- | Pending |
| - [ ] Middleware integration approach | -- | Pending |
| - [ ] Multi-role resolution strategy | -- | Pending |

### Active TODOs

- Run `bin/cake migrations migrate --plugin Rbac` to apply schema (after Table classes complete)
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
**Session Summary:** Completed 01-01-PLAN.md - created 5 reversible migrations for RBAC schema (roles, permissions, users_roles, users_permissions, roles_permissions). All tables have proper indexes and foreign key constraints.

**Stopped at:** Completed 01-01-PLAN.md
**Resume file:** .planning/phases/01-database-foundation/01-02-PLAN.md

**For Next Session:**
1. Execute 01-02-PLAN.md to create Table classes with ORM associations
2. Execute 01-03-PLAN.md to create seed data
3. Run migrations and verify schema in host app

**Context to Preserve:**
- Superadmin role must be system-managed: hidden from CRUD, cannot be created manually, only assignable
- Plugin must extend (not replace) CakePHP Authorization framework with DatabaseResolver
- CakeDC/Users integration critical: plugin must not conflict with existing middleware
- Performance critical: composite indexes on pivot tables from initial migrations (not added later)
- Testing strategy: Unit tests in each phase, integration tests after middleware/UI complete

---

*Last updated: 2026-02-05*
