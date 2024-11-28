<?php

namespace AIMuse\WordPress\Hooks\Actions;

use AIMuseVendor\Illuminate\Support\Facades\Log;

class ActivatePluginAction extends Action
{
  public function __construct()
  {
    $this->name = 'activate_' . aimuse()->file();
  }

  public function handle()
  {
    Log::info('Activating plugin');
  }
}
