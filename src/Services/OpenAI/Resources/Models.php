<?php

namespace AIMuse\Services\OpenAI\Resources;

use AIMuse\Contracts\Transporter;
use AIMuse\Services\OpenAI\Responses\ModelsResponse;

class Models
{
  private Transporter $transporter;

  public function __construct(Transporter $transporter)
  {
    $this->transporter = $transporter;
  }

  public function get()
  {
    $response = $this->transporter->get("models");
    return ModelsResponse::fromJson($response);
  }

  public function delete($modelId)
  {
    return $this->transporter->delete("models/$modelId");
  }
}
