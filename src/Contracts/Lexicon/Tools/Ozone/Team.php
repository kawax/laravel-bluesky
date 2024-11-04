<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\Tools\Ozone;

interface Team
{
    /**
     * Add a member to the ozone team. Requires admin role.
     *
     * method: post
     */
    public function addMember(string $did, string $role);

    /**
     * Delete a member from ozone team. Requires admin role.
     *
     * method: post
     */
    public function deleteMember(string $did);

    /**
     * List all members with access to the ozone service.
     *
     * method: get
     */
    public function listMembers(?int $limit = 50, ?string $cursor = null);

    /**
     * Update a member in the ozone service. Requires admin role.
     *
     * method: post
     */
    public function updateMember(string $did, ?bool $disabled = null, ?string $role = null);
}