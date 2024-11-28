<?php

namespace AIMuse\WordPress\Schedules;

use AIMuse\Models\Chatbot;
use AIMuseVendor\Carbon\Carbon;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ChatbotWeeklyNotificationSchedule extends Schedule
{
  protected $name = 'aimuse_chatbot_weekly_notification';
  protected $interval = 'weekly';

  public function __construct()
  {
    $this->timestamp = Carbon::now()->setTimezone(wp_timezone())->addWeek()->startOfWeek(1)->setTime(9, 0, 0)->unix();
    parent::__construct();
  }

  public function run()
  {
    /**
     * @var Chatbot[]|\AIMuseVendor\Illuminate\Database\Eloquent\Collection $chatbots
     */
    $chatbots = Chatbot::all();

    Log::info('Running weekly chatbot notification schedule', [
      'chatbots' => $chatbots->count(),
    ]);

    foreach ($chatbots as $chatbot) {
      $enabled = Arr::get($chatbot->settings, 'notifications.weekly.enabled', false);

      if (!$enabled) return;

      $chatbot->notify('weekly');
    }
  }
}
