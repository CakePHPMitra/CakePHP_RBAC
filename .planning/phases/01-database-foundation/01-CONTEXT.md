# Phase 1: Database Foundation - Context

**Gathered:** 2026-02-05
**Status:** Ready for planning

<domain>
## Phase Boundary

Establish the database schema with all tables (roles, permissions, users_roles, users_permissions, roles_permissions), composite indexes for performance, and seed data for default roles. This phase delivers the data layer that all subsequent phases query.

</domain>

<decisions>
## Implementation Decisions

### Seed Data Composition
- 3 default roles: superadmin, admin, user
- Admin role gets full RBAC management permissions (rbac.* namespace)
- User role gets dashboard and profile access only — host app assigns domain permissions
- Superadmin has NO explicit permission assignments — bypass is handled in service layer (Phase 2)
- Superadmin identified by name convention ('superadmin'), configurable via config
- Seed is idempotent: skip existing records, never update
- Only seed rbac.* permissions — no sample host-app permissions

### Permission Naming Convention
- Dotted format: `resource.action` (e.g., `posts.edit`, `rbac.roles.view`)
- Unlimited nesting depth allowed (e.g., `app.settings.email.smtp.edit`)
- Case-insensitive matching — store as-is, match ignoring case
- Validation: alphanumeric characters and dots only (`[a-zA-Z0-9.]+`)
- Grouping derived from first segment of permission name (no separate group column)

### Schema Flexibility
- Soft deletes: configurable — add `deleted_at` column, behavior controlled via config
- `is_system` column on roles — protects system roles (superadmin) from deletion
- `is_default` column on roles — role with flag assigned to new users automatically
- `is_active` column on both roles and permissions — can disable without deleting
- `sort_order` column on roles — controls display order in admin UI
- `description` column on permissions — optional (nullable)
- `parent_id` column on roles — nullable, for future hierarchical roles
- Pivot tables include `created` and `modified` timestamps

### Constraint Strictness
- Role and permission names: globally unique (even across soft-deleted records)
- **User deletion**: CASCADE to `users_roles` and `users_permissions`
- **Role deletion**: RESTRICT if any users assigned, CASCADE to `roles_permissions`
- **Permission deletion**: RESTRICT if any users assigned directly, CASCADE to `roles_permissions`
- Composite unique constraints on all pivot tables (prevent duplicate assignments)

### Claude's Discretion
- Exact index composition and naming
- Migration class naming and ordering
- Seed data permission list for admin and user roles
- Column ordering within tables

</decisions>

<specifics>
## Specific Ideas

- User mentioned "currently only dashboard & profile access" for user role — implies permissions like `dashboard.view`, `profile.view`, `profile.edit`
- Seed data should follow CakePHP conventions for table/column naming

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-database-foundation*
*Context gathered: 2026-02-05*
