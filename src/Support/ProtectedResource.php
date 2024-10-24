<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

final class ProtectedResource implements Arrayable
{
    protected Collection $meta;

    public function __construct(array|Collection|null $meta = null)
    {
        $this->meta = Collection::wrap($meta)
            ->only([
                'resource',
                'authorization_servers',
                'scopes_supported',
                'bearer_methods_supported',
                'resource_documentation',
            ]);
    }

    public static function create(array|Collection|null $meta = null): self
    {
        return new self($meta);
    }

    public function resource(): ?string
    {
        return $this->get('resource');
    }

    public function authServer(?string $default = null): ?string
    {
        return $this->get('authorization_servers.{first}', $default);
    }

    public function get(string $key, ?string $default = null): mixed
    {
        return data_get($this->meta, $key, $default);
    }

    public function toArray(): array
    {
        return $this->meta->toArray();
    }
}
