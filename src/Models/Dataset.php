<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;
use AIMuseVendor\Illuminate\Support\Facades\File;

class Dataset extends Model
{
  protected $table = 'aimuse_datasets';
  protected $guarded = [];
  protected static $files = [];
  public static $datasetsDir = WP_CONTENT_DIR . "/uploads/aimuse/datasets";
  public $appends = ['hash'];

  public function conversations()
  {
    return $this->hasMany(DatasetConversation::class, 'dataset_id');
  }

  public function getHashAttribute()
  {
    return hash_hmac('sha1', $this->id . $this->slug, wp_salt());
  }

  public function getBackupFileName()
  {
    $hash = $this->hash;
    return "dataset-{$hash}";
  }

  public function getBackupFilePath(string $type)
  {
    $fileName = $this->getBackupFileName();
    return static::$datasetsDir . "/$fileName.$type";
  }

  public function export(int $limit, int $offset, string $type)
  {
    $fileName = $this->getBackupFileName();
    $datasetsDir = static::$datasetsDir;
    $filePath = "$datasetsDir/$fileName.$type";
    $fileUrl = content_url("/uploads/aimuse/datasets/$fileName.$type");

    if (!File::exists($datasetsDir)) {
      try {
        File::makeDirectory($datasetsDir, 0755, true);
      } catch (\Exception $e) {
        throw new \Exception("Failed to create directory: $datasetsDir");
      }
    }

    if (!File::exists($filePath) || $offset == 0) {
      $header = $type === 'csv' ? '"Prompt","Response"' . "\n" : '';
      try {
        File::put($filePath, $header);
      } catch (\Exception $e) {
        throw new \Exception("Failed to create file: $filePath");
      }
    }

    $conversations = $this->conversations()->select(['prompt', 'response'])->limit($limit)->offset($offset)->get();
    $content = '';

    foreach ($conversations as $conversation) {
      if ($type === 'csv') {
        $content .= $conversation->toCsv();
      } elseif ($type === 'jsonl') {
        $content .= $conversation->toJsonLine();
      }
    }

    try {
      File::append($filePath, $content);
    } catch (\Exception $e) {
      throw new \Exception("Failed to write to file: $filePath");
    }

    return $fileUrl;
  }

  // Get all the dataset files from plugin directory
  public static function getFiles(): array
  {
    if (static::$files) {
      return static::$files;
    }

    $files = File::allFiles(aimuse()->dir() . 'database/datasets');
    $data = [];

    foreach ($files as $file) {
      if ($file->getExtension() != 'json') {
        continue;
      }

      $data[] = $file;
    }

    return $data;
  }
}
