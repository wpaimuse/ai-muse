<?php

namespace AIMuse\Controllers\Chatbot\Admin;

use WP_REST_Response;
use AIMuse\Models\Chatbot;
use AIMuse\Attributes\Route;
use AIMuse\Models\ChatbotChat;
use AIMuse\Controllers\Request;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Validators\Validator;
use AIMuse\Models\ChatbotVisitor;
use AIMuse\Controllers\Controller;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use AIMuse\Exceptions\ControllerException;
use AIMuseVendor\League\CommonMark\CommonMarkConverter;
use AIMuseVendor\Symfony\Component\Validator\Constraints;
use AIMuse\Controllers\Chatbot\Visitor\ChatController as VisitorChatController;
use AIMuseVendor\Illuminate\Support\Arr;

class ChatController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/chatbots/chats", method="GET")
   */
  public function all()
  {
    $chats = ChatbotChat::query()->with(['visitor', 'chatbot'])->get();

    return new WP_REST_Response($chats, 200);
  }

  /**
   * @Route(path="/admin/chatbots/chats/list", method="POST")
   */
  public function list(Request $request)
  {
    $violations = $request->validate([
      'chatbot_id' => new Constraints\Optional([
        new Constraints\Type('integer')
      ]),
      'visitor_id' => new Constraints\Optional([
        new Constraints\Type('integer')
      ]),
      'search' => new Constraints\Optional([
        new Constraints\Type('string')
      ]),
      'page' => new Constraints\Optional([
        new Constraints\Type('integer')
      ]),
      'limit' => new Constraints\Optional([
        new Constraints\Type('integer')
      ]),
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $query = ChatbotChat::query();

    $total = $query->count();

    $query->with(['visitor', 'message']);
    $query->withCount('messages');
    $query->orderBy('id', 'DESC');

    if ($request->json('chatbot_id')) {
      $query->where('chatbot_id', $request->json('chatbot_id'));
    }

    if ($request->json('visitor_id')) {
      $query->where('visitor_id', $request->json('visitor_id'));
    }

    if ($request->json('search')) {
      $query->orWhere(function ($query) use ($request) {
        $query->whereHas('visitor', function ($query) use ($request) {
          $query->where('name', 'LIKE', '%' . $request->json('search') . '%');
          $query->orWhere('email', 'LIKE', '%' . $request->json('search') . '%');
        });

        $query->orWhereHas('chatbot', function ($query) use ($request) {
          $query->where('name', 'LIKE', '%' . $request->json('search') . '%');
        });
      });
    }

    $page = $request->json('page') ?: 1;
    $limit = $request->json('limit') ?: 10;
    $query->limit($limit)->offset(($page - 1) * $limit);

    $chats = $query->get();

    return new WP_REST_Response([
      'data' => $chats,
      'total' => $total
    ], 200);
  }

  /**
   * @Route(path="/admin/chatbots/chats/(?P<id>\d+)", method="GET")
   */
  public function get(Request $request)
  {
    $chat = ChatbotChat::query()->with(['visitor', 'chatbot'])->find($request->param('id'));

    if (!$chat) {
      throw ControllerException::make('Chat not found', 404);
    }

    return new WP_REST_Response($chat, 200);
  }

  /**
   * @Route(path="/admin/chatbots/chats/(?P<id>\d+)/sendEmail", method="POST")
   */
  public function sendEmail(Request $request)
  {
    /**
     * @var ChatbotChat $chat
     */
    $chat = ChatbotChat::query()->with(['visitor', 'messages', 'chatbot'])->find($request->param('id'));

    if (!$chat) {
      throw ControllerException::make('Chat not found', 404);
    }

    $chat->notify();

    return new WP_REST_Response(null, 200);
  }

  /**
   * @Route(path="/admin/chatbots/chats/(?P<id>\d+)/messages", method="POST")
   */
  public function messages(Request $request)
  {
    $violations = $request->validate([
      'chat_id' => [
        new Constraints\NotBlank(),
        new Constraints\Type('integer')
      ],
      'page' => new Constraints\Optional([
        new Constraints\Type('integer')
      ]),
      'limit' => new Constraints\Optional([
        new Constraints\Type('integer')
      ]),
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $chat = ChatbotChat::query()->find($request->json('chat_id'));

    if (!$chat) {
      throw ControllerException::make('Chat not found', 404);
    }

    $query = $chat->messages();
    $total = $query->count();

    $query->orderBy('id', 'DESC');

    if ($request->json('page')) {
      $page = $request->json('page') ?: 1;
      $limit = $request->json('limit') ?: 10;
      $query->limit($limit)->offset(($page - 1) * $limit);
    }

    $messages = $query->get();

    return new WP_REST_Response([
      'data' => $messages,
      'total' => $total
    ], 200);
  }

  /**
   * @Route(path="/admin/chatbots/chats/delete", method="POST")
   */
  public function delete(Request $request)
  {
    $violations = $request->validate([
      'ids' => new Constraints\All([
        new Constraints\NotBlank(),
        new Constraints\Type('integer')
      ]),
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $chats = ChatbotChat::query()->whereIn('id', $request->json('ids'))->get();

    $chats->each(function ($chat) {
      $chat->delete();
    });

    return new WP_REST_Response(null, 204);
  }

  /**
   * @Route(path="/admin/chatbots/chats/inspect", method="POST")
   */
  public function inspect(Request $request)
  {
    $violations = $request->validate(new Constraints\Collection([
      'fields' => [
        'chatbot_id' => [
          new Constraints\NotBlank(),
          new Constraints\Type('integer')
        ],
        'visitor_id' => new Constraints\Optional([
          new Constraints\Type('integer')
        ]),
        'chat_id' => new Constraints\Optional([
          new Constraints\Type('integer')
        ]),
        'post' => new Constraints\Optional([
          new Constraints\Type('integer')
        ]),
        'prompt' => [
          new Constraints\NotBlank(),
          new Constraints\Type('string')
        ]
      ],
      'allowExtraFields' => true,
    ]), 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    /**
     * @var Chatbot $bot
     */
    $bot = Chatbot::query()->find($request->json('chatbot_id'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    $visitor = null;

    if ($request->json('visitor_id')) {
      $visitor = ChatbotVisitor::query()->find($request->json('visitor_id'));

      if (!$visitor) {
        throw ControllerException::make('Visitor not found', 404);
      }
    } else {
      $visitor = ChatbotVisitor::query()->create([
        'name' => 'Inspector User',
        'email' => 'test@test.com',
        'ip' => $_SERVER['REMOTE_ADDR'],
        'session_id' => ChatbotVisitor::generateSessionId()
      ]);
    }

    $chat = null;

    if ($request->json('chat_id')) {
      $chat = $visitor->chats()->find($request->json('chat_id'));

      if (!$chat) {
        throw ControllerException::make('Chat not found', 404);
      }
    } else {
      $chat = $bot->chats()->create([
        'visitor_id' => $visitor->id
      ]);

      $chat->messages()->create([
        'role' => 'user',
        'content' => $request->json('prompt')
      ]);
    }

    $data = [
      'chat_id' => $chat->id,
      'session_id' => $visitor->session_id,
      'preview' => hash_hmac('sha1', 'chatbot-inspect', wp_salt()),
    ];

    if ($request->json('url')) {
      $data['url'] = $request->json('url');
      $data['hash'] = $bot->generateURLHash($request->json('url'));
    }

    // return new WP_REST_Response($data, 201);
    return (new VisitorChatController())->generate(Request::make($data));
  }
}
