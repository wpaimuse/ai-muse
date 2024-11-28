<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;
use AIMuse\Exceptions\ControllerException;
use AIMuse\Helpers\EmailHelper;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use AIMuseVendor\League\CommonMark\CommonMarkConverter;

class ChatbotChat extends Model
{
  protected $table = 'aimuse_chatbot_chats';
  protected $guarded = [];

  public function chatbot()
  {
    return $this->belongsTo(Chatbot::class, 'chatbot_id');
  }

  public function messages()
  {
    return $this->hasMany(ChatbotMessage::class, 'chat_id');
  }

  public function visitor()
  {
    return $this->belongsTo(ChatbotVisitor::class, 'visitor_id');
  }

  public function message()
  {
    return $this->hasOne(ChatbotMessage::class, 'chat_id')->where('role', 'user');
  }

  public function delete()
  {
    $this->messages()->delete();

    return parent::delete();
  }

  public function notify()
  {
    $admin_email = Arr::get($this->chatbot->settings, 'notifications.email', get_option('admin_email'));

    $visitor = $this->visitor;

    if (!$visitor->email) {
      $visitor->email = $admin_email;
    }

    if (!$visitor->name) {
      $visitor->name = 'User';
    }

    $converter = new CommonMarkConverter([
      'html_input' => 'strip',
      'allow_unsafe_links' => false,
    ]);

    $messages = [];

    foreach ($this->messages as $message) {
      $content = $message->content;

      if ($message->role === 'model') {
        try {
          $content = $converter->convert($content);
        } finally {
        }
      }

      $messages[] = [
        'role' => $message->role,
        'content' => $content,
        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
      ];
    }

    $html = aimuse()->view()->make('email.chatbot-chat', [
      'title' => "Chat#{$this->id} with {$visitor->name}",
      'messages' => $messages,
      'visitor' => $visitor,
      'bot' => $this->chatbot->name ?? 'Chatbot',
    ])->render();

    $subject = "Chatbot Chat#{$this->id} with {$visitor->name}";
    $success = EmailHelper::send($admin_email, $subject, $html, [
      'Content-Type: text/html; charset=UTF-8',
      "Reply-To: {$visitor->email}",
    ]);

    if (!$success) {
      throw ControllerException::make('Failed to send email', 500);
    }

    Log::info('Chatbot chat notification email sent', [
      'chat_id' => $this->id,
      'visitor_id' => $visitor->id,
      'visitor_email' => $visitor->email,
      'visitor_name' => $visitor->name,
      'admin_email' => $admin_email,
    ]);

    $this->notified = true;
    $this->save();
  }
}
