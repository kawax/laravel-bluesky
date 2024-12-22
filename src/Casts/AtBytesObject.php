<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Revolution\Bluesky\Core\CBOR\AtBytes;

class AtBytesObject implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): AtBytes
    {
        return new AtBytes($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value instanceof AtBytes ? $value->toBytes() : $value;
    }
}
