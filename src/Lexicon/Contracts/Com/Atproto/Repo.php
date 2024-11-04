<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Com\Atproto;

interface Repo
{
    /**
     * Apply a batch transaction of repository creates, updates, and deletes. Requires auth, implemented by PDS.
     *
     * method: post
     */
    public function applyWrites(string $repo, array $writes, ?bool $validate = null, ?string $swapCommit = null);

    /**
     * Create a single new repository record. Requires auth, implemented by PDS.
     *
     * method: post
     */
    public function createRecord(string $repo, string $collection, mixed $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null);

    /**
     * Delete a repository record, or ensure it doesn't exist. Requires auth, implemented by PDS.
     *
     * method: post
     */
    public function deleteRecord(string $repo, string $collection, string $rkey, ?string $swapRecord = null, ?string $swapCommit = null);

    /**
     * Get information about an account and repository, including the list of collections. Does not require auth.
     *
     * method: get
     */
    public function describeRepo(string $repo);

    /**
     * Get a single record from a repository. Does not require auth.
     *
     * method: get
     */
    public function getRecord(string $repo, string $collection, string $rkey, ?string $cid = null);

    /**
     * Import a repo in the form of a CAR file. Requires Content-Length HTTP header to be set.
     *
     * method: post
     */
    public function importRepo();

    /**
     * Returns a list of missing blobs for the requesting account. Intended to be used in the account migration flow.
     *
     * method: get
     */
    public function listMissingBlobs(?int $limit = 500, ?string $cursor = null);

    /**
     * List a range of records in a repository, matching a specific collection. Does not require auth.
     *
     * method: get
     */
    public function listRecords(string $repo, string $collection, ?int $limit = 50, ?string $cursor = null, ?string $rkeyStart = null, ?string $rkeyEnd = null, ?bool $reverse = null);

    /**
     * Write a repository record, creating or updating it as needed. Requires auth, implemented by PDS.
     *
     * method: post
     */
    public function putRecord(string $repo, string $collection, string $rkey, mixed $record, ?bool $validate = null, ?string $swapRecord = null, ?string $swapCommit = null);

    /**
     * Upload a new blob, to be referenced from a repository record. The blob will be deleted if it is not referenced within a time window (eg, minutes). Blob restrictions (mimetype, size, etc) are enforced when the reference is created. Requires auth, implemented by PDS.
     *
     * method: post
     */
    public function uploadBlob();
}
