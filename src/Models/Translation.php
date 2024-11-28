<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;
use AIMuseVendor\Illuminate\Support\Facades\File;

class Translation extends Model
{
  protected $table = 'aimuse_translations';
  protected $guarded = [];

  protected $primaryKey = 'key';
  public $incrementing = false;
  protected $keyType = 'string';
  public $timestamps = false;

  public static function generateFile()
  {
    $path = aimuse()->dir() . 'languages/translations.php';
    $code = File::get(aimuse()->dir() . 'languages/translations.stub');

    self::query()->chunk(1000, function ($translations) use (&$code) {
      foreach ($translations as $translation) {
        $translation->text = str_replace("'", "\'", $translation->text);
        $translation->text = str_replace("\n", '\n', $translation->text);
        $code .= "__('{$translation->text}', '{$translation->domain}');" . PHP_EOL;
      }
    });

    File::put($path, $code);
  }
}
