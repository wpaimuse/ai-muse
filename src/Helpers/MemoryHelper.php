<?php

namespace AIMuse\Helpers;

class MemoryHelper
{
  public static function getMemoryLimit()
  {
    $memory = ini_get('memory_limit');
    $byte = intval($memory);
    $unit = strtolower($memory[strlen($memory) - 1]);
    switch ($unit) {
      case 'g':
        $byte *= 1024 * 1024 * 1024;
        break;
      case 'm':
        $byte *= 1024 * 1024;
        break;
      case 'k':
        $byte *= 1024;
        break;
    }

    return $byte;
  }

  public static function getUsagePercentage()
  {
    $memory = self::getMemoryLimit();
    $usage = memory_get_usage();
    return round($usage / $memory * 100, 2);
  }
}
