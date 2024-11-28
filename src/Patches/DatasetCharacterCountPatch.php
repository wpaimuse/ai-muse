<?php

namespace AIMuse\Patches;

use AIMuse\Models\Dataset;
use AIMuseVendor\Illuminate\Support\Facades\DB;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class DatasetCharacterCountPatch extends Patch
{
  public string $version = "1.2.2";

  public function apply()
  {
    $datasets = Dataset::all();
    foreach ($datasets as $dataset) {
      if ($dataset->character_count !== 0) continue;
      Log::info("Updating character count for dataset {$dataset->id}");
      $dataset->conversations()->update([
        'character_count' => DB::raw('LENGTH(prompt) + LENGTH(response)')
      ]);
      $dataset->character_count = $dataset->conversations()->sum('character_count');
      $dataset->save();
    }
  }
}
