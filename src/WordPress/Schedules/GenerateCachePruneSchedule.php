<?php

namespace AIMuse\WordPress\Schedules;

use AIMuse\Helpers\ResponseHelper;
use AIMuseVendor\Illuminate\Support\Facades\File;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class GenerateCachePruneSchedule extends Schedule
{
  protected $name = 'aimuse_generate_cache_prune';
  protected $interval = 'hourly';

  public function __construct()
  {
    $this->timestamp = strtotime('+1 hour');
    parent::__construct();
  }

  public function run()
  {
    if (!File::exists(ResponseHelper::$cacheDir)) return;

    try {
      $files = File::allFiles(ResponseHelper::$cacheDir);
      $lifetime = 60 * 10; // 10 minutes

      foreach ($files as $file) {
        if (time() - $file->getCTime() > $lifetime) {
          File::delete($file->getPathname());
        }
      }

      Log::info('Pruned cache files successfully');
    } catch (\Throwable $th) {
      Log::error(
        'An error occured when pruning cache files',
        [
          'error' => $th,
          'trace' => $th->getTrace(),
          'job' => $this->name,
        ]
      );
    }
  }
}
