# CLAUDE.md

## Project Context

Read `.planning/PROJECT.md` for full project scope, requirements, and decisions.

## Project Overview

CakePHPMitra/RBAC — A standalone CakePHP 5 plugin providing database-backed Role-Based Access Control with CakeDC/Users integration. Manages roles, permissions, and user assignments via pivot tables with a full admin UI.

**Stack:** PHP 8.1+, CakePHP 5.x, CakePHP Authorization ^3.0, CakeDC/Users ^16.0

## CakePHP 5 Plugin Best Practices

- Follow CakePHP 5 plugin conventions for file structure, naming, and namespacing
- Use `src/Plugin.php` extending `BasePlugin` for bootstrap, middleware, routes, and services
- All models use CakePHP ORM conventions (Table classes, Entity classes, Behaviors)
- Migrations use Phinx via `cakephp/migrations`
- Templates use CakePHP 5 default frontend layout (standard bake-style templates)
- Plugin namespace: `Rbac\`
- Test namespace: `Rbac\Test\`

## Before Planning & Execution

**Search implemented plugins first.** Before planning or implementing any feature:

1. Search the existing plugin source (`src/`, `config/`, `templates/`, `tests/`) for related code
2. Check CakeDC/Users and CakeDC/Auth patterns in the host app's vendor directory (`/home/atul/Documents/Projects/claudepe-admin/vendor/cakedc/`)
3. Check CakePHP Authorization patterns (`/home/atul/Documents/Projects/claudepe-admin/vendor/cakephp/authorization/`)
4. Think about how the feature integrates with existing plugin code before writing new code

## Documentation Requirements

- `docs/**/*.md` — Detailed documentation for each feature area
- `API/*.rest` — REST Client (VS Code extension) files for external API testing
- PHPDoc blocks on all public methods/classes
- README.md kept up to date with installation and usage

## Testing

- Unit tests required for all new functionality
- Test files in `tests/TestCase/` following CakePHP conventions
- Fixtures in `tests/Fixture/`
- Run tests: `vendor/bin/phpunit`

## Key Files

| File | Purpose |
|------|---------|
| `src/Plugin.php` | Plugin bootstrap, middleware, services |
| `src/Service/RbacService.php` | Core permission checking logic |
| `src/Policy/RbacPolicy.php` | CakePHP Authorization policy |
| `config/rbac.php` | Plugin configuration defaults |
| `config/Migrations/` | Database schema migrations |
| `.planning/PROJECT.md` | Full project scope and decisions |
| `.planning/ROADMAP.md` | Phase breakdown |
