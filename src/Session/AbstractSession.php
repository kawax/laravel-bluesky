<?php

namespace Revolution\Bluesky\Session;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use Revolution\Bluesky\Support\DidDocument;

abstract class AbstractSession implements Arrayable
{
    use ForwardsCalls;

    protected Collection $session;

    public function __construct(array|Collection|null $session = null)
    {
        $this->session = Collection::wrap($session);
    }

    public static function create(array|Collection|null $session = null): static
    {
        return new static($session);
    }

    public function get(string $key, $default = null): mixed
    {
        return data_get($this->session, $key, $default);
    }

    public function put(string $key, $value): static
    {
        $this->session = $this->session->put($key, $value);

        return $this;
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

    public function did(): ?string
    {
        return $this->get('did');
    }

    public function didDoc(): DidDocument
    {
        return DidDocument::create($this->get('didDoc'));
    }

    public function handle(): ?string
    {
        return $this->get('handle');
    }

    public function issuer(?string $default = null): string
    {
        return $this->session->only(['iss', 'issuer'])->first(default: $default);
    }

    public function collect(): Collection
    {
        return $this->session;
    }

    public function toArray(): array
    {
        return $this->session->toArray();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->forwardCallTo($this->session, $name, $arguments);
    }
}
