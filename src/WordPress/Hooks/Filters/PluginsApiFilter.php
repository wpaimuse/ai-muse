<?php

namespace AIMuse\WordPress\Hooks\Filters;

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

  public static $processing = false;

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
    if (self::$processing) return $result;

    self::$processing = true;
    $result = plugins_api($action, $args);

    dd($result);

    return $result;
  }
}
