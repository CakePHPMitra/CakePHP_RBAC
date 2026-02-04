# RBAC for CakePHP 5

[![CakePHP 5](https://img.shields.io/badge/CakePHP-5.x-red.svg)](https://cakephp.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[![Latest Version](https://img.shields.io/github/v/tag/CakePHPMitra/CakePHP_RBAC?label=Git%20Latest)](https://github.com/CakePHPMitra/CakePHP_RBAC)
[![Stable Version](https://img.shields.io/github/v/release/CakePHPMitra/CakePHP_RBAC?label=Git%20Stable&sort=semver)](https://github.com/CakePHPMitra/CakePHP_RBAC/releases)

[![GitHub Stars](https://img.shields.io/github/stars/CakePHPMitra/CakePHP_RBAC?style=social)](https://github.com/CakePHPMitra/CakePHP_RBAC/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/CakePHPMitra/CakePHP_RBAC?style=social)](https://github.com/CakePHPMitra/CakePHP_RBAC/network/members)

Database-backed Role-Based Access Control for CakePHP 5 applications with CakeDC/Users integration.

---

## Features

- Database-backed roles and permissions (runtime manageable via admin UI)
- Pivot tables: users_roles, users_permissions for flexible assignment
- Both role-based and direct user permission assignment
- String-based custom permissions AND controller/action auto-discovery
- CakePHP Authorization plugin integration with custom DB resolver
- Configurable multi-role support (single role default, opt-in multi-role)
- Configurable superadmin bypass role (defaults to 'superadmin')
- Permission caching with auto-invalidation (enabled by default)
- Full admin UI for managing roles, permissions, and user assignments
- View helper for permission checks in templates
- Middleware for automatic controller/action permission enforcement
- Overridable from host application

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.1 |
| CakePHP | ^5.0 |
| CakeDC/Users | ^16.0 |
| CakePHP Authorization | ^3.0 |

## Installation

1. Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/CakePHPMitra/CakePHP_RBAC"
        }
    ]
}
```

2. Install via Composer:

```bash
composer require cakephpmitra/rbac:dev-main
```

3. Load the plugin:

```bash
bin/cake plugin load Rbac
```

Or add to `src/Application.php` in the `bootstrap()` method:

```php
$this->addPlugin('Rbac');
```

4. Run migrations:

```bash
bin/cake migrations migrate --plugin Rbac
```

## Quick Start

TODO: Add quick start guide after implementation.

## Documentation

See the [docs](docs/) folder for detailed documentation:

- [Features](docs/features/) - Usage and helper methods
- [Development](docs/development/) - Configuration and other

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Issues

Report bugs and feature requests on the [Issue Tracker](https://github.com/CakePHPMitra/CakePHP_RBAC/issues).

## Author

[Atul Mahankal](https://atulmahankal.github.io/atulmahankal/)

## License

MIT License - see [LICENSE](LICENSE) file.
