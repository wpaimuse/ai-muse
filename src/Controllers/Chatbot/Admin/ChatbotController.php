<?php

namespace AIMuse\Controllers\Chatbot\Admin;

use WP_REST_Response;
use AIMuse\Attributes\Route;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Controllers\Controller;
use AIMuse\Controllers\Request;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Helpers\ChatbotHelper;
use AIMuse\Helpers\PremiumHelper;
use AIMuse\Models\Chatbot;
use AIMuse\Models\ChatbotChat;
use AIMuse\Models\Translation;
use AIMuse\Validators\Validator;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use AIMuseVendor\Symfony\Component\Validator\Constraints;

class ChatbotController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/chatbots/", method="POST")
   */
  public function create(Request $request)
  {
    $violations = $request->validate([
      'name' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string')
      ],
      'settings' => [
        new Constraints\NotBlank(),
        new Constraints\Type('array')
      ],
      'is_global' => [
        new Constraints\Type('bool')
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    /**
     * @var Chatbot $chatbot
     */
    $chatbot = Chatbot::query()->create([
      'name' => $request->json('name'),
      'settings' =>  $request->json('settings'),
      'is_global' => $request->json('is_global')
    ]);

    $chatbot->updateTranslations();

    return new WP_REST_Response($chatbot, 201);
  }

  /**
   * @Route(path="/admin/chatbots", method="GET")
   */
  public function list()
  {
    $query = Chatbot::query();
    $bots = $query->orderBy('id', 'DESC')->get();

    return new WP_REST_Response($bots, 200);
  }

  /**
   * @Route(path="/admin/chatbots/(?P<id>\d+)", method="GET")
   */
  public function get(Request $request)
  {
    $bot = Chatbot::query()->find($request->param('id'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    Log::debug('Chatbot settings', [
      'data' => $bot->settings
    ]);

    return new WP_REST_Response($bot, 200);
  }

  /**
   * @Route(path="/admin/chatbots/(?P<bot>\d+)/chats", method="GET")
   */
  public function chats(Request $request)
  {
    $bot = Chatbot::query()->find($request->param('bot'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    $chats = $bot->chats()->orderBy('id', 'DESC')->get();

    return new WP_REST_Response($chats, 200);
  }

  /**
   * @Route(path="/admin/chatbots/(?P<id>\d+)", method="PUT")
   */
  public function update(Request $request)
  {
    $violations = $request->validate([
      'name' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string')
      ],
      'settings' => [
        new Constraints\NotBlank(),
        new Constraints\Type('array')
      ],
      'is_global' => [
        new Constraints\Type('bool')
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $bot = Chatbot::query()->find($request->param('id'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    $bot->update([
      'name' => $request->json('name'),
      'settings' => $request->json('settings'),
      'is_global' => $request->json('is_global')
    ]);

    $bot->updateTranslations();

    return new WP_REST_Response($bot, 200);
  }

  /**
   * @Route(path="/admin/chatbots/(?P<id>\d+)", method="DELETE")
   */
  public function delete(Request $request)
  {
    /**
     * @var Chatbot $bot
     */
    $bot = Chatbot::query()->find($request->param('id'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    $bot->delete();
    $bot->deleteTranslations();

    return new WP_REST_Response([
      'message' => 'Chatbot deleted successfully'
    ], 200);
  }

  /**
   * @Route(path="/admin/chatbots/(?P<id>\d+)/clear", method="POST")
   */
  public function clear(Request $request)
  {
    /**
     * @var Chatbot $bot
     */
    $bot = Chatbot::query()->find($request->param('id'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    /**
     * @var ChatbotChat[] $chats
     */
    $chats = $bot->chats()->get();

    foreach ($chats as $chat) {
      $chat->messages()->delete();
      $chat->delete();
    }

    return new WP_REST_Response([
      'message' => 'Chatbot cleared successfully'
    ], 200);
  }
}
