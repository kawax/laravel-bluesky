<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use ReflectionMethod;
use ReflectionParameter;
use Revolution\Bluesky\Lexicon\Enum\AtProto;
use Revolution\Bluesky\Lexicon\Enum\Bsky;

use function Illuminate\Support\enum_value;

trait HasHttp
{
    protected const post = 'post';
    protected const get = 'get';

    protected PendingRequest $http;

    protected function call(AtProto|Bsky|string $api, string $method = 'get', ?array $params = null): Response
    {
        $params = collect($params)
            ->reject(fn ($param) => is_null($param))
            ->toArray();

        return $this->http()->$method(enum_value($api), $params);
    }

    protected function params(string $method): array
    {
        $ref = new ReflectionMethod($method);

        return collect($ref->getParameters())
            ->map(fn (ReflectionParameter $param) => $param->getName())
            ->toArray();
    }

    protected function http(): PendingRequest
    {
        return $this->http;
    }

    public function withHttp(PendingRequest $http): static
    {
        $this->http = $http;

        return $this;
    }
}
