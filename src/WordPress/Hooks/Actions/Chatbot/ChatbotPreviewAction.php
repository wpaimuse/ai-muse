<?php

namespace AIMuse\WordPress\Hooks\Actions\Chatbot;

use AIMuse\WordPress\Hooks\Actions\Action;
use AIMuse\WordPress\ShortCodes\ChatbotShortCode;

/**
 * Fires in head section for all admin pages.

 *
 * @link https://developer.wordpress.org/reference/hooks/admin_head/
 */
class ChatbotPreviewAction extends Action
{
  public function __construct()
  {
    $this->name = "admin_head";
  }

  public function handle()
  {
    if (!current_user_can('manage_options')) return;

    if (!isset($_GET['page'])) return;
    $isChatbotPreview = $_GET['page'] === aimuse()->name() . '-chatbot-preview';

    if (!$isChatbotPreview) return;

    $chatbotId = isset($_GET['id']) ? $_GET['id'] : null;

    echo "</head>";
    echo "<body>";
    $shorcode = new ChatbotShortCode();
    $shorcode->isPreview = true;
    echo $shorcode->show([
      'id' => $chatbotId,
    ]);
    do_action('admin_print_footer_scripts');
    echo "</body></html>";
    exit;
  }
}
