<?php

namespace AIMuse\Contracts;

use AIMuseVendor\Psr\Http\Message\StreamInterface;

interface Transporter
{
  public function get(string $endpoint);

  public function post(string $path, array $body);

  public function delete(string $endpoint);

  public function stream(string $path, array $body): StreamInterface;

  public function request(string $method, string $endpoint, array $options = []);
}
