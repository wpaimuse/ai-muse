<?php

namespace AIMuse\Controllers\Finetuning;

use WP_REST_Response;
use AIMuse\Models\AIModel;
use AIMuse\Models\Dataset;
use AIMuse\Models\Settings;
use AIMuse\Attributes\Route;
use AIMuse\Controllers\Request;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Validators\Validator;
use AIMuse\Helpers\PremiumHelper;
use AIMuse\Controllers\Controller;
use AIMuse\Helpers\ResponseHelper;
use AIMuse\Services\OpenAI\OpenAI;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Exceptions\ApiKeyNotSetException;
use AIMuseVendor\Symfony\Component\Validator\Constraints;

class FinetuneController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  private function getApiKey(Request $request)
  {
    $service = $request->json('service');
    PremiumHelper::validateService($service);

    if ($service !== 'openai') {
      throw ControllerException::make('Only OpenAI is supported for finetuning', 400);
    }

    $apiKey = Settings::get(AIModel::$keyNames[$service], null);

    if (!$apiKey) {
      throw new ApiKeyNotSetException("OpenAI API Key is not set");
    }

    return $apiKey;
  }

  private function getClient(Request $request)
  {
    $apiKey = $this->getApiKey($request);
    $openai = OpenAI::client($apiKey);

    return $openai;
  }

  /**
   * @Route(path="/admin/finetuning/finetune/upload", method="POST")
   */
  public function upload(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'dataset' => [
        new Constraints\NotBlank(),
        new Constraints\Type('integer'),
      ],
      'channel' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'secret' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    /**
     * @var Dataset $dataset
     */
    $dataset = Dataset::query()->find($request->json('dataset'));

    if (!$dataset) {
      throw ControllerException::make('Dataset not found', 404);
    }

    $openai = $this->getClient($request);

    $file = $openai->file();
    $file->path = $dataset->getBackupFilePath("jsonl");
    $file->purpose = 'fine-tune';

    if (!file_exists($file->path)) {
      throw ControllerException::make('Dataset backup file not found', 404);
    }

    $file->callback = function ($event, $data) {
      Log::debug("OpenAI file upload event: $event", [
        'data' => $data,
      ]);
      ResponseHelper::send($event, $data);
    };

    ResponseHelper::prepare([
      'channel' => $request->json('channel'),
      'secret' => $request->json('secret')
    ]);

    try {
      $file->create();
    } catch (ControllerException $error) {
      ResponseHelper::send('error', $error->getErrors());
    }

    exit();
  }

  /**
   * @Route(path="/admin/finetuning/finetune/create", method="POST")
   */
  public function create(Request $request)
  {
    $violations = $request->validate([
      'model' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'file' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'suffix' => [
        new Constraints\NotBlank(null, 'Suffix is required for finetuning'),
        new Constraints\Type('string'),
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }


    $request->merge([
      'service' => 'openai',
    ], 'json');

    $openai = $this->getClient($request);

    $job = $openai->finetune()->create([
      'model' => $request->json('model'),
      'training_file' => $request->json('file'),
      'suffix' => $request->json('suffix')
    ]);

    Log::debug('Finetune job created', [
      'job' => $job,
      'model' => $request->json('model'),
      'file' => $request->json('file'),
    ]);

    return new WP_REST_Response($job);
  }

  /**
   * @Route(path="/admin/finetuning/finetune/get", method="POST")
   */
  public function get(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $openai = $this->getClient($request);

    $job = $openai->finetune()->get($request->json('id'));

    return new WP_REST_Response($job);
  }

  /**
   * @Route(path="/admin/finetuning/finetune/jobs/list", method="POST")
   */
  public function jobs(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $openai = $this->getClient($request);

    $jobs = $openai->finetune()->jobs();

    return new WP_REST_Response($jobs);
  }

  /**
   * @Route(path="/admin/finetuning/finetune/files", method="POST")
   */
  public function files(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $openai = $this->getClient($request);

    $files = $openai->file()->list('fine-tune');

    return new WP_REST_Response($files);
  }

  /**
   * @Route(path="/admin/finetuning/finetune/files/delete", method="POST")
   */
  public function deleteFile(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $openai = $this->getClient($request);

    $openai->file()->delete($request->json('id'));

    return new WP_REST_Response([
      'message' => 'File deleted',
    ], 200);
  }

  /**
   * @Route(path="/admin/finetuning/finetune/delete", method="POST")
   */
  public function delete(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $openai = $this->getClient($request);

    $job = $openai->models()->delete($request->json('id'));

    return new WP_REST_Response($job);
  }

  /**
   * @Route(path="/admin/finetuning/finetune/cancel", method="POST")
   */
  public function cancel(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
      'id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $openai = $this->getClient($request);

    $job = $openai->finetune()->cancel($request->json('id'));

    return new WP_REST_Response($job);
  }

  /**
   * @Route(path="/admin/finetuning/finetune/models", method="POST")
   */
  public function models(Request $request)
  {
    $violations = $request->validate([
      'service' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ],
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $openai = $this->getClient($request);

    $models = $openai->models()->get();

    $finetunedModels = [];

    foreach ($models->data as $model) {
      if (str_starts_with($model->id, 'ft:')) {
        $finetunedModels[] = $model;
      }
    }

    return new WP_REST_Response($finetunedModels);
  }
}
