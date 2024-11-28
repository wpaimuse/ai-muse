<?php

namespace AIMuse\WordPress\Hooks\Actions\Chatbot;

use AIMuse\WordPress\Hooks\Actions\Action;

/**
 * Fires before the administration menu loads in the admin.

 *
 * @link https://developer.wordpress.org/reference/hooks/admin_menu/
 */
class AdminMenuAction extends Action
{
  public function __construct()
  {
    $this->name = "admin_menu";
    $this->priority = 11;
  }

  public function display()
  {
    return null;
  }

  public function handle(string $context)
  {
    add_submenu_page(
      '',
      'Chatbot Preview',
      'Chatbot Preview',
      'manage_options',
      aimuse()->name() . '-chatbot-preview',
      array($this, 'display')
    );
  }
}
