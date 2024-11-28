<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Str;
use AIMuseVendor\Illuminate\Support\Carbon;
use AIMuse\Helpers\EmailHelper;
use AIMuse\Exceptions\ControllerException;

class Chatbot extends Model
{
  protected $table = 'aimuse_chatbots';
  protected $guarded = [];

  protected $casts = [
    'settings' => 'array',
    'is_global' => 'boolean'
  ];

  public static array $translatedSettings = [
    'greeting.message',
    'greeting.welcome_message',
    'register.message',
    'register.title',
    'register.strings',
    'strings',
  ];

  public function chats()
  {
    return $this->hasMany(ChatbotChat::class, 'chatbot_id');
  }

  public function messages()
  {
    return $this->hasManyThrough(ChatbotMessage::class, ChatbotChat::class, 'chatbot_id', 'chat_id');
  }

  public function delete()
  {
    $this->chats()->get()->each(function (ChatbotChat $chat) {
      $chat->delete();
    });

    return parent::delete();
  }

  public function generateURLHash($postID)
  {
    $data = [
      "chatbot-{$this->id}",
      "post-{$postID}",
      "aimuse-chatbot-post-hash"
    ];

    return hash_hmac('sha256', implode('|', $data), wp_salt());
  }

  public function updateTranslations()
  {
    $this->deleteTranslations();
    $this->writeTranslations(self::$translatedSettings);
    Translation::generateFile();
  }

  public function writeTranslations($keys)
  {
    foreach ($keys as $key) {
      $value = Arr::get($this->settings, $key);

      if (is_array($value)) {
        $keys = array_keys($value);
        $keys = array_map(fn($k) => "{$key}.{$k}", $keys);
        $this->writeTranslations($keys);
        continue;
      }

      if ($value) {
        Translation::query()->updateOrCreate([
          'key' => "chatbot.{$this->id}.{$key}",
        ], [
          'text' => $value,
          'domain' => "aimuse-chatbot-{$this->id}",
        ]);
      }
    }
  }

  public function deleteTranslations()
  {
    return Translation::query()->where('key', 'like', "chatbot.{$this->id}.%")->delete();
  }

  public function applyTranslations()
  {
    $settings = $this->settings;
    foreach (Chatbot::$translatedSettings as $key) {
      $value = Arr::get($settings, $key);
      $value = __($value, "aimuse-chatbot-{$this->id}");
      Arr::set($settings, $key, $value);
    }
    $this->settings = $settings;
  }

  public function notify(string $period = 'daily')
  {
    $query = $this->chats();

    if ($period === 'daily') {
      $query->where('created_at', '>=', Carbon::now()->subDay());
    } else {
      $query->where('created_at', '>=', Carbon::now()->startOfWeek());
    }

    $count = $query->count();

    if ($count === 0) return;

    $periodMessage = $period === 'daily' ? 'today' : 'this week';

    $html = aimuse()->view()->make('email.chatbot-period', [
      'title' => Str::title("{$this->name} {$period} report"),
      'name' => $this->name,
      'period' => $periodMessage,
      'count' => $count,
      'link' => aimuse()->menu("#/chatbot-history/chatbot/{$this->id}"),
      'settings' => aimuse()->menu("#/chatbots/{$this->id}"),
    ])->render();

    $to = Arr::get($this->settings, 'notifications.email', get_option('admin_email'));
    $subject = Str::title("Chatbot {$this->name} {$period} report");

    $success = EmailHelper::send($to, $subject, $html, [
      'Content-Type: text/html; charset=UTF-8',
    ]);

    if (!$success) {
      throw ControllerException::make('Failed to send email', 500);
    }
  }
}
