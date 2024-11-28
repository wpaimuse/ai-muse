<?php

namespace AIMuseVendor\Illuminate\Http\Exceptions;

use RuntimeException;
use AIMuseVendor\Symfony\Component\HttpFoundation\Response;

class HttpResponseException extends RuntimeException
{
    /**
     * The underlying response instance.
     *
     * @var \AIMuseVendor\Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * Create a new HTTP response exception instance.
     *
     * @param  \AIMuseVendor\Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \AIMuseVendor\Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
