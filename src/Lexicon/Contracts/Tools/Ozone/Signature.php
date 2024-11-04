<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Tools\Ozone;

interface Signature
{
    public const findCorrelation = 'tools.ozone.signature.findCorrelation';
    public const findRelatedAccounts = 'tools.ozone.signature.findRelatedAccounts';
    public const searchAccounts = 'tools.ozone.signature.searchAccounts';

    /**
     * Find all correlated threat signatures between 2 or more accounts.
     *
     * method: get
     */
    public function findCorrelation(array $dids);

    /**
     * Get accounts that share some matching threat signatures with the root account.
     *
     * method: get
     */
    public function findRelatedAccounts(string $did, ?string $cursor = null, ?int $limit = 50);

    /**
     * Search for accounts that match one or more threat signature values.
     *
     * method: get
     */
    public function searchAccounts(array $values, ?string $cursor = null, ?int $limit = 50);
}
