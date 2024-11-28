<?php

namespace AIMuse\Controllers\Chatbot\Visitor;

use WP_REST_Response;
use AIMuse\Models\Chatbot;
use AIMuse\Attributes\Route;
use AIMuse\Models\ChatbotChat;
use AIMuse\Controllers\Request;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Validators\Validator;
use AIMuse\Models\ChatbotVisitor;
use AIMuse\Controllers\Controller;
use AIMuse\Exceptions\ControllerException;
use AIMuseVendor\Symfony\Component\Validator\Constraints;
use AIMuse\Controllers\GenerateTextController;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Carbon;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
  /**
   * @Route(path="/chatbots/messages/create", method="POST")
   */
  public function create(Request $request)
  {
    $violations = $request->validate([
      'chat_id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('integer')
      ],
      'session_id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string')
      ],
      'message' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string'),
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    /**
     * @var ChatbotChat $chat
     */
    $chat = ChatbotChat::query()->find($request->json('chat_id'));

    if (!$chat) {
      throw ControllerException::make('Chat not found', 404);
    }

    /**
     * @var ChatbotVisitor $visitor
     */
    $visitor = ChatbotVisitor::query()->where('session_id', $request->json('session_id'))->first();

    if (!$visitor) {
      throw ControllerException::make('Visitor not found', 404);
    }

    $lastMessage = $chat->messages()->latest('id')->first();

    if ($lastMessage && $lastMessage->role === 'user') {
      throw ControllerException::make("You already sent a message. Please retry after refreshing the page.", 400);
    }

    /**
     * @var Chatbot $bot
     */
    $bot = $chat->chatbot;

    $messageLimit = Arr::get($bot->settings, 'advanced.daily_message_limit', 0);

    if ($messageLimit > 0) {
      $chats = $visitor->chats()
        ->select('id')
        ->where('chatbot_id', $bot->id)
        ->withCount([
          'messages' => function ($query) {
            $query->whereDate('created_at', Carbon::today());
          }
        ])
        ->get();

      $totalMessages = $chats->sum('messages_count');

      Log::debug('Messages count: ' . $totalMessages, [
        'visitor_id' => $visitor->id,
        'chats' => $chats
      ]);

      if ($totalMessages >= $messageLimit) {
        throw ControllerException::make('You have reached the daily message limit', 429);
      }
    }

    $message = $chat->messages()->create([
      'content' => $request->json('message'),
      'role' => 'user'
    ]);

    return new WP_REST_Response($message, 201);
  }

  /**
   * @Route(path="/chatbots/messages/list", method="POST")
   */
  public function list(Request $request)
  {
    $violations = $request->validate([
      'chat_id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('integer')
      ],
      'session_id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('string')
      ]
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    /**
     * @var ChatbotVisitor $visitor
     */
    $visitor = ChatbotVisitor::query()->where('session_id', $request->json('session_id'))->first();

    if (!$visitor) {
      throw ControllerException::make('Visitor not found', 404);
    }

    /**
     * @var ChatbotChat $chat
     */
    $chat = $visitor->chats()->find($request->json('chat_id'));

    if (!$chat) {
      throw ControllerException::make('Chat not found', 404);
    }

    $messages = $chat->messages()->get();

    return new WP_REST_Response($messages, 200);
  }
}
