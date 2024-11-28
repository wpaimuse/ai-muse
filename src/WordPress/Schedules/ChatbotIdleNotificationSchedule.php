<?php

namespace AIMuse\WordPress\Schedules;

use AIMuse\Models\Chatbot;
use AIMuse\Models\ChatbotChat;
use AIMuseVendor\Illuminate\Database\Eloquent\Collection;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Carbon;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ChatbotIdleNotificationSchedule extends Schedule
{
  protected $name = 'aimuse_chatbot_idle_notification';
  protected $interval = 'every_15_minutes';

  public function __construct()
  {
    $this->timestamp = strtotime('+15 minutes');
    parent::__construct();
  }

  public function run()
  {
    /**
     * @var Chatbot[] $chatbots
     */
    $chatbots = Chatbot::all();

    foreach ($chatbots as $chatbot) {
      Log::info('Checking idle chats', [
        'chatbot' => $chatbot,
      ]);

      $enabled = Arr::get($chatbot->settings, 'notifications.after_idle.enabled', false);

      if (!$enabled) return;

      $idleTime = Arr::get($chatbot->settings, 'notifications.after_idle.timeout', 0);

      /**
       * @var ChatbotChat[]|Collection $chats
       */
      $chats = $chatbot->chats()
        ->where('created_at', '>=', Carbon::now()->subMinutes(30))
        ->where('notified', false)
        ->whereDoesntHave('messages', function ($query) use ($idleTime) {
          $query
            ->where('role', 'user')
            ->where('created_at', '>=', Carbon::now()->subMinutes($idleTime));
        })
        ->withCount('messages')
        ->get();

      Log::info('Idle chats', [
        'chats' => $chats
      ]);

      foreach ($chats as $chat) {
        if ($chat->messages_count === 0) continue;

        $chat->notify();
      }
    }
  }
}
