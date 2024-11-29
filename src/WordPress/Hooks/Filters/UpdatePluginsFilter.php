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

    if (!$data) return $transient;
    if (version_compare($data->requires, get_bloginfo('version'), '>')) return $transient;
    if (version_compare($data->requires_php, PHP_VERSION, '>')) return $transient;

    $result = (object) [
      'slug'        => aimuse()->name(),
      'plugin'      => aimuse()->file(),
      'new_version' => $data->version,
      'tested'         => $data->tested,
      'package'     => $data->download_url,
    ];

    $transient->response[$result->plugin] = $result;

    return $transient;
  }
}
