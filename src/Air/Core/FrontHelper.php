<?php

declare(strict_types=1);

namespace Air\Core;

use Air\Crud\Controller\Login;
use Air\Crud\Controller\NotAllowed;
use Air\Crud\Nav\Nav;
use Air\View\View;

final class FrontHelper
{
  public static function getView(Router $router, array $config): View
  {
    $viewFolder = '/View';
    $scriptFolder = '/Scripts';
    $layoutsFolder = '/Layouts';
    $layoutIndex = '/index.phtml';

    $path = implode('/', array_filter([
      $config['air']['loader']['path'],
      'Module',
      ucfirst($router->getModule()),
      ucfirst($router->getContext()),
    ]));

    $view = new View();

    if (file_exists($path . $viewFolder)) {
      $view->setPath($path . $viewFolder);

      $layoutFolder = null;

      if (file_exists($path . $viewFolder . $layoutsFolder . $layoutIndex)) {
        $layoutFolder = $path . $viewFolder . $layoutsFolder . $layoutIndex;
      } elseif (file_exists(dirname($path) . $viewFolder . $layoutsFolder . $layoutIndex)) {
        $layoutFolder = dirname($path) . $viewFolder . $layoutsFolder . $layoutIndex;
      }

      if ($layoutFolder) {
        $view->setLayoutTemplate('index');
      } else {
        $view->setLayoutEnabled(false);
      }

      $scripFile = $router->getController() . '/' . $router->getAction();

      if (file_exists($path . $viewFolder . $scriptFolder . '/' . $scripFile . '.phtml')) {
        $view->setScript($scripFile);
      } else {
        $view->setAutoRender(false);
      }
    } else {
      $view->setLayoutEnabled(false);
      $view->setAutoRender(false);
    }

    return $view;
  }

  public static function getControllerClassName(Router $router, array $config): ?string
  {
    $module = $router->getModule();
    $context = $router->getContext();
    $controller = $router->getController();

    if ($module === 'admin') {
      if ($nav = Nav::getSettingsItemWithAlias($controller)) {
        return $nav['controller'];
      }

      if (($config['air']['admin']['auth']['route'] ?? false) === $controller) {
        return Login::class;
      }

      if (($config['air']['admin']['notAllowed'] ?? false) === $controller) {
        return NotAllowed::class;
      }
    }

    $controllerClassname = implode("\\", array_filter([
      $config['air']['loader']['namespace'],
      'Module',
      ucfirst($module),
      ucfirst($context),
      'Controller',
      ucfirst($controller)
    ]));

    if (class_exists($controllerClassname) && is_subclass_of($controllerClassname, Controller::class)) {
      return $controllerClassname;
    }

    return null;
  }

  /**
   * @param Router $router
   * @param array $config
   * @return Plugin[]
   */
  public static function getPlugins(Router $router, array $config): array
  {
    $namespace = $config['air']['loader']['namespace'];
    $path = $config['air']['loader']['path'];

    $pluginPaths = [
      $namespace . '\\Module\\Plugin' => $path . '/Module/Plugin',
    ];

    if ($module = $router->getModule()) {
      $module = ucfirst($module);

      $pluginPaths[$namespace . '\\Module\\' . $module . '\\Plugin'] = $path . '/Module/' . $module . '/Plugin';

      if ($context = $router->getContext()) {
        $context = ucfirst($context);
        $pluginPaths[$namespace . '\\Module\\' . $module . '\\' . $context . '\\Plugin'] = $path . '/Module/' . $module . '/' . $context . '/Plugin';
      }
    }

    $plugins = [];

    foreach ($pluginPaths as $namespace => $pluginPath) {
      if (file_exists($pluginPath)) {
        foreach (glob($pluginPath . '/*.php') as $file) {
          $pluginClassname = $namespace . '\\' . basename($file, '.php');

          if (class_exists($pluginClassname) && is_subclass_of($pluginClassname, Plugin::class)) {
            $plugins[] = new $pluginClassname();
          }
        }
      }
    }

    return $plugins;
  }

  public static function getErrorRouter(Request $request, Router $router, array $config): ?Router
  {
    $context = $router->getContext();
    $errorControllerClassname = implode("\\", array_filter([
      $config['air']['loader']['namespace'],
      'Module',
      ucfirst($router->getModule()),
      ucfirst($router->getContext()),
      'Controller',
      'Error'
    ]));

    if (!class_exists($errorControllerClassname) || !is_subclass_of($errorControllerClassname, ErrorController::class)) {
      $context = '';
      $errorControllerClassname = implode("\\", array_filter([
        $config['air']['loader']['namespace'],
        'Module',
        ucfirst($router->getModule()),
        'Controller',
        'Error'
      ]));

      if (!class_exists($errorControllerClassname) || !is_subclass_of($errorControllerClassname, ErrorController::class)) {
        return null;
      }
    }

    $errorRouter = new Router();

    $errorRouter->setRequest($request);
    $errorRouter->setContext($context);
    $errorRouter->setModule($router->getModule());
    $errorRouter->setController('error');
    $errorRouter->setAction('index');
    $errorRouter->setRoutes($config['router'] ?? []);
    $errorRouter->setConfig($router->getConfig());
    $errorRouter->setIsError(true);

    return $errorRouter;
  }
}