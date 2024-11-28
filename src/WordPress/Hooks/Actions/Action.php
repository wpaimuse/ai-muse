<?php

namespace AIMuse\WordPress\Hooks\Actions;

use AIMuse\WordPress\Hooks\Hook;

class Action extends Hook
{
  protected string $name;
  protected int $priority = 10;

  public function register()
  {
    add_action($this->name, [$this, 'handle'], $this->priority);
  }
}
