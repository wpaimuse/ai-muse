<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ChatbotVisitor extends Model
{
  protected $table = 'aimuse_chatbot_visitors';
  protected $guarded = [];

  public static function generateSessionId()
  {
    return bin2hex(random_bytes(16));
  }

  public function chats()
  {
    return $this->hasMany(ChatbotChat::class, 'visitor_id');
  }
}
