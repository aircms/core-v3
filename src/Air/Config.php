<?php

declare(strict_types=1);

namespace Air;

use Air\Crud\Nav\Nav;
use Air\Type\RichContent;

class Config
{
  private static string $project;

  public static function getEnv(string $var, mixed $default = null): array|false|string|null
  {
    return getenv(self::$project . "_" . $var) ?? $default;
  }

  public static function admin(
    string  $title = 'AirCms',
    ?array  $settings = null,
    ?array  $nav = [],
    ?bool   $reportErrors = false,
    ?array  $richContent = null,
    ?array  $require = [],
    ?string $login = null,
    ?string $password = null,
    ?string $tiny = null,
  ): array
  {
    $title = $title ?? "AirCMS";
    $settings = $settings ?? Nav::getAllSettings();
    $richContent = $richContent ?? RichContent::getAllTypes();
    $require = [
      ...$require,
      'vendor/aircms/core-v2/src/Air/View/Shorts/shorts.php',
      'vendor/aircms/core-v2/src/Air/Crud/View/Ui/ui.php',
    ];

    $login = $login ?? self::getEnv('ADMIN_AUTH_ROOT_LOGIN');
    $password = $password ?? self::getEnv('ADMIN_AUTH_ROOT_PASSWORD');
    $tiny = $tiny ?? self::getEnv('ADMIN_TINY_KEY');

    return [
      'module' => 'admin',
      'air' => [
        'exception' => $reportErrors,
        'phpIni' => [
          'display_errors' => $reportErrors ? '1' : '0',
        ],
        'startup' => [
          'error_reporting' => $reportErrors ? E_ALL : 0,
        ],
        'admin' => [
          'title' => $title,
          'logo' => '/assets/air/logo.png',
          'favicon' => '/assets/air/logo.png',
          'notAllowed' => '_notAllowed',
          'settings' => $settings,
          'rich-content' => $richContent,
          'auth' => [
            'route' => '_auth',
            'source' => 'database',
            'root' => [
              'login' => $login,
              'password' => $password,
            ],
          ],
          'tiny' => $tiny,
          'menu' => $nav,
        ],
        'asset' => [
          'underscore' => false,
          'prefix' => '/assets/air',
        ],
        'require' => $require,
      ],
    ];
  }

  public static function api(
    bool  $strictRoutes = true,
    bool  $strictInject = true,
    bool  $cacheEnabled = false,
    array $routes = [],
    array $require = [],
  ): array
  {
    return [
      'strict' => $strictRoutes,
      'module' => 'api',
      'routes' => $routes,
      'air' => [
        'strictInject' => $strictInject,
        'cache' => [
          'enabled' => $cacheEnabled,
        ],
        'require' => $require,
      ],
    ];
  }

  public static function ui(
    bool  $strictInject = true,
    bool  $strictRoutes = true,
    bool  $cacheEnabled = false,
    array $routes = [],
    array $require = [],
  ): array
  {
    return [
      'strict' => $strictRoutes,
      'module' => 'ui',
      'routes' => $routes,
      'air' => [
        'strictInject' => $strictInject,
        'asset' => [
          'underscore' => false,
          'prefix' => '/assets/ui',
        ],
        'cache' => [
          'enabled' => $cacheEnabled,
        ],
        'require' => $require,
      ],
    ];
  }

  public static function cli(
    bool  $strictInject = false,
    bool  $strictRoutes = false,
    array $routes = [],
    array $require = [],
  ): array
  {
    return [
      'strict' => $strictRoutes,
      'module' => 'cli',
      'routes' => $routes,
      'air' => [
        'strictInject' => $strictInject,
        'require' => $require,
      ],
    ];
  }

  public static function defaults(
    ?array $admin = null,
    ?array $cli = null,
    ?array $api = null,
    ?array $ui = null,

    string $project = 'air',
    array  $extensions = [],
    string $timezone = "Europe/Kyiv",
    bool   $reportErrors = true,
    bool   $logs = true,
    ?array $db = [],
  ): array
  {
    self::$project = $project;
    $appEntryPoint = realpath(dirname($_SERVER['SCRIPT_FILENAME'], 2));

    return [
      'air' => [
        'modules' => '\\App\\Module',
        'exception' => $reportErrors,
        'phpIni' => [
          'display_errors' => $reportErrors ? '1' : '0',
        ],
        'startup' => [
          'error_reporting' => $reportErrors ? E_ALL : 0,
          'date_default_timezone_set' => $timezone,
        ],
        'loader' => [
          'namespace' => 'App',
          'path' => $appEntryPoint . '/app',
          'groupedController' => true,
        ],
        'db' => [
          ...[
            'driver' => 'mongodb',
            'user' => self::getEnv("DB_USER"),
            'pass' => self::getEnv("DB_PASS"),
            'servers' => [[
              'host' => self::getEnv("DB_HOST", "localhost"),
              'port' => self::getEnv("DB_PORT", 27017),
            ]],
            'db' => self::getEnv('DB_DB')
          ],
          ...$db
        ],
        'storage' => [
          'url' => self::getEnv('FS_URL'),
          'key' => self::getEnv('FS_KEY'),
        ],
        'logs' => [
          'enabled' => $logs,
          'exception' => true,
        ],
        'fontsUi' => 'fontsUi',
      ],
      'router' => array_filter([
        'admin.*' => $admin,
        'cli' => $cli,
        'api.*' => $api,
        '*' => $ui
      ]),
      ...$extensions
    ];
  }
}
