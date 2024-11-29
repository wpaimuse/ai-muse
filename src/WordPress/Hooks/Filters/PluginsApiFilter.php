<?php

namespace AIMuse\WordPress\Hooks\Filters;

use AIMuse\Helpers\UpdateHelper;

/**
 * Filters the response for the current WordPress.org Plugin Installation API request.

 *
 * @link https://developer.wordpress.org/reference/hooks/plugins_api/
 */
class PluginsApiFilter extends Filter
{
  public function __construct()
  {
    $this->name = "plugins_api";
    $this->acceptedArgs = 3;
  }

  /**
   * Filters the response for the current WordPress.org Plugin Installation API request.
   *
   * @param false|object|array $result
   * @param string $action
   * @param object $args
   * @return false|object|array
   */
  public function handle($result, $action, $args)
  {
    if ($args->slug !== aimuse()->name()) return $result;

    $response = UpdateHelper::info($action, $args);

    if (!$response) return $result;

    return $response;
  }
}
