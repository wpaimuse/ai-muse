<?php

namespace AIMuse;

use AIMuse\Controllers\LogController;
use AIMuseVendor\Monolog\Logger;
use WP_Filesystem_Direct;
use AIMuse\Models\Settings;
use AIMuse\Services\Api\Client;
use AIMuseVendor\Monolog\Handler\StreamHandler;
use AIMuseVendor\Illuminate\Container\Container;
use AIMuseVendor\Illuminate\Events\Dispatcher;
use AIMuseVendor\Illuminate\Filesystem\Filesystem;
use AIMuseVendor\Illuminate\Support\Facades\File;
use AIMuseVendor\Illuminate\Support\Facades\Facade;
use AIMuseVendor\Illuminate\View\Compilers\BladeCompiler;
use AIMuseVendor\Illuminate\View\DynamicComponent;
use AIMuseVendor\Illuminate\View\Engines\CompilerEngine;
use AIMuseVendor\Illuminate\View\Engines\EngineResolver;
use AIMuseVendor\Illuminate\View\Factory;
use AIMuseVendor\Illuminate\View\FileViewFinder;
use AIMuseVendor\Monolog\Formatter\JsonFormatter;
use AIMuseVendor\Illuminate\Contracts\Foundation\Application as ApplicationContract;
use AIMuseVendor\Illuminate\Contracts\View\Factory as ViewFactoryContract;

class App extends Container
{
  public static string $name = AI_MUSE_NAME;
  public static string $version = AI_MUSE_VERSION;
  public static string $file = AI_MUSE_FILE;
  public static string $dir = AI_MUSE_DIR;
  public static string $prefix = AI_MUSE_PREFIX;
  public static string $url = AI_MUSE_URL;
  public static string $freemiusId = '14580';
  protected static $test = "test";

  public function register()
  {

    $this->singleton('app', function () {
      return $this;
    });

    $this->singleton(ApplicationContract::class, 'app');

    $this->singleton(Database::class, function ($app) {
      return new Database($app);
    });

    $this->registerView();

    $this->singleton('log', function () {
      $file = $this->logPath();

      if (!File::exists($file)) {
        File::put($file, '');
      }

      $logger = new Logger('aimuse');

      $levels = [
        'debug' => Logger::DEBUG,
        'info' => Logger::INFO,
        'notice' => Logger::NOTICE,
        'warning' => Logger::WARNING,
        'error' => Logger::ERROR,
      ];

      $settings = get_option('aimuse_logs', [
        'level' => 'info',
      ]);

      $level = $levels[$settings['level']];

      $handler = new StreamHandler($file, $level);
      $handler->setFormatter(new JsonFormatter());
      $logger->pushHandler($handler);

      return $logger;
    });

    $this->singleton('api', function () {
      return new Client();
    });

    $this->singleton('freemius', function () {
      require_once aimuse()->dir() . 'freemius/start.php';

      return fs_dynamic_init([
        'id' => $this->freemiusId(),
        'slug' => $this->name(),
        'type' => 'plugin',
        'public_key' => 'pk_0226a176e65a3a9ce40197f7f3604',
        'is_premium' => false,
        'has_premium_version' => false,
        'has_paid_plans' => true,
        'is_org_compliant' => true,
        'menu' => array(
          'slug' => $this->name(),
          'support' => false,
          'contact' => false,
          'first-path' => "admin.php?page={$this->name()}#/settings/general",
        ),
      ]);
    });

    $this->singleton('wp_filesystem', function () {
      if (!function_exists('WP_Filesystem')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        WP_Filesystem();

        global $wp_filesystem;

        return $wp_filesystem;
      }
    });

    Facade::setFacadeApplication($this);
  }

  public function getNamespace()
  {
    return 'aimuse';
  }

  private function registerView()
  {
    $this->bind('files', fn() => new Filesystem());
    $this->bind('events', fn() => new Dispatcher());

    $this->bind('view.finder', function ($app) {
      return new FileViewFinder($app['files'], [
        aimuse()->dir() . 'views'
      ]);
    });

    $this->singleton('blade.compiler', function ($app) {
      $blade = new BladeCompiler($app['files'], WP_CONTENT_DIR . '/uploads/aimuse/cache/views');

      $blade->component('dynamic-component', DynamicComponent::class);

      return $blade;
    });

    $this->singleton('view.engine.resolver', function () {
      $resolver = new EngineResolver();

      $resolver->register('blade', function () {
        $compiler = new CompilerEngine(
          $this->make('blade.compiler'),
          $this->make('files'),
        );

        return $compiler;
      });

      return $resolver;
    });

    $this->singleton('view', function ($app) {
      $resolver = $app['view.engine.resolver'];
      $finder = $app['view.finder'];
      $factory = new Factory($resolver, $finder, $app['events']);
      $factory->setContainer($app);
      $factory->share('app', $app);

      return $factory;
    });

    $this->singleton(ViewFactoryContract::class, function ($app) {
      return $app['view'];
    });
  }

  public function logPath($date = null)
  {
    $logsDir = WP_CONTENT_DIR . '/uploads/aimuse/logs';

    if (!File::exists($logsDir)) {
      File::makeDirectory($logsDir, 0755, true);
    }

    $blogName = 'blog-' . get_current_blog_id();
    $date ??= date('Y-m-d');
    $hash = hash_hmac('sha1', $blogName . $date, wp_salt());

    $file = $logsDir . "/{$blogName}-{$date}-{$hash}.log";

    return $file;
  }

  public function run()
  {
    $this->make(Database::class);
    ShortCodes::register();
    Hooks::register();
    Schedules::init();

    aimuse()->freemius();
  }

  /**
   * Retrieves the view instance.
   *
   * @return \AIMuseVendor\Illuminate\View\Factory The view instance.
   */
  public function view()
  {
    return $this->get('view');
  }

  public function define($key, $value)
  {
    $this->offsetSet($key, $value);
  }

  public function filesystem(): WP_Filesystem_Direct
  {
    return $this->get('wp_filesystem');
  }

  public function db(): Database
  {
    return $this->make(Database::class);
  }

  public function install()
  {
    $this->db()->install();
    Settings::set('version', $this->version());
    Settings::set('isStreamChecked', !function_exists('openssl_encrypt'));
    Settings::set('isStreamAvailable', true);
    $this->api()->install();
    Patches::apply();
    Schedules::register();
  }

  public function uninstall()
  {
    $this->db()->uninstall();
    Schedules::unregister();
    (new LogController())->clear();
    delete_option('aimuse_logs');
    File::deleteDirectory(WP_CONTENT_DIR . '/uploads/aimuse');
  }

  public function installed()
  {
    if (!$this->db()->installed()) {
      return false;
    }

    if (version_compare(Settings::get('version', null), $this->version(), '!=')) {
      return false;
    }

    return true;
  }

  public function api(): Client
  {
    return $this->get('api');
  }

  /**
   * Freemius instance for the plugin.
   *
   * @return \Freemius
   */
  public function freemius()
  {
    return $this->get('freemius');
  }

  /**
   * Retrieves the Freemius ID.
   *
   * This function returns the Freemius ID associated with the plugin.
   *
   * @return string The Freemius ID.
   */
  public function freemiusId()
  {
    return static::$freemiusId;
  }

  public function name()
  {
    return static::$name;
  }

  public function version()
  {
    return static::$version;
  }

  /**
   * Retrieves the plugin file path.
   *
   * @return string The plugin file.
   */
  public function file()
  {
    return static::$file;
  }

  public function dir()
  {
    return static::$dir;
  }

  public function prefix()
  {
    return static::$prefix;
  }

  /**
   * Retrieves the plugin URL.
   *
   * @param string $path The path to append to the URL.
   * @return string The plugin URL.
   */
  public function url(string $path = '')
  {
    return static::$url . $path;
  }

  public function menu(string $path = '')
  {
    return admin_url('admin.php?page=' . $this->name() . $path);
  }
}
