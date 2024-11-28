<?php

namespace AIMuse\Helpers;

use AIMuse\Models\Settings;

class AdminScriptHelper
{
  public static bool $enabled = false;
  private static bool $registered = false;
  public static string $name = '';
  private static array $dependencies = [
    'wp-components',
    'wp-element',
    'wp-api-fetch',
    'wp-url',
    'wp-blob',
    'wp-i18n',
    'moment',
    'lodash',
    'tippy',
    'media-upload'
  ];
  private static array $data = [];

  public static function register()
  {
    if (self::$registered || !static::$enabled) return;

    static::$name = aimuse()->name() . "-main";

    wp_register_style('aimuse-admin', aimuse()->url('assets/css/admin.css'), [], aimuse()->version());
    wp_enqueue_style('aimuse-admin');

    wp_register_script('popperjs', aimuse()->url('assets/plugins/popperjs/popper.min.js'), [], '2.11.8', true);
    wp_register_script('tippy', aimuse()->url('assets/plugins/tippy/tippy-bundle.umd.min.js'), ['popperjs'], '6.3.7', true);
    wp_enqueue_script('tippy');


    $css_deps = array_filter(
      self::$dependencies,
      function ($d) {
        return wp_style_is($d, 'registered');
      }
    );

    foreach ($css_deps as $dep) {
      wp_enqueue_style($dep);
    }

    self::enqueue();


    $userLocale = get_user_locale();
    $postTypes = PostHelper::getPostTypes();
    $postType = $postTypes[array_search(PostHelper::getPostType(), array_column($postTypes, 'name'))];
    $blogId = get_current_blog_id();
    $userId = get_current_user_id();

    $adminLocalizeScript = array(
      'api_base' => get_rest_url($blogId, '/' . aimuse()->name() . '/v1'),
      'wp_base' => get_rest_url($blogId, '/wp/v2'),
      'version' => aimuse()->version(),
      'nonce' => wp_create_nonce('wp_rest'),
      'app_name' => aimuse()->name(),
      'assets' => array(
        'logo' => aimuse()->url('public/assets/images/logo.png'),
        'icon' => aimuse()->url('public/assets/images/icon.png'),
        'icon_light' => aimuse()->url('public/assets/images/icon_light.png'),
        'icon_36' => aimuse()->url('public/assets/images/icon_36.png'),
        'icon_64' => aimuse()->url('public/assets/images/icon_64.png'),
        'icon_128' => aimuse()->url('public/assets/images/icon_128.png'),
        'avatar' => get_avatar_url($userId)
      ),
      'chatbot' => array(
        'icons' => ChatbotHelper::defaultIcons(),
        'initialValues' => ChatbotHelper::initialValues(),
      ),
      'max_upload_size' => wp_max_upload_size(),
      'premium' => PremiumHelper::toArray(),
      'locale' => $userLocale,
      'post_types' => $postTypes,
      'post_type' => $postType,
      'screen' => aimuse()->has('screen') ? aimuse()->get('screen') : (object)[],
      'is_stream_checked' => Settings::get('isStreamChecked', false),
      'wp_version' => get_bloginfo('version'),
      'php_version' => PHP_VERSION,
    );

    $adminLocalizeScript = array_merge($adminLocalizeScript, self::$data);

    wp_localize_script(
      static::$name,
      'WPAIMuse',
      $adminLocalizeScript
    );

    wp_localize_script(
      static::$name,
      'WPAIMuseStream',
      array(
        'available' => Settings::get('isStreamAvailable', false),
        'token' => Settings::get('apiToken', ''),
      )
    );

    self::$registered = true;
  }

  private static function enqueue()
  {
    wp_register_script(static::$name, aimuse()->url('assets/dist/admin/main.js'), self::$dependencies, aimuse()->version(), true);
    wp_register_style(static::$name, aimuse()->url('assets/dist/admin/style.css'), null, aimuse()->version(), 'all');

    wp_enqueue_script(static::$name);
    wp_enqueue_style(static::$name);
  }

  public static function addDependencies($deps)
  {
    self::$dependencies = array_merge(self::$dependencies, $deps);
  }

  public static function addData($data)
  {
    self::$data = array_merge(self::$data, $data);
  }

}
