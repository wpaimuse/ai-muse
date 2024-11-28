<?php

namespace AIMuse\WordPress\Schedules;

use AIMuse\Models\Chatbot;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Carbon;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ChatbotDailyNotificationSchedule extends Schedule
{
  protected $name = 'aimuse_chatbot_daily_notification';
  protected $interval = 'daily';

  public function __construct()
  {
    $this->timestamp = Carbon::now()->setTimezone(wp_timezone())->addDay()->setTime(9, 0, 0)->unix();
    parent::__construct();
  }

  public function run()
  {
    /**
     * @var Chatbot[]|\AIMuseVendor\Illuminate\Database\Eloquent\Collection $chatbots
     */
    $chatbots = Chatbot::all();

    Log::info('Running daily chatbot notification schedule', [
      'chatbots' => $chatbots->count(),
    ]);

    foreach ($chatbots as $chatbot) {
      $enabled = Arr::get($chatbot->settings, 'notifications.daily.enabled', false);

      if (!$enabled) return;

      $chatbot->notify('daily');
    }
  }
}
