<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Tools\Ozone;

interface Moderation
{
    public const emitEvent = 'tools.ozone.moderation.emitEvent';
    public const getEvent = 'tools.ozone.moderation.getEvent';
    public const getRecord = 'tools.ozone.moderation.getRecord';
    public const getRecords = 'tools.ozone.moderation.getRecords';
    public const getRepo = 'tools.ozone.moderation.getRepo';
    public const getRepos = 'tools.ozone.moderation.getRepos';
    public const queryEvents = 'tools.ozone.moderation.queryEvents';
    public const queryStatuses = 'tools.ozone.moderation.queryStatuses';
    public const searchRepos = 'tools.ozone.moderation.searchRepos';

    /**
     * Take a moderation action on an actor.
     *
     * method: post
     */
    public function emitEvent(array $event, array $subject, string $createdBy, ?array $subjectBlobCids = null);

    /**
     * Get details about a moderation event.
     *
     * method: get
     */
    public function getEvent(int $id);

    /**
     * Get details about a record.
     *
     * method: get
     */
    public function getRecord(string $uri, ?string $cid = null);

    /**
     * Get details about some records.
     *
     * method: get
     */
    public function getRecords(array $uris);

    /**
     * Get details about a repository.
     *
     * method: get
     */
    public function getRepo(string $did);

    /**
     * Get details about some repositories.
     *
     * method: get
     */
    public function getRepos(array $dids);

    /**
     * List moderation events related to a subject.
     *
     * method: get
     */
    public function queryEvents(?array $types = null, ?string $createdBy = null, ?string $sortDirection = 'desc', ?string $createdAfter = null, ?string $createdBefore = null, ?string $subject = null, ?array $collections = null, ?string $subjectType = null, ?bool $includeAllUserRecords = null, ?int $limit = 50, ?bool $hasComment = null, ?string $comment = null, ?array $addedLabels = null, ?array $removedLabels = null, ?array $addedTags = null, ?array $removedTags = null, ?array $reportTypes = null, ?string $cursor = null);

    /**
     * View moderation statuses of subjects (record or repo).
     *
     * method: get
     */
    public function queryStatuses(?bool $includeAllUserRecords = null, ?string $subject = null, ?string $comment = null, ?string $reportedAfter = null, ?string $reportedBefore = null, ?string $reviewedAfter = null, ?string $reviewedBefore = null, ?bool $includeMuted = null, ?bool $onlyMuted = null, ?string $reviewState = null, ?array $ignoreSubjects = null, ?string $lastReviewedBy = null, ?string $sortField = 'lastReportedAt', ?string $sortDirection = 'desc', ?bool $takendown = null, ?bool $appealed = null, ?int $limit = 50, ?array $tags = null, ?array $excludeTags = null, ?string $cursor = null, ?array $collections = null, ?string $subjectType = null);

    /**
     * Find repositories based on a search term.
     *
     * method: get
     */
    public function searchRepos(?string $term = null, ?string $q = null, ?int $limit = 50, ?string $cursor = null);
}
