<?php

namespace AIMuse\Controllers\Finetuning;

use WP_REST_Response;
use AIMuse\Models\Dataset;
use AIMuse\Attributes\Route;
use AIMuse\Controllers\Request;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Validators\Validator;
use AIMuse\Controllers\Controller;
use AIMuse\Models\DatasetConversation;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Validators\Datasets\Conversations\CreateConversationValidator;
use AIMuse\Validators\Datasets\Conversations\UpdateConversationValidator;
use AIMuse\Validators\Datasets\Conversations\CreateBulkConversationValidator;
use AIMuseVendor\Symfony\Component\Validator\Constraints;

class DatasetConversationController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/finetuning/datasets/conversations", method="GET")
   */
  public function list()
  {
    $conversations = DatasetConversation::query()->get();

    return new WP_REST_Response($conversations, 200);
  }

  /**
   * @Route(path="/admin/finetuning/datasets/conversations", method="POST")
   */
  public function create(Request $request)
  {
    $violations = $request->validate(CreateConversationValidator::class);

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $dataset = Dataset::query()->find($request->json('dataset_id'));

    if (!$dataset) {
      throw ControllerException::make('Dataset not found', 404);
    }

    $conversation = array_merge($request->json(), [
      'character_count' => strlen($request->json('prompt')) + strlen($request->json('response')),
    ]);

    $conversation = $dataset->conversations()->create($conversation);
    $dataset->increment('character_count', $conversation->character_count);

    return new WP_REST_Response($conversation, 201);
  }

  /**
   * @Route(path="/admin/finetuning/datasets/(?P<dataset>[a-zA-z0-9-_]+)/conversations/bulk", method="POST")
   */
  public function bulkCreate(Request $request)
  {
    $violations = $request->validate(CreateBulkConversationValidator::class);

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $dataset = Dataset::query()->where('slug', $request->param('dataset'))->first();

    if (!$dataset) {
      throw ControllerException::make('Dataset not found', 404);
    }

    $conversations = $request->json('conversations');

    foreach ($conversations as &$conversation) {
      $conversation['created_at'] = current_time('mysql');
      $conversation['updated_at'] = current_time('mysql');
      $conversation['dataset_id'] = $dataset->id;
      $conversation['character_count'] = strlen($conversation['prompt']) + strlen($conversation['response']);
    }

    DatasetConversation::query()->insert($conversations);

    $dataset->increment('character_count', array_sum(array_column($conversations, 'character_count')));

    return new WP_REST_Response([
      'message' => 'Conversations created successfully',
    ], 201);
  }

  /**
   * @Route(path="/admin/finetuning/datasets/conversations/(?P<id>\d+)", method="PUT")
   */
  public function update(Request $request)
  {
    $violations = $request->validate(UpdateConversationValidator::class);

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $conversation = DatasetConversation::query()->find($request->param('id'));

    if (!$conversation) {
      throw ControllerException::make('Conversation not found', 404);
    }

    $originalCharacterCount = $conversation->character_count;

    $data = array_merge($request->json(), [
      'character_count' => strlen($request->json('prompt')) + strlen($request->json('response')),
    ]);

    $conversation->update($data);

    $conversation->dataset()->increment('character_count', $conversation->character_count - $originalCharacterCount);

    return new WP_REST_Response($conversation, 200);
  }

  /**
   * @Route(path="/admin/finetuning/datasets/conversations/(?P<id>\d+)", method="DELETE")
   */
  public function delete(Request $request)
  {
    $conversation = DatasetConversation::query()->find($request->param('id'));

    if (!$conversation) {
      throw ControllerException::make('Conversation not found', 404);
    }

    $conversation->dataset()->decrement('character_count', $conversation->character_count);
    $conversation->delete();

    return new WP_REST_Response([
      'message' => 'Conversation deleted successfully',
    ], 200);
  }

  /**
   * @Route(path="/admin/finetuning/datasets/conversations/delete", method="POST")
   */
  public function bulkDelete(Request $request)
  {
    $violations = $request->validate([
      'ids' => [
        new Constraints\NotBlank(),
        new Constraints\Type('array'),
        new Constraints\All([
          new Constraints\Type('int'),
        ]),
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $conversations = DatasetConversation::query()->whereIn('id', $request->json('ids'))->get();
    $dataset_id = $conversations->first()->dataset_id;

    $deleted = DatasetConversation::query()->whereIn('id', $request->json('ids'))->delete();

    Dataset::query()->find($dataset_id)->decrement('character_count', $conversations->sum('character_count'));

    return new WP_REST_Response([
      'message' => 'Conversations deleted successfully',
      'deleted' => $deleted,
    ], 200);
  }

  /**
   * @Route(path="/admin/finetuning/datasets/(?P<dataset>\w+)/conversations/clear", method="GET")
   */
  public function clear(Request $request)
  {
    $dataset = Dataset::query()->find($request->param('dataset'));

    if (!$dataset) {
      throw ControllerException::make('Dataset not found', 404);
    }

    $dataset->conversations()->delete();
    $dataset->update(['character_count' => 0]);

    return new WP_REST_Response([
      'message' => 'Conversations cleared successfully',
    ], 200);
  }
}
