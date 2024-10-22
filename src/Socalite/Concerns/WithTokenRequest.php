<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Socalite\Key\DPoP;

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

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
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
     */
    protected function getRefreshTokenResponse($refreshToken): array
    {
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
