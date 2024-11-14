<?php

namespace Revolution\Bluesky\Contracts;

use Illuminate\Http\Client\PendingRequest;
use Psr\Http\Message\StreamInterface;

interface XrpcClient
{
    public function withHttp(PendingRequest $http): static;

    public function withBody(StreamInterface|string $content, string $contentType = 'image/png'): static;
}
