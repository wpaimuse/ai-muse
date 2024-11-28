<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;
use AIMuseVendor\Illuminate\Database\Eloquent\Collection;

class Settings extends Model
{
  protected $table = 'aimuse_settings';
  protected $guarded = [];

  public $timestamps = false;

  protected $casts = [];

  /**
   * Undocumented variable
   *
   * @var \AIMuseVendor\Illuminate\Database\Eloquent\Collection|null
   */
  private static $cache = null;

  public static $backupKeys = [
    'openAiApiKey',
    'googleAiApiKey',
    'openRouterApiKey',
    'acceptedTerms',
    'isTextBlockActive',
    'isImageBlockActive',
    'textModel',
    'imageModel',
  ];

  public static $labels = [
    'textModel' => 'Text Model',
    'imageModel' => 'Image Model',
  ];

  private static function cache()
  {
    if (self::$cache === null) {
      self::$cache = static::all();
    }

    return self::$cache;
  }

  public static function clearCache()
  {
    self::$cache = null;
  }

  public static function get($name, $default = [])
  {
    try {
      $setting = self::cache()->where('name', $name)->first();
      return $setting ? unserialize($setting->data) : $default;
    } catch (\Exception $e) {
      return $default;
    }
  }

  public static function set($name, $data)
  {
    try {
      $setting = self::cache()->where('name', $name)->first() ?? new self(['name' => $name]);
      $setting->data = serialize($data);
      $setting->save();
      return $setting;
    } catch (\Exception $e) {
      return false;
    }
  }

  public static function default($name, $data)
  {
    $setting = static::get($name, null);
    if ($setting === null) {
      static::set($name, $data);
    }
  }

  public static function prettify(Collection $data)
  {
    $settings = [];
    foreach ($data as $value) {
      $settings[$value->name] = unserialize($value->data);
    }

    return $settings;
  }

  public static function export()
  {
    $settings = self::cache();
    $backup = [];
    foreach ($settings as $setting) {
      if (in_array($setting->name, static::$backupKeys)) {
        $backup[$setting->name] = unserialize($setting->data);
      }
    }

    return $backup;
  }

  public static function import(array $data)
  {
    foreach ($data as $name => $value) {
      static::set($name, $value);
    }
  }
}
