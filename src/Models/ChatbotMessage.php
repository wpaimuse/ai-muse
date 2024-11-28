<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;

class ChatbotMessage extends Model
{
  protected $table = 'aimuse_chatbot_messages';
  protected $guarded = [];

  public function chat()
  {
    return $this->belongsTo(ChatbotChat::class, 'chat_id');
  }
}
