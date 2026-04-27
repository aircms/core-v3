<?php

require_once 'vendor/autoload.php';
require_once __DIR__ . '/UpdateManager.php';

$config = \Air\Config::defaults(
  // ..
);

\Air\Core\Front::getInstance($config);

UpdateManager::reflectSchema();

$updateManager = UpdateManager::fetchObject();

foreach (glob($config['air']['updates'] . '/*.php') as $file) {
  $lastUpdate = DateTime::createFromFormat('Y-m-d-H-i-s', basename($file, '.php'))->getTimestamp();

  if ($lastUpdate > $updateManager->lastUpdate) {
    require_once $file;
  }
}

if (isset($lastUpdate)) {
  $updateManager->lastUpdate = $lastUpdate;
  $updateManager->save();
}