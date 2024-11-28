<?php

if (!defined('ABSPATH')) exit;

use AIMuseVendor\Illuminate\Database\Migrations\Migration;
use AIMuseVendor\Illuminate\Database\Schema\Blueprint;
use AIMuseVendor\Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public $feature = 'chatbot';
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('aimuse_chatbot_chats', function (Blueprint $table) {
      $table->boolean('notified')->default(false)->after('visitor_id');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('aimuse_chatbot_chats', function (Blueprint $table) {
      $table->dropColumn('notified');
    });
  }
};
