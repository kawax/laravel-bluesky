<?php

namespace Revolution\Bluesky\Contracts;

use Illuminate\Http\Client\PendingRequest;

interface XrpcClient
{
    public function withHttp(PendingRequest $http): static;

    public function withBody($content, $contentType = 'image/png'): static;
}
