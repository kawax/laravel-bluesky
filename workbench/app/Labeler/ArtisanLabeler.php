<?php

namespace Workbench\App\Labeler;

use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\NSID;
use Revolution\AtProto\Lexicon\Attributes\Output;
use Revolution\AtProto\Lexicon\Attributes\Ref;
use Revolution\AtProto\Lexicon\Attributes\Union;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Label;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;
use Revolution\Bluesky\Labeler\AbstractLabeler;
use Revolution\Bluesky\Labeler\LabelDefinition;
use Revolution\Bluesky\Labeler\LabelLocale;

class ArtisanLabeler extends AbstractLabeler
{
    /**
     * Label definitions.
     *
     * ```
     * use Revolution\Bluesky\Labeler\LabelDefinition;
     *
     * protected function definitions() {
     *     return [
     *         new LabelDefinition(
     *               identifier: 'artisan',
     *               severity: 'inform',
     *               blurs: 'none',
     *               locales: [new LabelLocale(lang: 'en', name: 'artisan', description: 'Web artisan')],
     *         ),
     *     ];
     * }
     * ```
     *
     * @return array<LabelDefinition>
     */
    public function definitions(): array
    {
        return [
            new LabelDefinition(
                identifier: 'artisan',
                severity: 'inform',
                blurs: 'none',
                locales: [
                    new LabelLocale(lang: 'en', name: 'artisan', description: 'Web artisan')
                ],
            ),
        ];
    }

    /**
     * @link https://docs.bsky.app/docs/api/com-atproto-label-query-labels
     */
    #[NSID(Label::queryLabels)]
    #[Output(Label::queryLabelsResponse)]
    public function queryLabels(array $uriPatterns, #[Format('did')] ?array $sources = null, ?int $limit = 50, ?string $cursor = null): array
    {
        // TODO: Implement queryLabels() method.
    }

    /**
     * @link https://docs.bsky.app/docs/api/com-atproto-moderation-create-report
     */
    #[NSID(Moderation::createReport)]
    #[Output(Moderation::createReportResponse)]
    public function createReport(#[Ref('com.atproto.moderation.defs#reasonType')] string $reasonType, #[Union(['com.atproto.admin.defs#repoRef', 'com.atproto.repo.strongRef'])] array $subject, ?string $reason = null): array
    {
        // TODO: Implement createReport() method.
    }
}
