<?php

namespace AIMuseVendor\Illuminate\Database\Eloquent\Casts;

use AIMuseVendor\Illuminate\Contracts\Database\Eloquent\Castable;
use AIMuseVendor\Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use AIMuseVendor\Illuminate\Support\Collection;

class AsCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return object|string
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                return isset($attributes[$key]) ? new Collection(json_decode($attributes[$key], true)) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => json_encode($value)];
            }
        };
    }
}
