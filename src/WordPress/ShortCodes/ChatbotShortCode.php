<?php

namespace AIMuse\WordPress\ShortCodes;

use AIMuse\Helpers\ChatbotHelper;
use AIMuse\Helpers\PostHelper;
use AIMuse\Helpers\PremiumHelper;
use AIMuse\Models\Chatbot;
use AIMuse\WordPress\ShortCodes\ShortCode;
use AIMuseVendor\Illuminate\Support\Arr;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ChatbotShortCode extends ShortCode
{
  protected string $name = 'aimuse_chat';
  protected array $scripts = [];
  public $isPreview = false;

  private function excludePremiumSettings(array $settings)
  {
    return Arr::except($settings, [
      'ui.primary_color',
      'ui.secondary_color',
      'ui.icon_color_filter',
      'ui.icon_padding',
      'ui.button',
      'ui.promptInput',
      'ui.chatbox',
      'ui.fontSize',
      'ui.branding.show'
    ]);
  }

  public function show(array $attributes = [])
  {
    Log::debug('ChatbotShortCode show', [
      'attributes' => $attributes,
      'index' => $this->index,
    ]);

    if ($this->index > 1) return;

    $chatbot = $attributes['chatbot'] ?? null;

    if (isset($attributes['id'])) {
      $chatbot = Chatbot::query()->find(intval($attributes['id']));
      if (!$chatbot) return;
    }

    /**
     * @var Chatbot $chatbot
     */

    $exceptions = ['model', 'systemPrompt', 'dataset_slug', 'advanced.daily_message_limit'];

    if ($chatbot) {
      $chatbot->makeHidden(['created_at', 'updated_at', 'is_global']);
      $chatbot->settings = Arr::except($chatbot->settings, $exceptions);
    }

    $defaults = ChatbotHelper::initialValues();
    $defaults['settings'] = Arr::except($defaults['settings'], $exceptions);
    $defaults = Arr::except($defaults, ['is_global']);

    $blogId = get_current_blog_id();

    $attributeKeys = [
      'daily_limit' => 'daily.limit',
    ];

    $jsDependencies = [];


    wp_register_script(aimuse()->name() . '-chatbot', aimuse()->url('assets/dist/chatbot/main.js'), $jsDependencies, aimuse()->version(), true);
    wp_enqueue_script(aimuse()->name() . '-chatbot');

    wp_register_style(aimuse()->name() . '-chatbot', aimuse()->url('assets/dist/chatbot/style.css'), null, aimuse()->version(), 'all');
    wp_enqueue_style(aimuse()->name() . '-chatbot');

    if (!$chatbot) {
      /**
       * @var Chatbot $chatbot
       */
      $chatbot = new Chatbot($defaults);
      $chatbot->id = 0;
    };

    $chatbot->applyTranslations();

    if (!PremiumHelper::isPremium()) {
      $chatbot->settings = $this->excludePremiumSettings($chatbot->settings);
    }

    $chatbotScriptData = [
      'details' => $chatbot ? $chatbot->toArray() : null,
      'defaults' => $defaults,
      'is_preview' => $this->isPreview,
      'api_base' => get_rest_url($blogId, '/' . aimuse()->name() . '/v1'),
      'nonce' => wp_create_nonce('wp_rest'),
      'variables' => [
        'site_title' => get_bloginfo('name'),
        'post_title' => PostHelper::getTitle(),
        'product_name' => PostHelper::getTitle(),
      ],
      'logo' => aimuse()->url('public/assets/images/icon.png'),
    ];

    $url = $_SERVER['REQUEST_URI'];
    $chatbotScriptData['url'] = $url;
    $chatbotScriptData['hash'] = $chatbot->generateURLHash($url);

    Log::debug('Chatbot script data', $chatbotScriptData);

    wp_localize_script(
      aimuse()->name() . '-chatbot',
      'WPAIMuseChatBot',
      $chatbotScriptData,
    );

    $content = "";


    $content .= '<div data-chatbot-id="' . $chatbot->id . '" class="aimuse_chat_bot"></div>';

    return $content;
  }


}
