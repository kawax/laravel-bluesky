<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Arr;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;
use InvalidArgumentException;
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
            info('invalid did', Arr::wrap($did));

            throw new InvalidArgumentException('Invalid DID.');
        }

        $profile = $this->getUserByToken($did);

        $didDoc = $this->getDidDoc($did);

        if ($this->hasInvalidDidDoc($didDoc)) {
            info('invalid DID Doc', Arr::wrap($didDoc));

            throw new InvalidArgumentException('Invalid DID Doc.');
        }

        $user = array_merge($didDoc, $profile);

        $session = $this->getOAuthSession()
            ->merge($didDoc)
            ->merge($profile)
            ->merge($response)
            ->merge([
                'iss' => $this->request->input('iss'),
            ])
            ->except([
                '@context',
            ]);

        $user['session'] = $session;

        return $user;
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
        $pds_url = DidDocument::create($didDoc)->pdsEndpoint();
        if (empty($pds_url)) {
            return true;
        }

        $auth_url = $this->pdsProtectedResourceMeta($pds_url, 'authorization_servers.{first}');

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
                return Bluesky::identity()->resolveHandle($this->login_hint) !== $did;
            }
        }

        return false;
    }
}
