<?php

namespace AIMuseVendor\Illuminate\Support\Facades;

/**
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse action(string $action, mixed $parameters = [], int $status = 302, array $headers = [])
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse away(string $path, int $status = 302, array $headers = [])
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse back(int $status = 302, array $headers = [], $fallback = false)
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse guest(string $path, int $status = 302, array $headers = [], bool $secure = null)
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse home(int $status = 302)
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse intended(string $default = '/', int $status = 302, array $headers = [], bool $secure = null)
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse refresh(int $status = 302, array $headers = [])
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse route(string $route, mixed $parameters = [], int $status = 302, array $headers = [])
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse secure(string $path, int $status = 302, array $headers = [])
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse signedRoute(string $name, mixed $parameters = [], \DateTimeInterface|\DateInterval|int $expiration = null, int $status = 302, array $headers = [])
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse temporarySignedRoute(string $name, \DateTimeInterface|\DateInterval|int $expiration, mixed $parameters = [], int $status = 302, array $headers = [])
 * @method static \AIMuseVendor\Illuminate\Http\RedirectResponse to(string $path, int $status = 302, array $headers = [], bool $secure = null)
 * @method static \Illuminate\Routing\UrlGenerator getUrlGenerator()
 * @method static void setSession(\AIMuseVendor\Illuminate\Session\Store $session)
 * @method static void setIntendedUrl(string $url)
 *
 * @see \Illuminate\Routing\Redirector
 */
class Redirect extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'redirect';
    }
}
