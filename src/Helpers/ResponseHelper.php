<?php

namespace AIMuse\Helpers;

use AIMuse\Models\Settings;
use WP_REST_Response;
use AIMuseVendor\Illuminate\Support\Facades\File;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ResponseHelper
{
  public static string $channel;
  public static string $mode = 'sse';
  public static bool $isStreamAvailable = false;
  public static string $data = '';
  public static string $cacheDir = WP_CONTENT_DIR . '/uploads/aimuse/generate';
  public static string $cacheFile = '';
  public static int $timeout = 60;

  public static function prepare(array $options)
  {
    static::$isStreamAvailable = $options['forceSSE'] || Settings::get('isStreamAvailable', null);
    static::$channel = $options['channel'];
    static::$cacheFile = static::$cacheDir . '/' . static::$channel;

    static::prepareSSE();

    if (static::$isStreamAvailable) {
      static::setMode('sse');
    } else {
      static::setMode('response');
    }
  }

  public static function setTimeout(int $timeout)
  {
    static::$timeout = $timeout;
  }

  public static function prepareSSE()
  {
    ob_start();
    ignore_user_abort(true);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    // Disable output buffering
    header('X-Accel-Buffering: no');

    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    ini_set('implicit_flush', true);

    ob_implicit_flush(true);

    while (ob_get_level() > 0) {
      ob_end_flush();
    }
  }

  public static function sendSSE($event, $data)
  {
    echo "event: " . esc_html($event) . "\n";
    echo 'data: ' . wp_json_encode(['message' => $data]) . "\n\n";
    if (ob_get_level() > 0) {
      @ob_flush();
    }
    flush();
  }

  public static function release()
  {
    @ob_end_flush();
    header('Content-Type: application/json');
  }

  public static function setMode(string $mode)
  {
    self::$mode = $mode;
  }

  public static function send($event, $data)
  {
    $action = 'continue';


    if (self::$mode == 'sse') {
      self::sendSSE($event, $data);
      if (connection_aborted()) {
        $action = 'stop';
      }
    }

    if (self::$mode == 'response') {
      static::$data .= "event: " . esc_html($event) . "\n";
      static::$data .= 'data: ' . wp_json_encode(['message' => $data]) . "\n\n";
    }

    if ($event == 'done') {
      self::dump();
      self::putCache();
      self::exit();
    }

    return $action;
  }

  public static function setChannel(string $channel)
  {
    self::$channel = $channel;
  }

  public static function putCache()
  {
    if (!static::$cacheFile) return;
    if (!File::exists(static::$cacheFile)) return;

    File::put(static::$cacheFile, static::$data);
  }

  public static function initCache()
  {
    if (File::exists(static::$cacheFile)) return;

    if (!File::exists(static::$cacheDir)) {
      File::makeDirectory(static::$cacheDir, 0755, true);
    }

    File::put(static::$cacheFile, '');
  }

  public static function readCache()
  {
    return File::get(static::$cacheFile);
  }

  public static function clearCache()
  {
    if (File::exists(static::$cacheFile)) {
      File::delete(static::$cacheFile);
    }
  }

  public static function serveCache()
  {
    Log::debug('Serving cache', [
      'channel' => static::$channel,
      'file' => static::$cacheFile,
    ]);

    for ($i = 0; $i < self::$timeout; $i++) {
      self::$data = self::readCache();
      if (self::$data) {
        self::dump();
        self::clearCache();
        self::exit();
      }
      sleep(1);
    }

    http_response_code(204);
    self::exit();
  }

  public static function dump()
  {
    if (self::$mode == 'response') {
      echo static::$data;
    }
  }

  public static function exit()
  {
    ob_end_flush();
    exit();
  }

  public static function error($data, int $status)
  {
    static::release();
    return new WP_REST_Response($data, $status);
  }
}
