<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Communication;
use Revolution\Bluesky\Client\HasHttp;

trait ToolsOzoneCommunication
{
    use HasHttp;

    public function createTemplate(string $name, string $contentMarkdown, string $subject, ?string $lang = null, ?string $createdBy = null): Response
    {
        return $this->call(
            api: Communication::createTemplate,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteTemplate(string $id): Response
    {
        return $this->call(
            api: Communication::deleteTemplate,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listTemplates(): Response
    {
        return $this->call(
            api: Communication::listTemplates,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateTemplate(string $id, ?string $name = null, ?string $lang = null, ?string $contentMarkdown = null, ?string $subject = null, ?string $updatedBy = null, ?bool $disabled = null): Response
    {
        return $this->call(
            api: Communication::updateTemplate,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
