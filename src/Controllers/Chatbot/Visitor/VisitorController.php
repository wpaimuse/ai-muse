<?php

namespace AIMuse\Controllers\Chatbot\Visitor;

use WP_REST_Response;
use AIMuse\Attributes\Route;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Controllers\Controller;
use AIMuse\Controllers\Request;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Models\Chatbot;
use AIMuse\Models\ChatbotChat;
use AIMuse\Models\ChatbotVisitor;
use AIMuse\Validators\Validator;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Symfony\Component\Validator\Constraints;

class VisitorController extends Controller
{
  /**
   * @Route(path="/chatbots/visitors", method="POST")
   */
  public function register(Request $request)
  {
    $violations = $request->validate([
      'chatbot_id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('integer')
      ],
      'name' => new Constraints\Optional([
        new Constraints\NotBlank(),
        new Constraints\Type('string')
      ]),
      'email' => new Constraints\Optional([
        new Constraints\NotBlank(),
        new Constraints\Email()
      ]),
      'session_id' => new Constraints\Optional([
        new Constraints\Type('string')
      ])
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    if ($request->json('chatbot_id') != 0) {
      $bot = Chatbot::query()->find($request->json('chatbot_id'));

      if (!$bot) {
        throw ControllerException::make('Chatbot not found', 404);
      }
    }

    /**
     * @var ChatbotVisitor|null $visitor
     */
    $visitor = null;

    if ($request->json('session_id')) {
      $visitor = ChatbotVisitor::query()->where('session_id', $request->json('session_id'))->first();

      if (!$visitor) {
        throw ControllerException::make('Visitor not found', 404);
      }

      $visitor->update([
        'name' => $request->json('name'),
        'email' => $request->json('email')
      ]);

      $visitor->chat = $visitor->chats()
        ->with('messages')
        ->where('chatbot_id', $request->json('chatbot_id'))
        ->latest("id")
        ->first();
    } else {
      $visitor = ChatbotVisitor::query()->create([
        'name' => $request->json('name'),
        'email' => $request->json('email'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'session_id' => ChatbotVisitor::generateSessionId()
      ]);

      if ($request->json('chatbot_id') != 0) {
        $visitor->chat = $visitor->chats()->create([
          'chatbot_id' => $request->json('chatbot_id')
        ]);
        $visitor->chat->messages = [];
      } else {
        $visitor->chat = new ChatbotChat([
          'id' => 0,
          'messages' => []
        ]);
      }
    }

    return new WP_REST_Response($visitor, 201);
  }

  /**
   * @Route(path="/chatbots/(?P<chatbot>\d+)/visitors/(?P<session>[a-f0-9]+)", method="GET")
   */
  public function get(Request $request)
  {
    /**
     * @var ChatbotVisitor $visitor
     */
    $visitor = ChatbotVisitor::query()->where('session_id', $request->param('session'))->first();

    if (!$visitor) {
      throw ControllerException::make('Visitor not found', 404);
    }

    $visitor->chat = $visitor->chats()
      ->with('messages')
      ->where('chatbot_id', $request->param('chatbot'))
      ->latest("id")
      ->first();

    if ($request->param('chatbot') == 0) {
      $visitor->chat = new ChatbotChat([
        'id' => 0,
        'messages' => []
      ]);
    } elseif (!$visitor->chat) {
      $visitor->chat = $visitor->chats()->create([
        'chatbot_id' => $request->param('chatbot')
      ]);
      $visitor->chat->messages = [];
    }

    return new WP_REST_Response($visitor, 200);
  }
}
