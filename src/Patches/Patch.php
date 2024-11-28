<?php

namespace AIMuse\Patches;

class Patch
{
  /**
   * The version this patch applies to.
   *
   * @var string
   */
  public string $version;

  public function __construct()
  {
    if (!isset($this->version)) {
      throw new \Exception('Version not set');
    }
  }

  public function apply()
  {
    throw new \Exception('Not implemented');
  }

  public function shouldApply(): bool
  {
    return version_compare(aimuse()->version(), $this->version, '>=');
  }
}
