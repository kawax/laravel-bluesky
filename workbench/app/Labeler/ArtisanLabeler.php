<?php

namespace Workbench\App\Labeler;

use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\Ref;
use Revolution\AtProto\Lexicon\Attributes\Union;
use Revolution\Bluesky\Labeler\AbstractLabeler;
use Revolution\Bluesky\Labeler\LabelDefinition;
use Revolution\Bluesky\Labeler\LabelLocale;

class ArtisanLabeler extends AbstractLabeler
{
    /**
     * @return array<LabelDefinition>
     */
    public function labels(): array
    {
        return [
            new LabelDefinition(
                identifier: 'artisan',
                severity: 'inform',
                blurs: 'none',
                locales: [
                    new LabelLocale(lang: 'en', name: 'artisan', description: 'Web artisan'),
                ],
            ),
        ];
    }

    public function queryLabels(array $uriPatterns, #[Format('did')] ?array $sources = null, ?int $limit = 50, ?string $cursor = null): array
    {
        dump($uriPatterns);

        return [];
    }

    public function createReport(#[Ref('com.atproto.moderation.defs#reasonType')] string $reasonType, #[Union(['com.atproto.admin.defs#repoRef', 'com.atproto.repo.strongRef'])] array $subject, ?string $reason = null): array
    {
        return [];
    }
}
