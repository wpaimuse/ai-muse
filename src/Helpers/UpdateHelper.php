<?php

namespace AIMuse\Helpers;

use AIMuseVendor\Illuminate\Support\Facades\Http;

class UpdateHelper
{
  private static $info;

  /**
   * Get plugin information.
   * 
   * @param string $action 
   * @param array $args 
   * @return object|bool
   */
  public static function info($action, $args)
  {
    $response = self::request($action, $args);

    if ($response) {
      self::$info = $response;
      set_transient('aimuse_info', $response, 60 * 60 * 24);
    }

    return $response;
  }

  /**
   * Request plugin information.
   * 
   * @param string $action 
   * @param array $args 
   * @return object|bool
   */
  public static function request($action, $args)
  {
    if (self::$info) {
      return self::$info;
    }

    $response = get_transient('aimuse_info');

    if ($response) {
      return $response;
    }

    $response = Http::get("http://api.wordpress.org/plugins/info/1.2/", [
      'action'  => $action,
      'request' => $args,
    ]);

    if (!$response->failed()) {
      return (object) $response->json();
    }

    $response = Http::get("https://github.com/wpaimuse/ai-muse/raw/refs/heads/main/info.json");

    if (!$response->failed()) {
      return (object) $response->json();
    }

    return false;
  }

  public static function purge()
  {
    delete_transient('aimuse_info');
    self::$info = null;
  }
}
