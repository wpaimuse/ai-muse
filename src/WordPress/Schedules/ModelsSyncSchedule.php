<?php

namespace AIMuse\WordPress\Schedules;

use AIMuse\Models\AIModel;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ModelsSyncSchedule extends Schedule
{
  protected $name = 'aimuse_models_sync';
  protected $interval = 'weekly';
  protected $immediate = true;

  public function __construct()
  {
    $this->timestamp = strtotime('+1 day');
    parent::__construct();
  }

  public function run()
  {
    try {
      AIModel::sync();
      Log::info('Models successfully synchronized automatically');
    } catch (\Throwable $th) {
      Log::error('Model synchronization failed', [
        'error' => $th,
        'trace' => $th->getTrace(),
        'job' => $this->name,
      ]);
    }
  }
}
