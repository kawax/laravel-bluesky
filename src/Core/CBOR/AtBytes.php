<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\CBOR;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * @link https://github.com/mary-ext/atcute/blob/trunk/packages/utilities/cbor/lib/bytes.ts
 */
final readonly class AtBytes implements Arrayable, Jsonable
{
    public function __construct(protected string $bytes)
    {
    }

    /**
     * ```
     * $bytes = AtBytes::fromArray(['$bytes' => 'base64'])->toBytes();
     * ```
     */
    public static function fromArray(array $array): self
    {
        return new self(base64_decode($array['$bytes']));
    }

    public function toBytes(): string
    {
        return $this->bytes;
    }

    public function toArray(): array
    {
        return ['$bytes' => $this->encode()];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    protected function encode(): string
    {
        return base64_encode($this->bytes);
    }
}
