<?php

declare(strict_types=1);

namespace Air\Core\Exception;

use Exception;

class ControllerClassWasNotFound extends Exception
{
  public function __construct(?string $controllerClassName = null)
  {
    $message = match (!$controllerClassName) {
      true => "Undefined controller classname",
      false => $controllerClassName
    };

    parent::__construct($message, 404);
  }
}