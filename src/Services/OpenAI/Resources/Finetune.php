<?php

namespace AIMuse\Services\OpenAI\Resources;

use AIMuse\Contracts\Transporter;

class Finetune
{
  private Transporter $transporter;

  public function __construct(Transporter $transporter)
  {
    $this->transporter = $transporter;
  }

  public function create(array $options)
  {
    return $this->transporter->post('fine_tuning/jobs', $options);
  }

  public function get(string $id)
  {
    return $this->transporter->get("fine_tuning/jobs/{$id}");
  }

  public function jobs()
  {
    return $this->transporter->get('fine_tuning/jobs');
  }

  public function cancel(string $id)
  {
    return $this->transporter->post("fine_tuning/jobs/{$id}/cancel", []);
  }
}
