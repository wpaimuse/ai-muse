<?php

namespace AIMuse;

use AIMuse\Exceptions\PatchException;
use AIMuse\Patches\Patch;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class Patches
{
  public static function apply()
  {
    $patches = self::list();

    foreach ($patches as $patch) {
      $patchClass = get_class($patch);
      Log::debug("Checking if patch $patchClass should be applied");
      if ($patch->shouldApply()) {
        Log::info("Applying patch $patchClass");
        $patch->apply();
      }
    }
  }

  /**
   * Returns a list of patches.
   *
   * @return Patch[] The list of patches.
   */
  public static function list()
  {
    $patches = [];

    $directory = new \RecursiveDirectoryIterator(aimuse()->dir() . 'src/Patches');
    $iterator = new \RecursiveIteratorIterator($directory);

    /**
     * @var \SplFileInfo $file
     */
    foreach ($iterator as $file) {
      if ($file->getFilename() == 'Patch.php') {
        continue;
      }

      if (
        !str_ends_with($file->getFilename(), 'Patch.php')
      ) {
        continue;
      }

      $relativePath = ltrim($file->getPath(), aimuse()->dir() . 'src/');
      $relativePath = str_replace('/', '\\', $relativePath);

      if ($file->isFile()) {
        $patches[] = 'AIMuse\\' . $relativePath . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
      }
    }

    foreach ($patches as &$patch) {
      if (!class_exists($patch)) {
        throw new PatchException("Patch class $patch does not exist.");
      }

      if (!is_subclass_of($patch, Patch::class)) {
        throw new PatchException('Patch must be a subclass of Patch');
      }

      $patch = new $patch();
    }

    /**
     * @var Patch[] $patches
     */
    usort($patches, function ($a, $b) {
      $isBigger = version_compare($a->version, $b->version, '>');

      return $isBigger ? 1 : -1;
    });

    return $patches;
  }
}
