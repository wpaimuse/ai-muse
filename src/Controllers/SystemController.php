<?php

namespace AIMuse\Controllers;

use WP_REST_Response;
use AIMuse\Models\AIModel;
use AIMuse\Models\Settings;
use AIMuse\Models\Template;
use AIMuse\Attributes\Route;
use AIMuse\Middleware\AdminAuth;
use AIMuse\Validators\Validator;
use AIMuse\Controllers\Controller;
use AIMuse\Helpers\ResponseHelper;
use AIMuse\Exceptions\ControllerException;
use AIMuseVendor\Symfony\Component\Validator\Constraints;

class SystemController extends Controller
{
  public array $middlewares = [
    AdminAuth::class,
  ];

  /**
   * @Route(path="/admin/system/clear", method="POST")
   */
  public function clear()
  {
    aimuse()->uninstall();
    deactivate_plugins([aimuse()->file()]);

    return new WP_REST_Response([
      'message' => 'Plugin cleared successfully',
      'redirect' => admin_url('plugins.php')
    ], 200);
  }

  /**
   * @Route(path="/admin/system/reset", method="POST")
   */
  public function reset()
  {
    Settings::clearCache();
    aimuse()->uninstall();
    aimuse()->install();
    Settings::set('acceptedTerms', true);

    return new WP_REST_Response([
      'message' => 'Plugin reset successfully',
    ], 200);
  }

  /**
   * @Route(path="/admin/system/database/columns", method="GET")
   */
  public function features()
  {
    $features = aimuse()->db()->features();
    return new WP_REST_Response(array_keys($features), 200);
  }

  /**
   * @Route(path="/admin/system/database/reset", method="POST")
   */
  public function featuresReset(Request $request)
  {
    $violations = $request->validate([
      'columns' => new Constraints\All([
        new Constraints\NotBlank(),
        new Constraints\Type('string')
      ]),
    ], 'json');

    if ($violations->count() > 0) {
      throw new ControllerException(Validator::toArray($violations), 400);
    }

    $migrator = aimuse()->db()->migrator();
    $features = aimuse()->db()->features();
    $columns = $request->json('columns');

    foreach ($columns as $column) {
      if (isset($features[$column])) {
        $migrator->reset($features[$column]);
      }
    }

    aimuse()->install();

    return new WP_REST_Response([
      'message' => 'Database reset successfully',
    ], 200);
  }

  /**
   * @Route(path="/admin/system/repair", method="POST")
   */
  public function repair()
  {
    aimuse()->install();

    return new WP_REST_Response([
      'message' => 'Plugin repaired successfully',
    ], 200);
  }

  /**
   * @Route(path="/admin/tests/stream", method="GET")
   */
  public function stream()
  {
    ResponseHelper::prepare([
      'forceSSE' => true,
      'channel' => 'test',
    ]);

    for ($i = 0; $i < 3; $i++) {
      ResponseHelper::send('message', 'ok');
      sleep(1);
    }

    ResponseHelper::send('done', true);
  }

  private function getSecretKey()
  {
    return substr(hash('sha256', 'wp-aimuse-backup'), 0, 16);
  }

  /**
   * @Route(path="/admin/system/export", method="POST")
   */
  public function export()
  {
    $this->checkOpenSSL();

    $backup = [
      'settings' => Settings::export(),
      'models' => AIModel::export(),
      'templates' => Template::export(),
    ];

    $file = wp_json_encode($backup);
    $key = $this->getSecretKey();
    $file = openssl_encrypt($file, 'aes-128-cbc', $key, 0, $key);

    return new WP_REST_Response([
      'message' => 'Backup created successfully',
      'file' => $file,
    ], 200);
  }

  /**
   * @Route(path="/admin/system/import", method="POST")
   */
  public function import(Request $request)
  {
    $this->checkOpenSSL();

    $file = $request->json('file');
    $key = $this->getSecretKey();
    $file = openssl_decrypt($file, 'aes-128-cbc', $key, 0, $key);

    if (!$file) {
      throw ControllerException::make('Invalid backup file', 400);
    }

    $backup = json_decode($file, true);

    if (isset($backup['settings'])) {
      Settings::import($backup['settings']);
    }

    if (isset($backup['models'])) {
      AIModel::import($backup['models']);
    }

    if (isset($backup['templates'])) {
      Template::import($backup['templates']);
    }

    return new WP_REST_Response([
      'message' => 'Settings restored successfully',
      'success' => true,
    ], 200);
  }

  public function checkOpenSSL()
  {
    if (!function_exists('openssl_encrypt')) {
      throw ControllerException::make('PHP OpenSSL extension is not enabled', 400);
    }
  }
}
