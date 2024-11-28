<?php

namespace AIMuseVendor\Illuminate\Contracts\Http;

interface Kernel
{
    /**
     * Bootstrap the application for HTTP requests.
     *
     * @return void
     */
    public function bootstrap();

    /**
     * Handle an incoming HTTP request.
     *
     * @param  \AIMuseVendor\Symfony\Component\HttpFoundation\Request  $request
     * @return \AIMuseVendor\Symfony\Component\HttpFoundation\Response
     */
    public function handle($request);

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \AIMuseVendor\Symfony\Component\HttpFoundation\Request  $request
     * @param  \AIMuseVendor\Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response);

    /**
     * Get the Laravel application instance.
     *
     * @return \AIMuseVendor\Illuminate\Contracts\Foundation\Application
     */
    public function getApplication();
}
