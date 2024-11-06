<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Tools\Ozone\Moderation;

trait ToolsOzoneModeration
{
    public function emitEvent(array $event, array $subject, string $createdBy, ?array $subjectBlobCids = null): Response
    {
        return $this->call(
            api: Moderation::emitEvent,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getEvent(int $id): Response
    {
        return $this->call(
            api: Moderation::getEvent,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRecord(string $uri, ?string $cid = null): Response
    {
        return $this->call(
            api: Moderation::getRecord,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRecords(array $uris): Response
    {
        return $this->call(
            api: Moderation::getRecords,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRepo(string $did): Response
    {
        return $this->call(
            api: Moderation::getRepo,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getRepos(array $dids): Response
    {
        return $this->call(
            api: Moderation::getRepos,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function queryEvents(?array $types = null, ?string $createdBy = null, ?string $sortDirection = 'desc', ?string $createdAfter = null, ?string $createdBefore = null, ?string $subject = null, ?array $collections = null, ?string $subjectType = null, ?bool $includeAllUserRecords = null, ?int $limit = 50, ?bool $hasComment = null, ?string $comment = null, ?array $addedLabels = null, ?array $removedLabels = null, ?array $addedTags = null, ?array $removedTags = null, ?array $reportTypes = null, ?string $cursor = null): Response
    {
        return $this->call(
            api: Moderation::queryEvents,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function queryStatuses(?bool $includeAllUserRecords = null, ?string $subject = null, ?string $comment = null, ?string $reportedAfter = null, ?string $reportedBefore = null, ?string $reviewedAfter = null, ?string $reviewedBefore = null, ?bool $includeMuted = null, ?bool $onlyMuted = null, ?string $reviewState = null, ?array $ignoreSubjects = null, ?string $lastReviewedBy = null, ?string $sortField = 'lastReportedAt', ?string $sortDirection = 'desc', ?bool $takendown = null, ?bool $appealed = null, ?int $limit = 50, ?array $tags = null, ?array $excludeTags = null, ?string $cursor = null, ?array $collections = null, ?string $subjectType = null): Response
    {
        return $this->call(
            api: Moderation::queryStatuses,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function searchRepos(?string $term = null, ?string $q = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Moderation::searchRepos,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
