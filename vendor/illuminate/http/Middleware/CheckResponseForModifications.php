<?php

namespace AIMuseVendor\Illuminate\Http\Middleware;

use Closure;
use AIMuseVendor\Symfony\Component\HttpFoundation\Response;

class CheckResponseForModifications
{
    /**
     * Handle an incoming request.
     *
     * @param  \AIMuseVendor\Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof Response) {
            $response->isNotModified($request);
        }

        return $response;
    }
}
