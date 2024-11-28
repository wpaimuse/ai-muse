<?php

namespace AIMuse\Exceptions;

use Exception;
use AIMuseVendor\Illuminate\Support\Facades\Log;

class ControllerException extends Exception
{
  public array $errors = [];

  public function __construct(array $errors, int $code)
  {
    $this->errors = $errors;
    $this->code = $code;
  }

  public function getErrors()
  {
    return $this->errors;
  }

  public static function make(string $message, int $code)
  {
    return new self([
      [
        'message' => $message,
      ]
    ], $code);
  }

  public function log(string $message, array $context = [])
  {
    $context = array_merge($context, [
      'errors' => $this->errors,
      'trace' => $this->getTrace(),
    ]);

    Log::error($message, $context);
  }
}
