<?php

namespace AIMuseVendor\Illuminate\Contracts\Support;

interface Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \AIMuseVendor\Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request);
}
