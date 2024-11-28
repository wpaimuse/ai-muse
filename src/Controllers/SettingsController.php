<?php

namespace AIMuse\Controllers;

use AIMuse\Attributes\Route;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Controllers\Controller;
use AIMuse\Controllers\Request;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Helpers\PremiumHelper;
use AIMuse\Models\AIModel;
use AIMuse\Models\Settings;
use AIMuse\Models\Template;
use AIMuse\Validators\SettingsValidator;
use AIMuse\Validators\Validator;
use WP_REST_Response;

class SettingsController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/settings/fetch", method="POST")
   */
  public function fetch(Request $request)
  {
    $query = Settings::query()->select(['name', 'data']);

    if ($request->has('fields')) {
      $query->whereIn('name', $request->fields);
    }

    return Settings::prettify($query->get());
  }

  /**
   * @Route(path="/admin/settings", method="POST")
   */
  public function update(Request $request)
  {
    $violations = $request->validate(SettingsValidator::class);

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $settings = $request->json();

    if (!PremiumHelper::isPremium()) {
      $premiumApiKeyNames = PremiumHelper::getPremiumApiKeyNames();
      $settings = array_diff_key($settings, array_flip($premiumApiKeyNames));

      if (isset($settings['textModel'])) {
        $model = AIModel::query()->find($settings['textModel']);

        if (!$model) {
          throw new ControllerException([
            [
              'message' => 'Invalid model selected',
            ]
          ], 400);
        }

        if (PremiumHelper::serviceIsPremium($model->service)) {
          throw new ControllerException([
            [
              'message' => "You need to upgrade to premium to use {$model->service} service",
            ]
          ], 400);
        }
      }
    }

    foreach ($settings as $name => $value) {
      Settings::set($name, $value);
    }

    return [
      "status" => "success",
      "message" => "Settings saved successfully",
    ];
  }
}
