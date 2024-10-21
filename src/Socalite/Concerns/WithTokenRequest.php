<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Socalite\DPoP;

trait WithTokenRequest
{
    protected function sendTokenRequest(string $token_url, array $payload): array
    {
        return Http::withRequestMiddleware(
            function (RequestInterface $request) use ($token_url) {
                $dpop_nonce = $this->getOAuthSession()->get(DPoP::AUTH_NONCE, '');

                $dpop_proof = DPop::authProof(
                    jwk: DPoP::load(),
                    url: $token_url,
                    nonce: $dpop_nonce,
                );

                return $request->withHeader('DPoP', $dpop_proof);
            })->withResponseMiddleware(
            function (ResponseInterface $response) {
                $dpop_nonce = collect($response->getHeader('DPoP-Nonce'))->first();

                $this->getOAuthSession()->put(DPoP::AUTH_NONCE, $dpop_nonce);

                $sub = (new Response($response))->json('sub');
                if (! empty($sub)) {
                    $this->getOAuthSession()->put('sub', $sub);
                }

                $this->getOAuthSession()->put('token_created_at', now()->toISOString());

                return $response;
            })
            ->retry(times: 2, throw: false)
            ->throw()
            ->post($token_url, $payload)
            ->json();
    }
}
