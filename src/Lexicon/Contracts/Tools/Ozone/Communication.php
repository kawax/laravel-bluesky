<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Tools\Ozone;

interface Communication
{
    public const createTemplate = 'tools.ozone.communication.createTemplate';
    public const deleteTemplate = 'tools.ozone.communication.deleteTemplate';
    public const listTemplates = 'tools.ozone.communication.listTemplates';
    public const updateTemplate = 'tools.ozone.communication.updateTemplate';

    /**
     * Administrative action to create a new, re-usable communication (email for now) template.
     *
     * method: post
     */
    public function createTemplate(string $name, string $contentMarkdown, string $subject, ?string $lang = null, ?string $createdBy = null);

    /**
     * Delete a communication template.
     *
     * method: post
     */
    public function deleteTemplate(string $id);

    /**
     * Get list of all communication templates.
     *
     * method: get
     */
    public function listTemplates();

    /**
     * Administrative action to update an existing communication template. Allows passing partial fields to patch specific fields only.
     *
     * method: post
     */
    public function updateTemplate(string $id, ?string $name = null, ?string $lang = null, ?string $contentMarkdown = null, ?string $subject = null, ?string $updatedBy = null, ?bool $disabled = null);
}
