<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Com\Atproto;

interface Sync
{
    /**
     * Get a blob associated with a given account. Returns the full blob as originally uploaded. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    public function getBlob(string $did, string $cid);

    /**
     * Get data blocks from a given repo, by CID. For example, intermediate MST nodes, or records. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    public function getBlocks(string $did, array $cids);

    /**
     * DEPRECATED - please use com.atproto.sync.getRepo instead
     *
     * method: get
     */
    public function getCheckout(string $did);

    /**
     * DEPRECATED - please use com.atproto.sync.getLatestCommit instead
     *
     * method: get
     */
    public function getHead(string $did);

    /**
     * Get the current commit CID & revision of the specified repo. Does not require auth.
     *
     * method: get
     */
    public function getLatestCommit(string $did);

    /**
     * Get data blocks needed to prove the existence or non-existence of record in the current version of repo. Does not require auth.
     *
     * method: get
     */
    public function getRecord(string $did, string $collection, string $rkey, ?string $commit = null);

    /**
     * Download a repository export as CAR file. Optionally only a 'diff' since a previous revision. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    public function getRepo(string $did, ?string $since = null);

    /**
     * Get the hosting status for a repository, on this server. Expected to be implemented by PDS and Relay.
     *
     * method: get
     */
    public function getRepoStatus(string $did);

    /**
     * List blob CIDs for an account, since some repo revision. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    public function listBlobs(string $did, ?string $since = null, ?int $limit = 500, ?string $cursor = null);

    /**
     * Enumerates all the DID, rev, and commit CID for all repos hosted by this service. Does not require auth; implemented by PDS and Relay.
     *
     * method: get
     */
    public function listRepos(?int $limit = 500, ?string $cursor = null);

    /**
     * Notify a crawling service of a recent update, and that crawling should resume. Intended use is after a gap between repo stream events caused the crawling service to disconnect. Does not require auth; implemented by Relay.
     *
     * method: post
     */
    public function notifyOfUpdate(string $hostname);

    /**
     * Request a service to persistently crawl hosted repos. Expected use is new PDS instances declaring their existence to Relays. Does not require auth.
     *
     * method: post
     */
    public function requestCrawl(string $hostname);
}
