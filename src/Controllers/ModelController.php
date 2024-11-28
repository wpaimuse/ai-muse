<?php

namespace AIMuse\Controllers;

use WP_REST_Response;
use AIMuse\Models\AIModel;
use AIMuse\Models\Settings;
use AIMuse\Attributes\Route;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Validators\Validator;
use AIMuse\Controllers\Controller;
use AIMuse\Exceptions\ControllerException;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use AIMuse\Validators\UpdateModelValidator;
use AIMuse\WordPress\Schedules\ModelsSyncSchedule;

class ModelController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/models", method="GET")
   */
  public function list()
  {
    $models = AIModel::query()->latest()->get();

    return new WP_REST_Response($models);
  }

  /**
   * @Route(path="/admin/models", method="POST")
   */
  public function create(Request $request)
  {
    // TODO: Implement validation

    // if ($violations->count() > 0) {
    //   return new WP_REST_Response([
    //     'errors' => Validator::toArray($violations),
    //   ], 400);
    // }

    $id = AIModel::generateId(
      $request->json('name'),
      $request->json('service'),
      $request->json('type')
    );

    $request->merge([
      'id' => $id,
      'custom' => true,
    ], 'json');

    $model = AIModel::query()->find($id);

    if ($model) throw ControllerException::make('Model already exists', 400);

    $model = AIModel::query()->create($request->json());

    return new WP_REST_Response([
      'message' => 'Model created successfully',
    ]);
  }

  /**
   * @Route(path="/admin/models", method="PUT")
   */
  public function update(Request $request)
  {
    $violations = $request->validate(UpdateModelValidator::class);

    if ($violations->count() > 0) {
      return new WP_REST_Response([
        'errors' => Validator::toArray($violations),
      ], 400);
    }

    $model = AIModel::query()->find($request->json('id'));

    if (!$model) {
      return new WP_REST_Response(
        [
          'errors' => [
            'message' => 'Model not found',
          ]
        ],
        400
      );
    }

    $model->update($request->json());

    return new WP_REST_Response([
      'message' => 'Model updated successfully',
    ]);
  }

  /**
   * @Route(path="/admin/models/(?P<id>[a-f0-9]+)", method="DELETE")
   */
  public function delete(Request $request)
  {
    $model = AIModel::query()->find($request->param('id'));

    if (!$model) {
      throw ControllerException::make('Model not found', 404);
    }

    if (!$model->custom) {
      throw ControllerException::make('Cannot delete default model', 400);
    }

    $model->delete();

    return new WP_REST_Response([
      'message' => 'Model deleted successfully',
    ]);
  }

  /**
   * @Route(path="/admin/models/sync", method="GET")
   */
  public function sync(Request $request)
  {
    try {
      $models = AIModel::sync();

      Log::info('Models were successfully synchronized manually.', [
        'count' => count($models),
      ]);

      return new WP_REST_Response([
        'message' => 'Models synced successfully.',
      ]);
    } catch (\Throwable $th) {
      Log::error('Model synchronization failed', [
        'error' => $th,
        'trace' => $th->getTrace(),
        'request' => $request->id,
      ]);

      throw new ControllerException([
        [
          'message' => 'Failed to sync models',
        ]
      ], 400);
    }
  }
}
