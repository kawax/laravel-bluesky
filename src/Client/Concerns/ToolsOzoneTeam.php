<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Team;
use Revolution\Bluesky\Client\HasHttp;

trait ToolsOzoneTeam
{
    use HasHttp;

    public function addMember(string $did, string $role): Response
    {
        return $this->call(
            api: Team::addMember,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteMember(string $did): Response
    {
        return $this->call(
            api: Team::deleteMember,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listMembers(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Team::listMembers,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateMember(string $did, ?bool $disabled = null, ?string $role = null): Response
    {
        return $this->call(
            api: Team::updateMember,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
