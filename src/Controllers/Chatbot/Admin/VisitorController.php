<?php

namespace AIMuse\Controllers\Chatbot\Admin;

use WP_REST_Response;
use AIMuse\Attributes\Route;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Controllers\Controller;
use AIMuse\Models\ChatbotVisitor;

class VisitorController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/chatbots/visitors", method="GET")
   */
  public function list()
  {
    $list = ChatbotVisitor::all();

    return new WP_REST_Response($list, 200);
  }
}
