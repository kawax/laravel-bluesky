<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Events\DPoPNonceReceived;
use Revolution\Bluesky\Events\RefreshTokenReplayed;
use Revolution\Bluesky\Socalite\Key\DPoP;

trait WithTokenRequest
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     * @throws AuthenticationException
     */
    protected function sendTokenRequest(string $token_url, array $payload): array
    {
        $response = Http::retry(times: 2, throw: false)
            ->withRequestMiddleware($this->tokenRequestMiddleware(...))
            ->withResponseMiddleware($this->tokenResponseMiddleware(...))
            ->post($token_url, $payload);

        // "refresh token replayed" error
        if ($response->clientError()) {
            if ($response->status() === 400 && $response->json('error') === 'invalid_grant') {
                RefreshTokenReplayed::dispatch(
                    $this->getOAuthSession(),
                    $response,
                );

                throw new AuthenticationException();
            }
        }

        $response->throwIf($response->serverError());

        return $response->json();
    }

    protected function tokenRequestMiddleware(RequestInterface $request): RequestInterface
    {
        $dpop_nonce = $this->getOAuthSession()->get(DPoP::AUTH_NONCE, '');

        $dpop_proof = DPoP::authProof(
            jwk: DPoP::load(),
            url: (string) $request->getUri(),
            nonce: $dpop_nonce,
        );

        return $request->withHeader('DPoP', $dpop_proof);
    }

    protected function tokenResponseMiddleware(ResponseInterface $response): ResponseInterface
    {
        $dpop_nonce = (string) collect($response->getHeader('DPoP-Nonce'))->first();

        $this->getOAuthSession()->put(DPoP::AUTH_NONCE, $dpop_nonce);

        $sub = (new Response($response))->json('sub');
        if (filled($sub)) {
            $this->getOAuthSession()->put('sub', $sub);
        }

        $this->getOAuthSession()->put('token_created_at', now()->toISOString());

        DPoPNonceReceived::dispatch($dpop_nonce, $this->getOAuthSession());

        return $response;
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     * @throws RequestException
     * @throws ConnectionException
     * @throws AuthenticationException
     */
    public function getAccessTokenResponse($code): array
    {
        $token_url = $this->getTokenUrl();

        $payload = $this->getTokenFields($code);

        return $this->sendTokenRequest($token_url, $payload);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->authServerMeta('token_endpoint', 'https://bsky.social/oauth/token');
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code): array
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_assertion_type' => self::CLIENT_ASSERTION_TYPE,
            'client_assertion' => $this->getClientAssertion($this->authUrl()),
        ];

        if ($this->usesPKCE()) {
            $fields['code_verifier'] = $this->request->session()->get('code_verifier');
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * Get the refresh token response for the given refresh token.
     *
     * @param  string  $refreshToken
     * @return array
     * @throws RequestException
     * @throws ConnectionException
     * @throws AuthenticationException
     */
    protected function getRefreshTokenResponse($refreshToken): array
    {
        $this->getOAuthSession()->put('old_refresh_token', $refreshToken);

        $token_url = $this->getTokenUrl();

        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_assertion_type' => self::CLIENT_ASSERTION_TYPE,
            'client_assertion' => $this->getClientAssertion($this->authUrl()),
        ];

        return $this->sendTokenRequest($token_url, $payload);
    }
}
