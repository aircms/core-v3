<?php

declare(strict_types=1);

namespace Air\Core;

use Exception;

abstract class Worker extends Controller
{
  public function __call(string $name, array $arguments)
  {
    $arguments = $this->getParams();

    $this->didStarted($name, $arguments);

    if (!method_exists($this, $name)) {
      throw new Exception("Method {$name} was not found");
    }
    while (true) {
      $r = call_user_func_array([$this, $name], $arguments);
      if ($r === false) {
        break;
      }
      $this->didTick($name, $arguments);
      sleep(Front::getInstance()->getConfig()['air']['worker']['sleep'] ?? 1);
    }
    $this->didFinished($name, $arguments);
  }

  protected function didStarted(string $method, array $params = [])
  {
  }

  protected function didTick(string $method, array $params = []): void
  {
  }

  protected function didFinished(string $method, array $params = [])
  {
  }
}
