<?php

namespace Air\Core\Exception;

use Exception;

class Stop extends Exception
{
  public function __construct(int $code = 200)
  {
    parent::__construct("Stop execution", $code);
  }
}