<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for Rbac Plugin
 *
 * This file is executed before each test suite run. It sets up the
 * CakePHP test environment for standalone plugin testing.
 */

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Migrations\TestSuite\Migrator;

// Load Composer autoloader
require dirname(__DIR__) . '/vendor/autoload.php';

// Define path constants for plugin
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__) . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('TESTS', ROOT . 'tests' . DS);
define('APP', ROOT . 'tests' . DS . 'test_app' . DS);
define('APP_DIR', 'test_app');
define('WEBROOT_DIR', 'webroot');
define('WWW_ROOT', APP . 'webroot' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('CONFIG', APP . 'config' . DS);
define('CACHE', TMP . 'cache' . DS);
define('LOGS', TMP . 'logs' . DS);

// Configure application settings
Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'Rbac\Test',
    'encoding' => 'UTF-8',
    'defaultLocale' => 'en_US',
    'defaultTimezone' => 'UTC',
    'paths' => [
        'plugins' => [ROOT . 'plugins' . DS],
        'templates' => [ROOT . 'templates' . DS],
        'locales' => [ROOT . 'resources' . DS . 'locales' . DS],
    ],
]);

// Configure cache (use array engine for tests - no persistence needed)
Cache::setConfig([
    '_cake_core_' => [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
        'serialize' => true,
    ],
    '_cake_model_' => [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
        'serialize' => true,
    ],
    'default' => [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
        'serialize' => true,
    ],
]);

// Configure test database connection
// Using SQLite in-memory for fast, isolated tests
ConnectionManager::setConfig('test', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => ':memory:',
    'encoding' => 'utf8',
    'timezone' => 'UTC',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,
    'log' => false,
]);

// Alias 'default' to 'test' for plugin code that expects 'default' connection
ConnectionManager::alias('test', 'default');

// Load plugin
Plugin::getCollection()->add(new \Rbac\Plugin());

// Run migrations to create schema in test database
$migrator = new Migrator();
$migrator->run([
    'connection' => 'test',
    'source' => ROOT . 'config' . DS . 'Migrations',
]);
