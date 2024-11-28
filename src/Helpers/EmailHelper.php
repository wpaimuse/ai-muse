<?php

namespace AIMuse\Helpers;

use AIMuseVendor\Illuminate\Support\Facades\Log;

class EmailHelper
{
  public static function send(
    $to,
    $subject,
    $message,
    $headers = '',
    $attachments = array()
  ) {
    $wp_mail_failed = function ($data) {
      Log::error('Failed to send chatbot chat notification email', [
        'data' => $data
      ]);
    };

    add_action('wp_mail_failed', $wp_mail_failed);

    $success = wp_mail(
      $to,
      $subject,
      $message,
      $headers,
      $attachments
    );

    remove_action('wp_mail_failed', $wp_mail_failed);

    return $success;
  }
}
