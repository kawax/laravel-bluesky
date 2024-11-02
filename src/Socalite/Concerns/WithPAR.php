<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Events\DPoPNonceReceived;
use Revolution\Bluesky\Socalite\Key\DPoP;

/**
 * Pushed Authentication Request
 */
trait WithPAR
{
    protected function getParRequestUrl(string $state): string
    {
        $par_data = $this->parRequestFields($state);

        return $this->sendParRequest($par_data)
            ->json('request_uri', '');
    }

    protected function sendParRequest(array $par_data): Response
    {
        $par_url = $this->authServerMeta('pushed_authorization_request_endpoint');

        return Http::asForm()
            ->withRequestMiddleware($this->parRequestMiddleware(...))
            ->withResponseMiddleware($this->parResponseMiddleware(...))
            ->retry(times: 2, throw: false)
            ->throw()
            ->post($par_url, $par_data);
    }

    protected function parRequestFields($state): array
    {
        // Special exception for development only.
        if ($this->clientId === 'http://localhost') {
            $this->scopes = ['atproto'];
        }

        return [
            'response_type' => 'code',
            'code_challenge' => $this->getCodeChallenge(),
            'code_challenge_method' => $this->getCodeChallengeMethod(),
            'client_id' => $this->clientId,
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'client_assertion_type' => self::CLIENT_ASSERTION_TYPE,
            'client_assertion' => $this->getClientAssertion($this->authUrl()),
            'login_hint' => $this->login_hint,
        ];
    }

    protected function parRequestMiddleware(RequestInterface $request): RequestInterface
    {
        $dpop_nonce = $this->getOAuthSession()->get(DPoP::AUTH_NONCE, '');

        $dpop_proof = DPoP::authProof(
            jwk: DPoP::load(),
            url: (string) $request->getUri(),
            nonce: $dpop_nonce,
        );

        return $request->withHeader('DPoP', $dpop_proof);
    }

    protected function parResponseMiddleware(ResponseInterface $response): ResponseInterface
    {
        $dpop_nonce = (string) collect($response->getHeader('DPoP-Nonce'))->first();

        $this->getOAuthSession()->put(DPoP::AUTH_NONCE, $dpop_nonce);

        return $response;
    }
}
