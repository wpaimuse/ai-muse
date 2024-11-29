<?php

namespace AIMuse\WordPress\Hooks\Actions;

use AIMuse\Helpers\UpdateHelper;
use WP_Upgrader;

/**
 * Fires when the upgrader process is complete.

 *
 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
 */
class UpgraderProcessCompleteAction extends Action
{
  public function __construct()
  {
    $this->name = "upgrader_process_complete";
  }

  public function handle($upgrader, array $hookExtra)
  {
    if ($hookExtra['action'] !== 'update') return;
    if ($hookExtra['type'] !== 'plugin') return;

    UpdateHelper::purge();
  }
}
