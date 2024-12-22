<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\NSID;
use Revolution\AtProto\Lexicon\Attributes\Output;
use Revolution\AtProto\Lexicon\Attributes\Ref;
use Revolution\AtProto\Lexicon\Attributes\Union;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Label;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;

abstract class AbstractLabeler implements Label, Moderation
{
    /**
     * Label definitions.
     *
     * ```
     * use Revolution\Bluesky\Labeler\LabelDefinition;
     * use Revolution\Bluesky\Labeler\LabelLocale;
     *
     * protected function labels() {
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
    abstract public function labels(): array;

    /**
     * @link https://docs.bsky.app/docs/api/com-atproto-label-query-labels
     */
    #[NSID(Label::queryLabels)]
    #[Output(Label::queryLabelsResponse)]
    abstract public function queryLabels(array $uriPatterns, #[Format('did')] ?array $sources = null, ?int $limit = 50, ?string $cursor = null): array;

    /**
     * @link https://docs.bsky.app/docs/api/com-atproto-moderation-create-report
     */
    #[NSID(Moderation::createReport)]
    #[Output(Moderation::createReportResponse)]
    abstract public function createReport(#[Ref('com.atproto.moderation.defs#reasonType')] string $reasonType, #[Union(['com.atproto.admin.defs#repoRef', 'com.atproto.repo.strongRef'])] array $subject, ?string $reason = null): array;
}
