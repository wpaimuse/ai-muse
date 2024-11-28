<?php

if (!defined('ABSPATH')) exit;

use AIMuseVendor\Illuminate\Database\Migrations\Migration;
use AIMuseVendor\Illuminate\Database\Schema\Blueprint;
use AIMuseVendor\Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public $feature = 'models';
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('aimuse_models', function (Blueprint $table) {
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('aimuse_models', function (Blueprint $table) {
      $table->dropTimestamps();
    });
  }
};
