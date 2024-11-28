<?php

namespace AIMuseVendor\Illuminate\Http\Client\Events;

use AIMuseVendor\Illuminate\Http\Client\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     *
     * @var \AIMuseVendor\Illuminate\Http\Client\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \AIMuseVendor\Illuminate\Http\Client\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
