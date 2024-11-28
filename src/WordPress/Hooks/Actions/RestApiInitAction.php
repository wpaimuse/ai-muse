<?php

namespace AIMuse\WordPress\Hooks\Actions;

use AIMuse\Router;

class RestApiInitAction extends Action
{
  public function __construct()
  {
    $this->name = 'rest_api_init';
  }

  public function handle()
  {
    Router::registerRoutes();
  }
}
