<?php

namespace AIMuse\Patches;

use AIMuse\Models\History;

class HistoryTypeFixPatch extends Patch
{
  public string $version = "1.2.2";

  public function apply()
  {
    History::query()->where('model', 'LIKE', 'dall-e%')->update(['model_type' => 'image']);
  }
}
