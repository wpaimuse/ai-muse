<?php

namespace AIMuse;

use AIMuse\Database\Connector;
use AIMuse\Database\Connection;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use AIMuseVendor\Illuminate\Support\Facades\File;
use AIMuseVendor\Illuminate\Filesystem\Filesystem;
use AIMuseVendor\Illuminate\Database\DatabaseManager;
use AIMuse\Database\Seeders\DatabaseSeeder;
use AIMuseVendor\Illuminate\Database\Migrations\Migrator;
use AIMuseVendor\Illuminate\Database\Capsule\Manager as Capsule;
use AIMuseVendor\Illuminate\Database\Migrations\DatabaseMigrationRepository;

class Database
{
  /**
   * The application instance.
   *
   * @var App $app
   */
  private $app;

  public function __construct(App $app)
  {
    $this->app = $app;

    /**
     * @var \wpdb $wpdb
     */
    global $wpdb;

    $this->app->singleton('db.connector.wordpress', function () {
      return new Connector();
    });

    Connection::resolverFor('wordpress', function ($connection, $database, $prefix, $config) {
      return new Connection(
        $connection(),
        $database,
        $prefix,
        $config
      );
    });

    $capsule = new Capsule($this->app);

    $capsule->addConnection([
      'driver' => 'wordpress',
      'charset' => $wpdb->charset,
      'collation' => $wpdb->collate,
      'prefix' => $wpdb->prefix,
      'database' => $wpdb->dbname,
      'strict' => false,
      'timezone' => '+00:00',
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $this->app->singleton('db', function () use ($capsule) {
      return $capsule->getDatabaseManager();
    });

    $this->app->singleton('migration.repository', function () {
      return new DatabaseMigrationRepository($this->app->get('db'), $this->app->prefix() . '_migrations');
    });

    $this->app->singleton('files', function () {
      return new Filesystem();
    });

    $this->app->singleton('migrator', function () {
      return new Migrator($this->app->get('migration.repository'), $this->app->get('db'), $this->app->get('files'));
    });

    $this->app->singleton('seeder', function () {
      $seeder = new DatabaseSeeder();
      $seeder->setContainer($this->app);
      return $seeder;
    });
  }

  public function manager(): DatabaseManager
  {
    return $this->app->make('db');
  }

  public function uninstall()
  {
    try {
      $migrator = $this->migrator();
      $files = $migrator->getMigrationFiles(aimuse()->dir() . '/database/migrations');
      $migrator->reset($files);
      if ($migrator->repositoryExists()) {
        $migrator->getRepository()->deleteRepository();
      }
    } catch (\Throwable $th) {
    }
  }

  /**
   * Get the migrator instance.
   *
   * @return Migrator
   */
  public function migrator()
  {
    return $this->app->make('migrator');
  }

  public function features()
  {
    $migrator = aimuse()->db()->migrator();
    $files = $migrator->getMigrationFiles(aimuse()->dir() . '/database/migrations');
    $features = [];
    foreach ($files as $path) {
      $migration = File::requireOnce($path);
      if (!isset($migration->feature)) continue;
      $features[$migration->feature][] = $path;
    }
    return $features;
  }

  public function install()
  {
    Log::info('Installing database');
    $migrator = $this->migrator();
    if (!$migrator->repositoryExists()) {
      $migrator->getRepository()->createRepository();
    }
    $migrator->run($this->app->dir() . '/database/migrations');

    Log::info('Seeding database');
    /**
     * @var DatabaseSeeder $seeder
     */
    $seeder = $this->app->make('seeder');
    $seeder->run();
  }

  public function installed()
  {
    return $this->app->make('migrator')->repositoryExists();
  }
}
