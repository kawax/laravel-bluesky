<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Revolution\Bluesky\Core\CBOR\BytesWrapper;

class AtBytes implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): BytesWrapper
    {
        return new BytesWrapper($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $value instanceof BytesWrapper ? $value->bytes() : $value;
    }
}
