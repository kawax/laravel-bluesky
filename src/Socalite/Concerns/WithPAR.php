<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Revolution\Bluesky\Socalite\Key\DPoP;

trait WithPAR
{
    protected function getParRequestUrl(string $state): string
    {
        $response = $this->sendParRequest($state);

        return $response->json('request_uri', '');
    }

    protected function sendParRequest(string $state): Response
    {
        $par_url = $this->authServerMeta('pushed_authorization_request_endpoint', 'https://bsky.social/oauth/par');

        $par_body = [
            'response_type' => 'code',
            'code_challenge' => $this->getCodeChallenge(),
            'code_challenge_method' => $this->getCodeChallengeMethod(),
            'client_id' => $this->clientId,
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->getClientAssertion($this->authUrl()),
            'login_hint' => $this->login_hint,
        ];

        return Http::asForm()
            ->withRequestMiddleware(function (RequestInterface $request) use ($par_url) {
                $dpop_nonce = $this->request->session()->get(DPoP::AUTH_NONCE, '');

                $dpop_proof = DPoP::authProof(
                    jwk: DPoP::load(),
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
}
