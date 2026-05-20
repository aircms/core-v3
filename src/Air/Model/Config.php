<?php

declare(strict_types=1);

namespace Air\Model;

use Air\Core\Front;

class Config
{
  public static function getConfig(): array
  {
    return Front::getInstance()->getConfig()['air']['db'];
  }
}