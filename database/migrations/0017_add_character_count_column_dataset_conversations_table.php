<?php

if (!defined('ABSPATH')) exit;

use AIMuseVendor\Illuminate\Database\Migrations\Migration;
use AIMuseVendor\Illuminate\Database\Schema\Blueprint;
use AIMuseVendor\Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public $feature = 'datasets';
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('aimuse_dataset_conversations', function (Blueprint $table) {
      $table->integer('character_count')->default(0)->after('priority');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('aimuse_dataset_conversations', function (Blueprint $table) {
      $table->dropColumn('character_count');
    });
  }
};
