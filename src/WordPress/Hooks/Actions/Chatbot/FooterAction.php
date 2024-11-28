<?php

namespace AIMuse\WordPress\Hooks\Actions\Chatbot;

use AIMuse\Helpers\ChatbotHelper;
use AIMuse\Models\Chatbot;
use AIMuse\WordPress\Hooks\Actions\Action;
use AIMuse\WordPress\ShortCodes\ChatbotShortCode;
use AIMuseVendor\Illuminate\Support\Facades\Log;

/**
 * Prints scripts or data before the closing body tag on the front end.

 *
 * @link https://developer.wordpress.org/reference/hooks/wp_footer/
 */
class FooterAction extends Action
{
  public function __construct()
  {
    $this->name = "wp_footer";
  }

  private function hasShortCode()
  {
    if (is_front_page() && !get_option('page_on_front')) return false;

    $hasShortCode = has_shortcode(get_the_content(), 'aimuse_chat');

    Log::debug('FooterAction handle', [
      'hasShortCode' => $hasShortCode,
    ]);

    return $hasShortCode;
  }

  public function handle()
  {
    if ($this->hasShortCode()) return;

    $chatbot = Chatbot::query()->where('is_global', true)->first();

    if (!$chatbot) return;

    echo (new ChatbotShortCode())->show(['chatbot' => $chatbot]);
  }
}
