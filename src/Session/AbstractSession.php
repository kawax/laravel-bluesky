<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Session;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Revolution\Bluesky\Support\DidDocument;

abstract class AbstractSession implements Arrayable
{
    use Tappable;
    use Macroable;
    use Conditionable;

    protected Collection $session;

    public function __construct(array|Collection|AbstractSession|null $session = null)
    {
        $this->session = $session instanceof AbstractSession ? $session->collect() : Collection::wrap($session);
    }

    public static function create(array|Collection|AbstractSession|null $session = null): static
    {
        return new static($session);
    }

    public function get(string $key, $default = null): mixed
    {
        return data_get($this->session, $key, $default);
    }

    public function put(string $key, $value): static
    {
        $this->session->put($key, $value);

        return $this;
    }

    public function forget($keys): static
    {
        $this->session->forget($keys);

        return $this;
    }

    public function has($key): bool
    {
        return $this->session->has($key);
    }

    public function merge($items): static
    {
        $this->session = $this->session->merge($items);

        return $this;
    }

    public function except($keys): static
    {
        $this->session = $this->session->except($keys);

        return $this;
    }

    public function did(string $default = ''): string
    {
        return $this->get('did', $default);
    }

    public function didDoc(): DidDocument
    {
        return DidDocument::create($this->get('didDoc', $this->session));
    }

    public function handle(string $default = ''): string
    {
        return $this->get('handle', $default);
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
