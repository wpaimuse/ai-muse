<?php

namespace AIMuseVendor\Illuminate\Http\Client\Events;

use AIMuseVendor\Illuminate\Http\Client\Request;
use AIMuseVendor\Illuminate\Http\Client\Response;

class ResponseReceived
{
    /**
     * The request instance.
     *
     * @var \AIMuseVendor\Illuminate\Http\Client\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \AIMuseVendor\Illuminate\Http\Client\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \AIMuseVendor\Illuminate\Http\Client\Request  $request
     * @param  \AIMuseVendor\Illuminate\Http\Client\Response  $response
     * @return void
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
