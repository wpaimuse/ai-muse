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
use AIMuse\Helpers\ChatbotHelper;
use AIMuse\Helpers\PostHelper;
use AIMuse\Helpers\PromptHelper;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use Throwable;
use WP;

class ChatController extends Controller
{
  private $chatbotIdValidator;
  private $chatIdValidator;
  private $sessionIdValidator;

  public function __construct()
  {
    $this->chatbotIdValidator = [
      new Constraints\NotBlank(null, 'Chatbot ID is required'),
      new Constraints\Type('integer', 'Chatbot ID must be an integer')
    ];

    $this->chatIdValidator = [
      new Constraints\NotBlank(null, 'Chat ID is required'),
      new Constraints\Type('integer', 'Chat ID must be an integer')
    ];

    $this->sessionIdValidator = [
      new Constraints\NotBlank(null, 'Session ID is required'),
      new Constraints\Type('string', 'Session ID must be a string')
    ];
  }

  /**
   * @Route(path="/chatbots/chats/create", method="POST")
   */
  public function create(Request $request)
  {
    $violations = $request->validate([
      'chatbot_id' => $this->chatbotIdValidator,
      'session_id' => $this->sessionIdValidator,
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
     * @var Chatbot $bot
     */
    $bot = Chatbot::query()->find($request->json('chatbot_id'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    $chat = $bot->chats()->create([
      'visitor_id' => $visitor->id
    ]);

    $chat->messages = [];

    return new WP_REST_Response($chat, 201);
  }

  /**
   * @Route(path="/chatbots/chats/list", method="POST")
   */
  public function list(Request $request)
  {
    $violations = $request->validate([
      'chatbot_id' => $this->chatbotIdValidator,
      'session_id' => $this->sessionIdValidator,
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $bot = Chatbot::query()->find($request->json('chatbot_id'));

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    $visitor = ChatbotVisitor::query()->where('session_id', $request->json('session_id'))->first();

    if (!$visitor) {
      throw ControllerException::make('Chat session not found.', 404);
    }

    $chats = $bot->chats()
      ->where('visitor_id', $visitor->id)
      ->with(['messages' => function ($query) {
        $query->limit(1);
      }])
      ->get();

    return new WP_REST_Response($chats, 200);
  }

  /**
   * @Route(path="/chatbots/chats/generate", method="POST")
   */
  public function generate(Request $request)
  {
    $violations = $request->validate([
      'chat_id' => $this->chatIdValidator,
      'session_id' => $this->sessionIdValidator,
      'url' => new Constraints\Optional([
        new Constraints\Type('string')
      ]),
      'hash' => new Constraints\Optional([
        new Constraints\Type('string')
      ]),
      'preview' => new Constraints\Optional([
        new Constraints\Type('string')
      ]),
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

    /**
     * @var Chatbot $bot
     */
    $bot = $chat->chatbot;

    if (!$bot) {
      throw ControllerException::make('Chatbot not found', 404);
    }

    $systemPrompt = $bot->settings['systemPrompt'];

    if (!empty($systemPrompt)) {
      $systemPrompt = PromptHelper::replaceUserName($systemPrompt, $visitor->name ?? "");
      $systemPrompt = PromptHelper::replaceUserEmail($systemPrompt, $visitor->email ?? "");
    }

    $data = [
      'systemPrompt' => $systemPrompt,
      'messages' => [],
      'component' => 'chatbot:' . $bot->id,
      'disableStream' => true,
      'withoutSystemPrompt' => true
    ];

    $model = $bot->settings['model'];

    if ($model) {
      $data['model'] = $model;
    }

    $url = $request->json('url');
    if ($url) {
      if ($request->json('hash') === $bot->generateURLHash($url)) {
        $data['url'] = $url;
      } else {
        Log::error(
          'Invalid URL hash provided for chatbot',
          [
            'chat_id' => $chat->id,
            'request' => $request->all(),
          ]
        );
      }
    }

    $last = $chat->messages->last();

    if ($last->role === 'model') {
      // If the last message was generated by the model, return it
      return new WP_REST_Response($last, 200);
    }

    foreach ($chat->messages as $message) {
      $data['messages'][] = [
        'content' => $message->content,
        'role' => $message->role,
      ];
    }

    $response = '';
    $data['callback'] = function ($event, $data) use (&$response) {
      if ($event === 'message') {
        $response .= $data;
      }
    };

    $request->merge($data, 'json');

    $preview = $request->json('preview');
    if ($preview == hash_hmac('sha1', 'chatbot-inspect', wp_salt())) {
      $request->merge(['preview' => true], 'json');
      return (new GenerateTextController())->text($request);
    }

    try {
      (new GenerateTextController())->text($request);
    } catch (Throwable $th) {
      if ($th instanceof ControllerException) {
        $th->log('An error occured when generating text for chatbot', [
          'chat_id' => $chat->id,
          'request' => $request->id,
        ]);
      } else {
        Log::error('An error occured when generating text for chatbot', [
          'chat_id' => $chat->id,
          'error' => $th,
          'trace' => $th->getTrace(),
          'request' => $request->id,
        ]);
      }

      throw ControllerException::make(
        "An internal server error occured when generating text.",
        500
      );
    }

    if ($response === '') {
      throw ControllerException::make(
        'No response generated. Please try again later.',
        500
      );
    }

    $message = $chat->messages()->create([
      'content' => $response,
      'role' => 'model'
    ]);

    return new WP_REST_Response($message, 200);
  }

  /**
   * @Route(path="/chatbots/chats/latest", method="POST")
   */
  public function latest(Request $request)
  {
    $violations = $request->validate([
      'chatbot_id' => $this->chatbotIdValidator,
      'session_id' => $this->sessionIdValidator,
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
    $chat = $visitor->chats()
      ->with('messages')
      ->where('chatbot_id', $request->json('chatbot_id'))
      ->latest("id")
      ->first();

    return new WP_REST_Response($chat, 200);
  }
}
