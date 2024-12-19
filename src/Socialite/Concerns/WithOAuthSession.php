<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Concerns;

use InvalidArgumentException;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Support\DidDocument;
use Revolution\Bluesky\Support\Identity;

trait WithOAuthSession
{
    protected ?OAuthSession $session = null;

    /**
     * @param  array  $response  token
     */
    protected function getUserWithSession(array $response): array
    {
        $did = data_get($response, 'did', data_get($response, 'sub'));

        if ($this->hasInvalidDID($did)) {
            throw new InvalidArgumentException('Invalid DID.');
        }

        $profile = $this->getUserByToken($did);

        $didDoc = $this->getDidDoc($did);

        if ($this->hasInvalidDidDoc($didDoc)) {
            throw new InvalidArgumentException('Invalid DID Doc.');
        }

        $user = array_merge($didDoc, $profile);

        $session = $this->getOAuthSession()
            ->put('didDoc', $didDoc)
            ->put('profile', $profile)
            ->merge($response)
            ->merge([
                'iss' => $this->request->input('iss'),
            ]);

        $user['session'] = $session;

        return $user;
    }

    protected function getUserByToken($token): array
    {
        return Bluesky::getProfile($token)->json();
    }

    protected function getDidDoc(?string $did): array
    {
        return Bluesky::identity()->resolveDID($did)->json();
    }

    public function getOAuthSession(): OAuthSession
    {
        if (is_null($this->session)) {
            $this->session = new OAuthSession();
        }

        return $this->session;
    }

    public function setOAuthSession(?OAuthSession $session = null): self
    {
        $this->session = $session;

        return $this;
    }

    protected function hasInvalidDidDoc(array $didDoc): bool
    {
        $pds_url = DidDocument::make($didDoc)->pdsUrl();

        if (empty($pds_url)) {
            return true;
        }

        $auth_url = $this->pdsProtectedResource($pds_url)->authServer();

        return $this->authUrl() !== $auth_url;
    }

    protected function hasInvalidDID(?string $did): bool
    {
        if (! Identity::isDID($did)) {
            return true;
        }

        if (! empty($this->login_hint)) {
            if (Identity::isDID($this->login_hint)) {
                return $this->login_hint !== $did;
            }
            if (Identity::isHandle($this->login_hint)) {
                return Bluesky::resolveHandle($this->login_hint)->json('did') !== $did;
            }
        }

        return false;
    }
}
