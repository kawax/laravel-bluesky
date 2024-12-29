<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Session;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Revolution\Bluesky\Crypto\JsonWebToken;
use Revolution\Bluesky\Support\DidDocument;

abstract class AbstractSession implements Arrayable
{
    use Tappable;
    use Macroable;
    use Conditionable;

    protected Collection $session;

    final public function __construct(array|Collection|AbstractSession|null $session = null)
    {
        $this->session = $session instanceof AbstractSession ? $session->collect() : Collection::wrap($session);
    }

    public static function create(array|Collection|AbstractSession|null $session = null): static
    {
        return new static($session);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->session, $key, $default);
    }

    public function put(string $key, mixed $value): static
    {
        $this->session->put($key, $value);

        return $this;
    }

    public function forget(string|array $keys): static
    {
        $this->session->forget($keys);

        return $this;
    }

    public function has(string|array $key): bool
    {
        return $this->session->has($key);
    }

    public function merge(array|Collection $items): static
    {
        $this->session = $this->session->merge($items);

        return $this;
    }

    public function except(string|array $keys): static
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
        return DidDocument::make($this->get('didDoc', $this->session));
    }

    public function handle(string $default = ''): string
    {
        return $this->get('handle', $default);
    }

    public function token(string $default = ''): string
    {
        return $this->get('access_token', $default);
    }

    public function tokenExpired(): bool
    {
        $token = $this->token();
        if (empty($token)) {
            return true;
        }

        [, $payload] = JsonWebToken::explode($this->token());
        $exp = data_get($payload, 'exp');
        if (empty($exp)) {
            return true;
        }

        return now()->greaterThan(Carbon::createFromTimestamp($exp));
    }

    public function refresh(string $default = ''): string
    {
        return $this->get('refresh_token', $default);
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
