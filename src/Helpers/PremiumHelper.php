<?php

namespace AIMuse\Helpers;

use AIMuse\Exceptions\ControllerException;
use AIMuse\Models\AIModel;

class PremiumHelper
{
  public static array $premiumServices = [];

  public static array $freePostTypes = [
    'post',
    'page',
  ];

  public static function getPremiumApiKeyNames()
  {
    return array_map(function ($service) {
      return AIModel::$keyNames[$service];
    }, self::$premiumServices);
  }

  public static function serviceIsPremium(string $service)
  {
    return in_array($service, self::$premiumServices);
  }

  public static function isPremium()
  {
    return !aimuse()->freemius()->is_free_plan();
  }

  public static function toArray()
  {
    $freemius = aimuse()->freemius();
    $premium = array(
      'is_free' => $freemius->is_free_plan(),
    );

    return $premium;
  }

  public static function validateModel(AIModel $model)
  {
    self::validateService($model->service);
  }

  public static function validateService(string $service)
  {
    if (!PremiumHelper::isPremium()) {
      if (in_array($service, self::$premiumServices)) {
        throw ControllerException::make("You need to upgrade to premium to use {$service} service", 400);
      }
    }
  }
}
