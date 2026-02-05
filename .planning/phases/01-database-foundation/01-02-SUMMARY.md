---
phase: 01-database-foundation
plan: 02
subsystem: database
tags: [cakephp, orm, associations, belongsToMany, entities, rbac]

# Dependency graph
requires:
  - phase: 01-database-foundation (01-01)
    provides: Database tables (roles, permissions, pivot tables) via migrations
provides:
  - RolesTable with belongsToMany Users and Permissions associations
  - PermissionsTable with belongsToMany Roles and Users associations
  - Three pivot Table classes (UsersRoles, UsersPermissions, RolesPermissions)
  - Five Entity classes with accessible fields and PHPDoc annotations
  - Unique name validation on Roles and Permissions
  - Permission name regex validation for dotted format
affects: [01-database-foundation (01-03 seeds), 02-service-layer, 03-authorization-integration]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - belongsToMany with explicit through option for pivot tables
    - Composite primary keys handled at database level (not ORM)
    - saveStrategy append to preserve existing associations
    - INNER joinType for required belongsTo associations
    - PHPDoc @property annotations for IDE/static analysis support

key-files:
  created:
    - src/Model/Table/RolesTable.php
    - src/Model/Table/PermissionsTable.php
    - src/Model/Table/UsersRolesTable.php
    - src/Model/Table/UsersPermissionsTable.php
    - src/Model/Table/RolesPermissionsTable.php
    - src/Model/Entity/Role.php
    - src/Model/Entity/Permission.php
    - src/Model/Entity/UsersRole.php
    - src/Model/Entity/UsersPermission.php
    - src/Model/Entity/RolesPermission.php
  modified: []

key-decisions:
  - "Used explicit through option on all belongsToMany for pivot table control"
  - "Permission name regex /^[a-zA-Z0-9.]+$/ enforces dotted format"
  - "buildRules for unique constraints instead of validation-only for database-level enforcement"
  - "Self-referential associations (ParentRoles/ChildRoles) prepared for future hierarchy"

patterns-established:
  - "Association pattern: belongsToMany with className, through, foreignKey, targetForeignKey, saveStrategy"
  - "Pivot Table pattern: belongsTo with INNER joinType to both related tables"
  - "Entity pattern: $_accessible with id=false, association fields accessible"

# Metrics
duration: 8min
completed: 2026-02-05
---

# Phase 1 Plan 2: ORM Models Summary

**CakePHP ORM Table and Entity classes with belongsToMany associations for Roles, Permissions, and three pivot tables using explicit through tables**

## Performance

- **Duration:** 8 min
- **Started:** 2026-02-05T15:45:00Z
- **Completed:** 2026-02-05T15:53:00Z
- **Tasks:** 3
- **Files created:** 10

## Accomplishments
- RolesTable with belongsToMany Users (CakeDC/Users) and Permissions via explicit through tables
- PermissionsTable with belongsToMany Roles and Users (direct permission assignment)
- Three pivot Table classes enabling ORM containment from junction tables
- Five Entity classes with accessible fields and PHPDoc annotations
- Unique name validation with buildRules for database-level enforcement
- Permission name validated against alphanumeric+dots regex pattern

## Task Commits

Each task was committed atomically:

1. **Task 1: Create primary Table classes (Roles and Permissions)** - `85497e6` (feat)
2. **Task 2: Create pivot Table classes** - `386a153` (feat)
3. **Task 3: Create Entity classes** - `d58405d` (feat)

## Files Created

### Table Classes
- `src/Model/Table/RolesTable.php` - Role model with Users/Permissions belongsToMany, ParentRoles/ChildRoles for future hierarchy
- `src/Model/Table/PermissionsTable.php` - Permission model with Roles/Users belongsToMany
- `src/Model/Table/UsersRolesTable.php` - Pivot table linking Users and Roles
- `src/Model/Table/UsersPermissionsTable.php` - Pivot table for direct user-permission assignment
- `src/Model/Table/RolesPermissionsTable.php` - Pivot table linking Roles and Permissions

### Entity Classes
- `src/Model/Entity/Role.php` - Role entity with accessible fields for all columns and associations
- `src/Model/Entity/Permission.php` - Permission entity with accessible fields
- `src/Model/Entity/UsersRole.php` - Pivot entity for user-role assignments
- `src/Model/Entity/UsersPermission.php` - Pivot entity for direct user permissions
- `src/Model/Entity/RolesPermission.php` - Pivot entity for role-permission assignments

## Decisions Made

1. **Used explicit `through` option on all belongsToMany associations**
   - Rationale: Enables pivot tables with timestamps and metadata; allows ORM containment from pivot tables

2. **Permission name regex `/^[a-zA-Z0-9.]+$/`**
   - Rationale: Enforces dotted format (e.g., posts.edit, rbac.roles.view) while allowing flexibility in nesting depth

3. **buildRules for unique constraints instead of validation-only**
   - Rationale: Database-level enforcement via isUnique rule prevents race conditions; validation alone could miss concurrent inserts

4. **Self-referential ParentRoles/ChildRoles associations added**
   - Rationale: Prepared for future hierarchical roles feature; parent_id column exists in migration, associations ready

5. **saveStrategy: 'append' on all belongsToMany**
   - Rationale: Prevents accidental removal of existing associations when updating; explicit unlink required to remove

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - all files created successfully following CakePHP 5 conventions.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- ORM models ready for seed data (plan 01-03)
- Table classes can be used immediately with `$this->fetchTable('Rbac.Roles')`
- Eager loading enabled: `$roles->find()->contain(['Users', 'Permissions'])`
- Ready for service layer to query permissions (Phase 2)

### Prerequisites for next plan
- Database tables must exist (01-01 migrations must be run first)
- Seed data (01-03) will populate default roles and permissions

---
*Phase: 01-database-foundation*
*Plan: 02*
*Completed: 2026-02-05*
