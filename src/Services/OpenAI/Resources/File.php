<?php

namespace AIMuse\Services\OpenAI\Resources;

use AIMuse\Contracts\Transporter;
use Closure;
use Exception;
use AIMuseVendor\GuzzleHttp\Exception\ClientException;

class File
{
  private Transporter $transporter;
  public string $path;
  public string $purpose;
  public Closure $callback;

  public function __construct(Transporter $transporter)
  {
    $this->transporter = $transporter;
  }

  public function callback($event, $data)
  {
    if (!$this->callback) return;
    return call_user_func($this->callback, $event, $data);
  }

  public function create()
  {
    $file = fopen($this->path, 'r');

    $options = [
      'multipart' => [
        [
          'name' => 'purpose',
          'contents' => $this->purpose,
        ],
        [
          'name' => 'file',
          'contents' => $file,
        ]
      ],
      'progress' => function (
        $downloadTotal,
        $downloadedBytes,
        $uploadTotal,
        $uploadedBytes
      ) {
        $this->callback('progress', [
          'downloadTotal' => $downloadTotal,
          'downloadedBytes' => $downloadedBytes,
          'uploadTotal' => $uploadTotal,
          'uploadedBytes' => $uploadedBytes,
        ]);
      },
    ];

    $response = $this->transporter->request('POST', 'files', $options);

    $this->callback('done', $response);
  }

  public function list(string $purpose = null)
  {
    $options = [];
    if ($purpose) {
      $options['query'] = [
        'purpose' => $purpose,
      ];
    }

    return $this->transporter->request('GET', 'files', $options);
  }

  public function delete(string $id)
  {
    return $this->transporter->request('DELETE', "files/$id");
  }
}
