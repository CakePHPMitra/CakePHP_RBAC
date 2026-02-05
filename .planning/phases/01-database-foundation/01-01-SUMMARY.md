---
phase: 01-database-foundation
plan: 01
subsystem: database
tags: [cakephp, migrations, rbac, pivot-tables, foreign-keys]

# Dependency graph
requires: []
provides:
  - Roles table with is_system, is_default, sort_order, parent_id, soft delete
  - Permissions table with dotted name format and soft delete
  - users_roles pivot table with composite PK and UUID foreign key
  - users_permissions pivot table with composite PK and UUID foreign key
  - roles_permissions pivot table with composite PK
  - All foreign key constraints for referential integrity
affects: [01-02, 01-03, 02-service-layer, 03-authorization]

# Tech tracking
tech-stack:
  added: [cakephp/migrations]
  patterns: [composite-primary-keys, reversible-migrations, foreign-key-constraints]

key-files:
  created:
    - config/Migrations/20260205000001_CreateRolesTable.php
    - config/Migrations/20260205000002_CreatePermissionsTable.php
    - config/Migrations/20260205000003_CreateUsersRolesTable.php
    - config/Migrations/20260205000004_CreateUsersPermissionsTable.php
    - config/Migrations/20260205000005_CreateRolesPermissionsTable.php
  modified: []

key-decisions:
  - "Composite primary keys for pivot tables (no separate id column)"
  - "UUID type for user_id columns matching CakeDC/Users"
  - "RESTRICT delete on roles/permissions when users assigned"
  - "CASCADE delete on user deletion for pivot assignments"

patterns-established:
  - "Reversible migrations: Use change() method for all table creation"
  - "Composite unique indexes: All pivot tables have unique constraint on FK pair"
  - "Foreign key naming: fk_{table}_{column} convention"

# Metrics
duration: 5min
completed: 2026-02-05
---

# Phase 1 Plan 1: Database Migration Schema Summary

**Five reversible migrations establishing RBAC tables with composite primary keys, UUID foreign keys for CakeDC/Users compatibility, and referential integrity constraints**

## Performance

- **Duration:** 5 min
- **Started:** 2026-02-05T15:49:55Z
- **Completed:** 2026-02-05T15:55:00Z
- **Tasks:** 2
- **Files created:** 5

## Accomplishments
- Created roles table with all schema flexibility columns (is_system, is_default, sort_order, parent_id, deleted_at)
- Created permissions table with dotted name format support and soft delete
- Created three pivot tables with composite primary keys (no separate id column)
- Established foreign key constraints with appropriate CASCADE/RESTRICT rules per CONTEXT.md
- All migrations reversible using change() method

## Task Commits

Each task was committed atomically:

1. **Task 1: Create roles and permissions table migrations** - `691895d` (feat)
2. **Task 2: Create pivot table migrations with foreign keys** - `7e8ad83` (feat)

## Files Created

- `config/Migrations/20260205000001_CreateRolesTable.php` - Roles table with is_system, is_default, sort_order, parent_id, deleted_at
- `config/Migrations/20260205000002_CreatePermissionsTable.php` - Permissions table with dotted name format, soft delete
- `config/Migrations/20260205000003_CreateUsersRolesTable.php` - Users-Roles pivot with UUID user_id, RESTRICT role delete
- `config/Migrations/20260205000004_CreateUsersPermissionsTable.php` - Users-Permissions pivot with UUID user_id, RESTRICT permission delete
- `config/Migrations/20260205000005_CreateRolesPermissionsTable.php` - Roles-Permissions pivot with CASCADE delete both ways

## Decisions Made

| Decision | Rationale |
|----------|-----------|
| Composite primary keys on pivots | Cleaner schema, saves 4 bytes/row, follows CakePHP 5 conventions |
| UUID type for user_id | Matches CakeDC/Users primary key type exactly |
| RESTRICT on role/permission delete when users assigned | Prevents orphaned user assignments, forces explicit reassignment |
| CASCADE on roles_permissions | Role-permission links are metadata, not critical assignments |
| Soft delete columns added | Future-proofing per CONTEXT.md, behavior attachment configurable |

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Database schema complete, ready for Table classes (Plan 01-02)
- All 5 migrations can be run with `bin/cake migrations migrate --plugin Rbac`
- Rollback supported via `bin/cake migrations rollback --plugin Rbac`
- Foreign key constraints require users table to exist (CakeDC/Users)

---
*Phase: 01-database-foundation*
*Plan: 01*
*Completed: 2026-02-05*
