<?php

namespace AIMuse\Models;

use Exception;
use AIMuse\Database\Model;
use AIMuse\Controllers\Request;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Exceptions\ModelSettingException;
use AIMuse\Models\Casts\Serialize;
use AIMuseVendor\Illuminate\Support\Carbon;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class AIModel extends Model
{
  protected $table = 'aimuse_models';
  protected $guarded = [];
  protected $keyType = 'string';
  public $incrementing = false;

  public static array $keyNames = [
    'openai' => 'openAiApiKey',
    'googleai' => 'googleAiApiKey',
    'openrouter' => 'openRouterApiKey'
  ];

  protected $casts = [
    'settings' => Serialize::class,
    'meta' => Serialize::class,
    'custom' => 'boolean',
  ];

  public static function getByRequest(Request $request, string $settingKey)
  {
    $label = Settings::$labels[$settingKey] ?? $settingKey;
    $defaultModelId = Settings::get($settingKey, null);
    if ($request->has('model')) {
      $model = static::find($request->model['id']);

      if (!$model) {
        if ($request->model['id'] == $defaultModelId) {
          throw new ModelSettingException("Default {$label} is invalid. Model not found with ID {$defaultModelId}", $settingKey);
        } else {
          throw new ControllerException([
            [
              'message' => 'Invalid model selected'
            ]
          ], 400);
        }
      }

      if (isset($request->model['settings'])) {
        $model->settings = $request->model['settings'];
      }
    } else {
      if (!$defaultModelId) {
        throw new ModelSettingException("Default {$label} setting is not set", $settingKey);
      }

      $model = static::find($defaultModelId);

      if (!$model) {
        throw new ModelSettingException("Default {$label} is invalid. Model not found with ID {$defaultModelId}", $settingKey);
      }
    }

    return $model;
  }

  public static function export()
  {
    $models = static::all();
    $models = $models->map(function ($model) {
      if (!is_array($model->settings) || count($model->settings) == 0) {
        return null;
      }

      return [
        'name' => $model->name,
        'service' => $model->service,
        'settings' => $model->settings,
      ];
    })->filter(fn($model) => $model !== null);

    return $models->toArray();
  }

  public static function import(array $data)
  {
    foreach ($data as $backup) {
      $model = static::query()->find($backup['id']);
      Log::info('Model importing', ['model' => $model, 'backup' => $backup]);
      if ($model) {
        $model->settings = $backup['settings'];
        $model->save();
      }
    }
  }

  public static function generateId($name, $service, $type)
  {
    $id = hash_hmac('sha1', $name . $service . $type, 'aimuse-model-id');
    $id = substr($id, 0, 10);
    return $id;
  }

  public static function sync()
  {
    $files = array_keys(static::$keyNames);
    $models = collect([]);

    foreach ($files as $file) {
      $path = aimuse()->dir() . "/database/models/{$file}.json";
      if (!file_exists($path)) {
        throw new Exception("File not found: {$path}");
      }

      $data = json_decode(file_get_contents($path), true);
      $models->push(...$data);
    }

    $models = $models->map(function ($model) {
      $model['id'] = static::generateId($model['name'], $model['service'], $model['type']);

      return $model;
    });

    Log::debug('Deleting old models');
    static::query()
      ->where('custom', false)
      ->whereNotIn('id', $models->pluck('id'))
      ->delete();

    $models->each(function ($model) {
      Log::debug('Model syncing', ['model' => $model]);
      $created = static::query()->updateOrCreate([
        'id' => $model['id'],
      ], [
        'name' => $model['name'],
        'service' => $model['service'],
        'type' => $model['type'],
        'meta' => $model['meta'],
        'created_at' => Carbon::createFromTimestamp($model['created']),
      ]);
      Log::debug('Model created', ['model' => $created]);

      if ($created->name == 'gpt-4o-mini') {
        Settings::default('textModel', $created->id);
      } elseif ($created->name == 'dall-e-3') {
        Settings::default('imageModel', $created->id);
      }
    });

    return $models->toArray();
  }
}
