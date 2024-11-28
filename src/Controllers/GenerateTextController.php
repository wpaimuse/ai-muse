<?php

namespace AIMuse\Controllers;

use WP_REST_Response;
use AIMuse\Models\Settings;
use AIMuse\Models\Template;
use AIMuse\Attributes\Route;
use AIMuse\Data\GenerateTextOptions;
use AIMuse\Exceptions\ApiKeyNotSetException;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Exceptions\GenerateException;
use AIMuse\Helpers\PostHelper;
use AIMuse\Helpers\PremiumHelper;
use AIMuse\Helpers\PromptHelper;
use AIMuse\Validators\Validator;
use AIMuse\Helpers\ResponseHelper;
use AIMuse\Middleware\Editor;
use AIMuse\Models\AIModel;
use AIMuse\Models\Dataset;
use AIMuse\Services\OpenAI\OpenAI;
use AIMuse\Validators\GenerateTextValidator;
use AIMuse\Services\GoogleAI\GoogleAI;
use AIMuse\Services\OpenRouter\OpenRouter;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class GenerateTextController extends Controller
{
  public array $middlewares = [
    Editor::class,
  ];

  /**
   * @Route(path="/admin/generate/text", method="POST")
   */
  public function text(Request $request)
  {
    $violations = $request->validate(GenerateTextValidator::class);

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    if ($request->json('isRetry')) {
      ResponseHelper::prepare([
        'channel' => $request->json('channel'),
      ]);

      ResponseHelper::setTimeout($request->timeout);
      ResponseHelper::initCache();
      ResponseHelper::serveCache();
    }

    $systemPrompt = '';

    if (!$request->withoutSystemPrompt) {
      $systemPromptSlugs = ['global-text'];

      if (in_array($request->component, ['text-block'])) {
        $systemPromptSlugs[] = $request->component;
      }

      $systemPrompts = Template::query()->whereIn('slug', $systemPromptSlugs);
      $systemPrompt = $systemPrompts->pluck('prompt')->implode("\n");
    }

    $systemPrompt = $request->json('systemPrompt') ?? $systemPrompt;

    $request->templates = collect($request->templates);
    $slugs = $request->templates->pluck('slug')->toArray();
    $templates = Template::query()->whereIn('slug', $slugs)->get();

    $prompts = [];

    if ($request->prompt) {
      $prompts[] = $request->prompt;
    }

    foreach ($request->templates as $template) {
      $prompt = $templates->where('slug', $template['slug'])->first()->prompt;
      $option = $template['option'] ?? '';
      $prompts[] = str_replace('{{option}}', $option, $prompt);
    }

    $datasetSlugs = [];

    if ($request->json('dataset')) {
      array_push($datasetSlugs, $request->json('dataset'));
    }

    foreach ($templates as $template) {
      if (!$template->dataset_slug) continue;

      // Skip the dataset that is already included
      if (in_array($template->dataset_slug, $datasetSlugs)) continue;

      array_push($datasetSlugs, $template->dataset_slug);
    }

    if (count($datasetSlugs) > 0) {
      $prompts[] = 'Datasets';
      $prompts[] = '=====================';
      $datasets = Dataset::query()->whereIn('slug', $datasetSlugs)->get();
      foreach ($datasets as $dataset) {
        $prompts[] = $dataset->name;
        $prompts[] = "{{dataset:{$dataset->id}}}";
      }
    }

    if (!count($prompts) && !count($request->messages)) {
      throw new ControllerException([
        [
          'message' => 'Templates or prompt must be provided'
        ]
      ], 400);
    }

    $userPrompt = implode("\n", $prompts);
    $userPrompt = str_replace('{{text}}', $request->text, $userPrompt);

    if ($request->json('post')) {
      PostHelper::overrideID($request->json('post'));
    } elseif ($request->json('url')) {
      PostHelper::overrideURL($request->json('url'));
    }

    try {
      $userPrompt = PromptHelper::replaceAllVariables($userPrompt);
    } catch (\Throwable $th) {
      Log::error('An error occured when replacing variables in user prompt', [
        'error' => $th->getMessage(),
        'trace' => $th->getTraceAsString(),
        'request' => $request->id,
      ]);

      throw ControllerException::make('An error occured when replacing variables in user prompt', 500);
    }

    if ($systemPrompt) {
      try {
        $systemPrompt = PromptHelper::replaceAllVariables($systemPrompt);
      } catch (\Throwable $th) {
        Log::error('An error occured when replacing variables in system prompt', [
          'error' => $th->getMessage(),
          'trace' => $th->getTraceAsString(),
          'request' => $request->id,
        ]);

        throw ControllerException::make('An error occured when replacing variables in system prompt', 500);
      }
    }

    $model = AIModel::getByRequest($request, 'textModel');

    PremiumHelper::validateModel($model);

    if (!isset(AIModel::$keyNames[$model->service])) {
      throw new ControllerException([
        [
          'message' => "Text model service {$model->service} is not supported"
        ]
      ], 400);
    }

    $apiKey = Settings::get(AIModel::$keyNames[$model->service]);

    if (!$apiKey) {
      throw new ApiKeyNotSetException("Your text model is {$model->service}@{$model->name} but {$model->service} API key is not set");
    }

    if (!$request->json('disableStream')) {
      ResponseHelper::prepare([
        'forceSSE' => $request->json('forceSSE'),
        'secret' => $request->json('secret'),
        'channel' => $request->json('channel'),
      ]);
    }

    $callback = function ($event, $data) {
      Log::debug('Generate text callback', [
        'event' => $event,
        'data' => $data,
      ]);
      return ResponseHelper::send($event, $data);
    };

    $options = new GenerateTextOptions([
      'systemPrompt' => $systemPrompt,
      'userPrompt' => $userPrompt,
      'model' => $model,
      'component' => $request->component,
      'callback' => $request->json('callback') ?? $callback,
      'session' => $request->session ?? "",
      'messages' => $request->messages ?? [],
      'contextLength' => $request->contextLength ?? 0,
    ]);

    if ($request->preview) {
      ResponseHelper::release();
      return new WP_REST_Response($options);
    }

    Log::debug('Generate text options', [
      'options' => $options,
    ]);

    // We need to set the time limit to 0 to prevent the script from timing out when the stream is running
    set_time_limit(0);

    try {
      if ($model->service == 'openai') {
        $client = OpenAI::client($apiKey);
        $client->chat()->stream($options);
      } elseif ($model->service == 'googleai') {
        $client = GoogleAI::client($apiKey);
        $client->content()->stream($options);
      } elseif ($model->service == 'openrouter') {
        $client = OpenRouter::client($apiKey);
        $client->chat()->stream($options);
      }
    } catch (GenerateException $error) {
      ResponseHelper::release();
      throw $error;
    }
  }
}
