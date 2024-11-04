<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Enum;

enum AtProtoSync: string
{
    /**
     * Get a blob associated with a given account. Returns the full blob as originally uploaded. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    case getBlob = 'com.atproto.sync.getBlob';

    /**
     * Get data blocks from a given repo, by CID. For example, intermediate MST nodes, or records. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    case getBlocks = 'com.atproto.sync.getBlocks';

    /**
     * DEPRECATED - please use com.atproto.sync.getRepo instead
     *
     * method: get
     */
    case getCheckout = 'com.atproto.sync.getCheckout';

    /**
     * DEPRECATED - please use com.atproto.sync.getLatestCommit instead
     *
     * method: get
     */
    case getHead = 'com.atproto.sync.getHead';

    /**
     * Get the current commit CID & revision of the specified repo. Does not require auth.
     *
     * method: get
     */
    case getLatestCommit = 'com.atproto.sync.getLatestCommit';

    /**
     * Get data blocks needed to prove the existence or non-existence of record in the current version of repo. Does not require auth.
     *
     * method: get
     */
    case getRecord = 'com.atproto.sync.getRecord';

    /**
     * Download a repository export as CAR file. Optionally only a 'diff' since a previous revision. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    case getRepo = 'com.atproto.sync.getRepo';

    /**
     * Get the hosting status for a repository, on this server. Expected to be implemented by PDS and Relay.
     *
     * method: get
     */
    case getRepoStatus = 'com.atproto.sync.getRepoStatus';

    /**
     * List blob CIDs for an account, since some repo revision. Does not require auth; implemented by PDS.
     *
     * method: get
     */
    case listBlobs = 'com.atproto.sync.listBlobs';

    /**
     * Enumerates all the DID, rev, and commit CID for all repos hosted by this service. Does not require auth; implemented by PDS and Relay.
     *
     * method: get
     */
    case listRepos = 'com.atproto.sync.listRepos';

    /**
     * Notify a crawling service of a recent update, and that crawling should resume. Intended use is after a gap between repo stream events caused the crawling service to disconnect. Does not require auth; implemented by Relay.
     *
     * method: post
     */
    case notifyOfUpdate = 'com.atproto.sync.notifyOfUpdate';

    /**
     * Request a service to persistently crawl hosted repos. Expected use is new PDS instances declaring their existence to Relays. Does not require auth.
     *
     * method: post
     */
    case requestCrawl = 'com.atproto.sync.requestCrawl';
}
