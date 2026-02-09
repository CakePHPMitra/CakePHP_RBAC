<?php
declare(strict_types=1);

namespace Rbac;

use Cake\Core\BasePlugin;

/**
 * Plugin class for Rbac
 *
 * Minimal plugin bootstrap for RBAC system
 */
class Plugin extends BasePlugin
{
    /**
     * Plugin name
     *
     * @var string
     */
    protected string $name = 'Rbac';

    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;
}
