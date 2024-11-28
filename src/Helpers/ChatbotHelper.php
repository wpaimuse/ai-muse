<?php

namespace AIMuse\Helpers;

use AIMuse\Models\Chatbot;
use AIMuse\Models\Settings;

class ChatbotHelper
{
  public static function initialValues()
  {
    return [
      'name' => 'Chatbot',
      'is_global' => false,
      'settings' => [
        'model' => null,
        'systemPrompt' => 'You are a chatbot assistant.',
        'greeting' => [
          'show' => 'on',
          'message' => __('Hello, welcome! How may I help you today?', 'aimuse-chatbot'),
          'welcome_message' => __("Hello, welcome!\nDo you need any help about {{site_title}}?", 'aimuse-chatbot'),
          'delay' => 1000,
        ],
        'register' => [
          'enabled' => true,
          'title' => __('Welcome to Chat!', 'aimuse-chatbot'),
          'message' => __('Please provide your name and email to start the chat', 'aimuse-chatbot'),
          'background_color' => '',
          'background_image' => '',
          'strings' => [
            'name_label' => __('Name', 'aimuse-chatbot'),
            'name_description' => __('Your name to be used in the chat', 'aimuse-chatbot'),
            'email_label' => __('Email', 'aimuse-chatbot'),
            'email_description' => __('Your email address to contact you later', 'aimuse-chatbot'),
            'button' => __('Start Chat', 'aimuse-chatbot'),
          ]
        ],
        'ui' => [
          'primary_color' => '#3172b0',
          'secondary_color' => '#FFFFFF',
          'icon_padding' => 50,
          'icon_color_filter' => false,
          'icon' => self::defaultIcons()[0],
          'button' => [
            'position' => 'right',
            'size' => 60,
            'radius' => 60,
          ],
          'position' => [
            'x' => 20,
            'y' => 20,
          ],
          'chatbox' => [
            'width' => 500,
            'height' => 600,
          ],
          'fontSize' => 14,
          "branding" => [
            "show" => true,
          ],
        ],
        'advanced' => [
          'daily_message_limit' => 100,
          'css' => null,
        ],
        'strings' => [
          'input_placeholder' => __('Type a message...', 'aimuse-chatbot'),
          'new_chat_tooltip' => __('Start new chat', 'aimuse-chatbot'),
          'chat_create_error' => __('Failed to create chat', 'aimuse-chatbot'),
          'message_create_error' => __('An error occurred while sending the message', 'aimuse-chatbot'),
          'message_generate_error' => __('An error occurred while generating the message', 'aimuse-chatbot'),
          'retry_button' => __('Retry', 'aimuse-chatbot'),
          'close' => __('Close', 'aimuse-chatbot'),
          'thinking' => __('Thinking...', 'aimuse-chatbot'),
        ],
        'notifications' => [
          'email' => get_option('admin_email'),
          'after_idle' => [
            'enabled' => false,
            'timeout' => 15,
          ],
          'daily' => [
            'enabled' => false,
          ],
          'weekly' => [
            'enabled' => false,
          ]
        ],
      ]
    ];
  }

  public static function defaultIcons()
  {
    $icons = array();

    for ($i = 1; $i <= 9; $i++) {
      array_push($icons, aimuse()->url('assets/chat/avatars/' . $i . '.png'));
    }

    return $icons;
  }
}
