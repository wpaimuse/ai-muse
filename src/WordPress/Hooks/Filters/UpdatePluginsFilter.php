<?php

namespace AIMuse\WordPress\Hooks\Filters;

use AIMuse\Helpers\UpdateHelper;

/**
 * Filters the value of an existing site transient.

 *
 * @link https://developer.wordpress.org/reference/hooks/site_transient_transient/
 */
class UpdatePluginsFilter extends Filter
{
  public function __construct()
  {
    $this->name = "site_transient_update_plugins";
    $this->acceptedArgs = 2;
  }

  /**
   * Filters the value of an existing site transient.
   *
   * @param object $transient
   * @return object
   */
  public function handle($transient)
  {
    if (empty($transient->checked)) return $transient;

    $data = UpdateHelper::info("plugin_information", [
      'slug'   => aimuse()->name(),
      'fields' => [
        'sections' => false,
      ],
    ]);

    dd($data);

    dd($transient);
    $plugin_data = get_plugin_data("https://raw.githubusercontent.com/wpaimuse/ai-muse/refs/heads/main/aimuse.php");
    dd($plugin_data);
  }
}
