<?php

namespace AIMuse\WordPress\Hooks\Actions;

use AIMuse\Helpers\AdminScriptHelper;
use AIMuse\Helpers\ChatbotHelper;
use AIMuse\Helpers\PostHelper;
use AIMuse\Helpers\PremiumHelper;
use AIMuse\Models\Settings;

/**
 * Action Hook: Fires when enqueuing scripts for all admin pages.
 *
 * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
 */
class AdminEnqueueScriptsAction extends Action
{
  public function __construct()
  {
    $this->name = 'admin_enqueue_scripts';
  }

  public function handle()
  {
    AdminScriptHelper::register();
  }
}
