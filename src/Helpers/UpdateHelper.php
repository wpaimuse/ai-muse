<?php

namespace AIMuse\Helpers;

use AIMuseVendor\Illuminate\Support\Facades\Http;

class UpdateHelper
{
  public static function info($action, $args)
  {
    $args['slug'] = "akismet";
    $response = Http::get("http://api.wordpress.org/plugins/info/1.2/", [
      'action'  => $action,
      'request' => $args,
    ]);

    dd($response->json());

    if (!$response->failed()) {
      return $response->json();
    }
  }
}
