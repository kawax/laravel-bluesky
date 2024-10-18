<?php

namespace Revolution\Bluesky\Session;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

abstract class AbstractSession implements Arrayable
{
    protected Collection $session;

    public function __construct(array|Collection $session)
    {
        $this->session = Collection::wrap($session);
    }

    public static function create(array|Collection $session): static
    {
        return new static(Collection::wrap($session));
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    public function did(): mixed
    {
        return $this->get('did', '');
    }

    public function toArray(): array
    {
        return $this->session->toArray();
    }
}
