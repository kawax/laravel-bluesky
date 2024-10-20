<?php

namespace Revolution\Bluesky\Session;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

abstract class AbstractSession implements Arrayable
{
    protected Collection $session;

    public function __construct(array|Collection|null $session = null)
    {
        $this->session = Collection::wrap($session);
    }

    public static function create(array|Collection|null $session = null): static
    {
        return new static(Collection::wrap($session));
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->session->dot()->get($key, $default);
    }

    public function put(string $key, $value): static
    {
        $this->session->put($key, $value);

        return $this;
    }

    public function merge($items): static
    {
        $this->session = $this->session->merge($items);

        return $this;
    }

    public function did(): string
    {
        return $this->get('did', '');
    }

    public function handle(): string
    {
        return $this->get('handle', '');
    }

    public function issuer(): string
    {
        return $this->get('iss', '');
    }

    public function collect(): Collection
    {
        return $this->session;
    }

    public function toArray(): array
    {
        return $this->session->toArray();
    }
}
