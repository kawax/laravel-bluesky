<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Tools\Ozone;

interface Set
{
    /**
     * Add values to a specific set. Attempting to add values to a set that does not exist will result in an error.
     *
     * method: post
     */
    public function addValues(string $name, array $values);

    /**
     * Delete an entire set. Attempting to delete a set that does not exist will result in an error.
     *
     * method: post
     */
    public function deleteSet(string $name);

    /**
     * Delete values from a specific set. Attempting to delete values that are not in the set will not result in an error
     *
     * method: post
     */
    public function deleteValues(string $name, array $values);

    /**
     * Get a specific set and its values
     *
     * method: get
     */
    public function getValues(string $name, ?int $limit = 100, ?string $cursor = null);

    /**
     * Query available sets
     *
     * method: get
     */
    public function querySets(?int $limit = 50, ?string $cursor = null, ?string $namePrefix = null, ?string $sortBy = 'name', ?string $sortDirection = 'asc');

    /**
     * Create or update set metadata
     *
     * method: post
     */
    public function upsertSet();
}
