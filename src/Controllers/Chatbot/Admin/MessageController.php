<?php

namespace AIMuse\Controllers\Chatbot\Admin;

use WP_REST_Response;
use AIMuse\Attributes\Route;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Controllers\Controller;
use AIMuse\Controllers\Request;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Models\ChatbotMessage;

class MessageController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/chatbots/chats/messages", method="GET")
   */
  public function list(Request $request)
  {
    $messages = ChatbotMessage::query()->orderBy('id', 'DESC')->get();

    return new WP_REST_Response($messages, 200);
  }

  /**
   * @Route(path="/admin/chatbots/chats/messages/(?P<id>\d+)", method="GET")
   */
  public function get(Request $request)
  {
    $message = ChatbotMessage::query()->find($request->param('id'));

    if (!$message) {
      throw ControllerException::make('Message not found', 404);
    }

    return new WP_REST_Response($message, 200);
  }

  /**
   * @Route(path="/admin/chatbots/chats/messages/(?P<id>\d+)", method="DELETE")
   */
  public function delete(Request $request)
  {
    $message = ChatbotMessage::query()->find($request->param('id'));

    if (!$message) {
      throw ControllerException::make('Message not found', 404);
    }

    $message->delete();

    return new WP_REST_Response(null, 204);
  }
}
