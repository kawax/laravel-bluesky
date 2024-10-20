<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Socalite\BlueskyKey;
use Revolution\Bluesky\Socalite\DPoP;
use Revolution\Bluesky\Socalite\JsonWebKey;
use Revolution\Bluesky\Socalite\JsonWebToken;

trait WithPAR
{
    public function getParRequestUrl(
        string $auth_url,
        array $meta,
        string $state,
    ) {
        $par_url = Arr::get($meta, 'pushed_authorization_request_endpoint', 'https://bsky.social/oauth/par');

        $response = $this->sendParRequest(
            auth_url: $auth_url,
            par_url: $par_url,
            state: $state,
            login_hint: $this->login_hint,
            dpop_private_jwk: DPoP::load(),
        );

        return $response->json('request_uri');
    }

    public function sendParRequest(
        string $auth_url,
        string $par_url,
        string $state,
        ?string $login_hint,
        JsonWebKey $dpop_private_jwk,
    ): Response {
        $par_body = [
            'response_type' => 'code',
            'code_challenge' => $this->getCodeChallenge(),
            'code_challenge_method' => $this->getCodeChallengeMethod(),
            'client_id' => $this->clientId,
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->getClientAssertion($auth_url),
            'login_hint' => $login_hint,
        ];

        return Http::asForm()
            ->withRequestMiddleware(function (RequestInterface $request) use ($dpop_private_jwk, $par_url) {

                $dpop_nonce = $this->request->session()->get(DPoP::AUTH_NONCE, '');

                $dpop_proof = DPoP::authProof(
                    jwk: $dpop_private_jwk,
                    url: $par_url,
                    nonce: $dpop_nonce,
                );

                return $request->withHeader('DPoP', $dpop_proof);
            })
            ->withResponseMiddleware(function (ResponseInterface $response) {
                $dpop_nonce = collect($response->getHeader('DPoP-Nonce'))->first();

                $this->request->session()->put(DPoP::AUTH_NONCE, $dpop_nonce);

                return $response;
            })
            ->retry(times: 2, throw: false)
            ->throw()
            ->post($par_url, $par_body);
    }

    public function getServerMeta(string $auth_url): array
    {
        return Http::get($auth_url.'/.well-known/oauth-authorization-server')
            ->json();
    }

    public function getPDSResource(string $pds_url): array
    {
        return Http::get($pds_url.'/.well-known/oauth-protected-resource')
            ->json();
    }

    protected function getClientAssertion(string $auth_url): string
    {
        $client_secret_jwk = BlueskyKey::load()->toJWK();

        $head = [
            'alg' => JsonWebKey::ALG,
            'kid' => $client_secret_jwk->kid(),
        ];

        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $auth_url,
            'jti' => Str::random(40),
            'iat' => now()->timestamp,
        ];

        return JsonWebToken::encode($head, $payload, $client_secret_jwk->key());
    }
}
