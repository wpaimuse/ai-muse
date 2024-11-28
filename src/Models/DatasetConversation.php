<?php

namespace AIMuse\Models;

use AIMuse\Database\Model;

class DatasetConversation extends Model
{
  protected $table = 'aimuse_dataset_conversations';
  protected $guarded = [];

  public function dataset()
  {
    return $this->belongsTo(Dataset::class, 'dataset_id');
  }

  public function toJsonLine(): string
  {
    $row = [
      'messages' => [
        ['role' => 'user', 'content' => $this->prompt],
        ['role' => 'assistant', 'content' => $this->response]
      ]
    ];

    return json_encode($row) . PHP_EOL;
  }

  public function toCsv(): string
  {
    $this->prompt = str_replace('"', '""', $this->prompt);
    $this->response = str_replace('"', '""', $this->response);

    return '"' . $this->prompt . '","' . $this->response . '"' . PHP_EOL;
  }

  public static function fromJsonLine(string $line): self
  {
    $row = json_decode($line, true);

    $conversation = new self();
    $conversation->prompt = $row['messages'][0]['content'];
    $conversation->response = $row['messages'][1]['content'];

    return $conversation;
  }

  protected static function booted()
  {
    static::creating(function ($conversation) {
      $conversation->character_count = strlen($conversation->prompt) + strlen($conversation->response);
    });

    static::updating(function ($conversation) {
      $conversation->character_count = strlen($conversation->prompt) + strlen($conversation->response);
    });

    static::deleted(function ($conversation) {
      $conversation->dataset->decrement('character_count', $conversation->character_count);
    });

    static::created(function ($conversation) {
      $conversation->dataset->increment('character_count', $conversation->character_count);
    });

    static::updated(function ($conversation) {
      $conversation->dataset->increment('character_count', $conversation->character_count - $conversation->getOriginal('character_count'));
    });
  }
}
