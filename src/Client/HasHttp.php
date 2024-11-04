<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Http\Client\PendingRequest;

trait HasHttp
{
    protected PendingRequest $http;

    public function withHttp(PendingRequest $http): static
    {
        $this->http = $http;

        return $this;
    }

    public function http(): PendingRequest
    {
        return $this->http;
    }
}
