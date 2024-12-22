<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

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
     * @return array{cursor: string, labels: array{ver?: int, src: string, uri: string, cid?: string, val: string, neg?: bool, cts: string, exp?: string, sig?: mixed}}
     *
     * @link https://docs.bsky.app/docs/api/com-atproto-label-query-labels
     */
    abstract public function queryLabels(array $uriPatterns, ?array $sources = null, ?int $limit = 50, ?string $cursor = null): array;

    /**
     * @return array{id: int, reasonType: string, reason: string, subject: array, reportedBy: string, createdAt: string}
     *
     * @link https://docs.bsky.app/docs/api/com-atproto-moderation-create-report
     */
    abstract public function createReport(string $reasonType, array $subject, ?string $reason = null): array;
}
