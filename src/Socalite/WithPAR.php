<?php

namespace Revolution\Bluesky\Socalite;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

trait WithPAR
{
    public function getParRequestUrl(
        string $auth_url,
        array $meta,
        string $state,
    ) {
        $par_url = Arr::get($meta, 'pushed_authorization_request_endpoint', 'https://bsky.social/oauth/par');

        $dpop_private_key = DPoP::generate();
        $this->request->session()->put('bluesky.dpop_private_key', $dpop_private_key);

        $dpop_private_jwk = DPoP::load($dpop_private_key);

        $response = $this->sendParRequest(
            auth_url: $auth_url,
            par_url: $par_url,
            state: $state,
            login_hint: $this->login_hint,
            dpop_private_jwk: $dpop_private_jwk,
        );

        if ($response->failed()) {
            throw new RuntimeException('Unable to get authorization response.');
        }

        return $response->json('request_uri');
    }

    public function sendParRequest(
        string $auth_url,
        string $par_url,
        string $state,
        ?string $login_hint,
        JsonWebKey $dpop_private_jwk,
    ): Response {
        $client_assertion = $this->getClientAssertion($auth_url);

        $par_body = [
            'response_type' => 'code',
            'code_challenge' => $this->getCodeChallenge(),
            'code_challenge_method' => $this->getCodeChallengeMethod(),
            'client_id' => $this->clientId,
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $client_assertion,
            'login_hint' => $login_hint,
        ];

        return Http::asForm()
            ->withRequestMiddleware(function (RequestInterface $request) use ($dpop_private_jwk, $par_url) {

                $dpop_nonce = $this->request->session()->get('bluesky.dpop_nonce', '');

                $dpop_proof = DPoP::authProof(
                    jwk: $dpop_private_jwk,
                    url: $par_url,
                    nonce: $dpop_nonce,
                );

                return $request->withHeader('DPoP', $dpop_proof);
            })
            ->withResponseMiddleware(function (ResponseInterface $response) {
                $dpop_nonce = collect($response->getHeader('DPoP-Nonce'))->first();

                $this->request->session()->put('bluesky.dpop_nonce', $dpop_nonce);

                return $response;
            })
            ->retry(times: 2, throw: false)
            ->post($par_url, $par_body);
    }

    public function getServerMeta(string $url): array
    {
        return Http::get($url.'/.well-known/oauth-authorization-server')
            ->json();
    }

    protected function getClientAssertion(string $url): string
    {
        $client_secret_jwk = BlueskyKey::load()->toJWK();

        $head = [
            'alg' => JsonWebKey::ALG,
            'kid' => $client_secret_jwk->kid(),
        ];

        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $url,
            'jti' => Str::random(40),
            'iat' => now()->timestamp,
        ];

        return JsonWebToken::encode($head, $payload, $client_secret_jwk->key());
    }
}
