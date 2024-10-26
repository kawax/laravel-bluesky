<?php

namespace Revolution\Bluesky\Traits;

use Illuminate\Container\Container;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Session\OAuthSession;

trait WithBluesky
{
    public function bluesky(): Factory
    {
        return Container::getInstance()
            ->make(Factory::class)
            ->withToken($this->tokenForBluesky());
    }

    abstract protected function tokenForBluesky(): OAuthSession;
}
