<?php

namespace Xoshbin\JmeryarAccounting\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get($model, string $key, $value, array $attributes): float
    {
        // Transform the integer stored in the database into a float.
        return round(floatval($value) / 100, precision: 2);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, $value, array $attributes): float
    {
        // Transform the float into an integer for storage.
        return round(floatval($value) * 100);
    }
}
