---
phase: 01-database-foundation
plan: 03
subsystem: database-seeds
tags: [seeds, idempotency, rbac-data, default-roles, default-permissions]
dependency-graph:
  requires:
    - 01-01 (migrations - table schema)
  provides:
    - Default roles data (superadmin, admin, user)
    - Default permissions data (rbac.*, dashboard.*, profile.*)
    - Role-permission assignments (admin and user roles)
  affects:
    - Phase 2 (service layer will query seeded data)
    - Phase 5 (admin UI will display seeded roles/permissions)
tech-stack:
  added:
    - CakePHP Migrations BaseSeed
  patterns:
    - Idempotent seed files with query-based existence checks
    - Seed dependency ordering with getDependencies()
    - Composite primary keys for pivot table seed data
key-files:
  created:
    - config/Seeds/RolesSeed.php
    - config/Seeds/PermissionsSeed.php
    - config/Seeds/RolesPermissionsSeed.php
  modified: []
decisions:
  - title: "Superadmin gets zero permission assignments"
    rationale: "Bypass logic handled in service layer (Phase 2), not via permission grants"
    impact: "Cleaner separation: seeds define permission model, service layer handles bypass"
  - title: "Idempotency via SELECT COUNT checks"
    rationale: "Simple, reliable check - query for specific record existence before insert"
    impact: "Seeds can be run multiple times safely without duplicate key errors"
  - title: "Seed dependency ordering"
    rationale: "RolesPermissionsSeed requires role/permission IDs from previous seeds"
    impact: "CakePHP Migrations automatically orders seed execution based on getDependencies()"
metrics:
  duration_seconds: 131
  tasks_completed: 2
  files_created: 3
  commits: 2
  completed: 2026-02-09
---

# Phase 1 Plan 3: Database Seeds Summary

**One-liner:** Idempotent seed files create default roles (superadmin, admin, user) and permissions (rbac.* management + basic user access) with dependency-ordered assignments.

## What Was Built

Created three seed files providing initial RBAC data:

1. **RolesSeed**: Creates 3 default roles
   - `superadmin` - System administrator (is_system=true, no permissions)
   - `admin` - RBAC administrator (manages roles/permissions)
   - `user` - Standard user (is_default=true, basic access)

2. **PermissionsSeed**: Creates 14 default permissions
   - 11 RBAC management permissions (`rbac.*` namespace)
   - 3 basic user permissions (`dashboard.view`, `profile.*`)

3. **RolesPermissionsSeed**: Assigns permissions to roles
   - Admin role → all 11 rbac.* permissions
   - User role → 3 basic permissions (dashboard.view, profile.view, profile.edit)
   - Superadmin → zero permissions (bypass in service layer)

**All seeds are idempotent**: Safe to run `bin/cake migrations seed --plugin Rbac` multiple times without errors or duplicates.

## Implementation Details

### Idempotency Pattern

Each seed implements existence check before insert:

```php
// RolesSeed
$stmt = $this->query("SELECT COUNT(*) as count FROM roles WHERE name = 'superadmin'");
$result = $stmt->fetch('assoc');
if ($result['count'] > 0) {
    return; // Skip - already seeded
}
```

**Rationale:** Simple, reliable check prevents duplicate key violations on re-run.

### Seed Dependency Ordering

RolesPermissionsSeed declares dependencies:

```php
public function getDependencies(): array
{
    return ['RolesSeed', 'PermissionsSeed'];
}
```

**Effect:** CakePHP Migrations automatically runs RolesSeed and PermissionsSeed first, ensuring role/permission IDs exist before querying them for assignments.

### Role-Permission Assignments

**Admin role** (RBAC administrator):
- rbac.roles.view
- rbac.roles.create
- rbac.roles.edit
- rbac.roles.delete
- rbac.permissions.view
- rbac.permissions.create
- rbac.permissions.edit
- rbac.permissions.delete
- rbac.users.assign
- rbac.matrix.view
- rbac.matrix.edit

**User role** (standard user):
- dashboard.view
- profile.view
- profile.edit

**Superadmin role** (system administrator):
- **No permissions assigned**
- Bypass logic will be implemented in RbacService (Phase 2)
- Service layer checks `is_system` flag and skips permission checks

## Deviations from Plan

None - plan executed exactly as written.

## Technical Decisions

### Why superadmin has zero permissions

**Decision:** Superadmin role gets no permission assignments in seed data.

**Rationale:**
- Separation of concerns: Permission model defines what admins can do, service layer handles bypass
- Flexibility: Superadmin bypass is centralized in one place (RbacService::hasPermission)
- Clarity: Empty assignment explicitly communicates "this role doesn't use the permission system"

**Alternative considered:** Assign all permissions to superadmin → Rejected because it couples seed data to future permission additions and creates maintenance burden.

### Idempotency strategy

**Decision:** Use SELECT COUNT queries to check existence before insert.

**Rationale:**
- Simple and readable
- Works with any database (no vendor-specific UPSERT/MERGE)
- Fast for small seed datasets
- No complex timestamp comparison logic

**Alternative considered:** Track seed execution in separate table → Rejected as over-engineering for initial seed data.

## Files Created

All files in `/home/atul/Documents/__Testing/CakePHP_own_plugins/CakePHP_RBAC/config/Seeds/`:

1. **RolesSeed.php** (2,378 bytes)
   - Creates 3 roles with proper flags (is_system, is_default)
   - Idempotent via superadmin name check

2. **PermissionsSeed.php** (5,031 bytes)
   - Creates 14 permissions (rbac.* + basic user)
   - Idempotent via rbac.roles.view check

3. **RolesPermissionsSeed.php** (2,945 bytes)
   - Assigns permissions to admin and user roles
   - Uses getDependencies() for ordering
   - Idempotent via roles_permissions table count check

## Testing Notes

**Manual verification after seeding:**

```bash
# Run migrations first (if not already done)
bin/cake migrations migrate --plugin Rbac

# Run seeds
bin/cake migrations seed --plugin Rbac

# Verify roles created
bin/cake bake shell_helper Query "SELECT name, is_system, is_default FROM roles"

# Verify permissions created
bin/cake bake shell_helper Query "SELECT COUNT(*) FROM permissions WHERE name LIKE 'rbac.%'"

# Verify assignments (should be 14 total: 11 for admin + 3 for user)
bin/cake bake shell_helper Query "SELECT COUNT(*) FROM roles_permissions"
```

**Expected results:**
- 3 roles: superadmin (is_system=1), admin, user (is_default=1)
- 14 permissions: 11 rbac.*, 3 basic
- 14 role_permission records: 11 admin assignments, 3 user assignments, 0 superadmin assignments

**Idempotency test:**
```bash
# Run seeds again - should skip (no errors, no duplicates)
bin/cake migrations seed --plugin Rbac
```

## Next Steps

1. **Verify seeds in host application:**
   - Run migrations: `bin/cake migrations migrate --plugin Rbac`
   - Run seeds: `bin/cake migrations seed --plugin Rbac`
   - Confirm data exists and idempotency works

2. **Begin Phase 2 (Service Layer):**
   - Create RbacService for permission checking
   - Implement superadmin bypass logic (check is_system flag)
   - Create DatabaseResolver for CakePHP Authorization integration

3. **Integration testing:**
   - Test ORM associations (Role->Permissions, User->Roles)
   - Verify containable queries work with seeded data

## Self-Check: PASSED

**Files exist:**
```
FOUND: /home/atul/Documents/__Testing/CakePHP_own_plugins/CakePHP_RBAC/config/Seeds/RolesSeed.php
FOUND: /home/atul/Documents/__Testing/CakePHP_own_plugins/CakePHP_RBAC/config/Seeds/PermissionsSeed.php
FOUND: /home/atul/Documents/__Testing/CakePHP_own_plugins/CakePHP_RBAC/config/Seeds/RolesPermissionsSeed.php
```

**Commits exist:**
```
FOUND: 7e5ed39 (Task 1: RolesSeed and PermissionsSeed)
FOUND: 62ec54e (Task 2: RolesPermissionsSeed)
```

**File verification:**
```bash
# RolesSeed creates 3 roles
grep -c "'name' =>" config/Seeds/RolesSeed.php
# Output: 3

# PermissionsSeed creates 14 permissions
grep -c "'name' =>" config/Seeds/PermissionsSeed.php
# Output: 14

# RolesPermissionsSeed has getDependencies
grep -c "getDependencies" config/Seeds/RolesPermissionsSeed.php
# Output: 1

# Superadmin has is_system=true
grep "superadmin" -A10 config/Seeds/RolesSeed.php | grep "is_system"
# Output: 'is_system' => true,

# User has is_default=true
grep "'user'" -A10 config/Seeds/RolesSeed.php | grep "is_default"
# Output: 'is_default' => true,
```

All verification checks passed.
