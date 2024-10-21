<?php

namespace Revolution\Bluesky\Socalite\Concerns;

use Illuminate\Support\Arr;
use Revolution\Bluesky\Session\OAuthSession;
use RuntimeException;

trait WithOAuthSession
{
    protected ?OAuthSession $session = null;

    /**
     * @param  array  $response  token
     */
    protected function getUserWithSession(array $response): array
    {
        $did = Arr::get($response, 'did', Arr::get($response, 'sub'));

        if ($this->hasInvalidDID($did)) {
            info('invalid did', Arr::wrap($did));

            throw new RuntimeException('Invalid DID.');
        }

        $user = $this->getUserByToken($did);

        if ($this->hasInvalidUser($user)) {
            info('invalid user', Arr::wrap($user));

            throw new RuntimeException();
        }

        $session = $this->getOAuthSession()
            ->merge($user)
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
}
