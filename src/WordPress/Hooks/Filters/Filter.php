<?php

namespace AIMuse\WordPress\Hooks\Filters;

use AIMuse\WordPress\Hooks\Hook;

class Filter extends Hook
{
  protected string $name;
  protected int $priority = 10;
  protected int $acceptedArgs = 1;

  public function register()
  {
    add_filter($this->name, [$this, 'handle'], $this->priority, $this->acceptedArgs);
  }
}
