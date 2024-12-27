<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Http\Request;
use Revolution\Bluesky\Labeler\Response\SubscribeLabelResponse;

abstract class AbstractLabeler
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
     *               locales: [
     *                   new LabelLocale(
     *                       lang: 'en',
     *                       name: 'artisan',
     *                       description: 'Web artisan',
     *                   ),
     *               ],
     *               severity: 'inform',
     *               blurs: 'none',
     *         ),
     *     ];
     * }
     * ```
     *
     * @return array<LabelDefinition>
     */
    abstract public function labels(): array;

    /**
     * @return iterable<SubscribeLabelResponse>
     *
     * @throw LabelerException
     */
    abstract public function subscribeLabels(?int $cursor): iterable;

    /**
     * @return iterable<UnsignedLabel>
     *
     * @throw LabelerException
     *
     * @link https://docs.bsky.app/docs/api/tools-ozone-moderation-emit-event
     */
    abstract public function emitEvent(Request $request): iterable;

    /**
     * @param  string  $sign  raw bytes compact signature
     *
     * @throw LabelerException
     */
    abstract public function saveLabel(SignedLabel $signed, string $sign): ?SavedLabel;

    /**
     * @return array{id: int, reasonType: string, reason: string, subject: array, reportedBy: string, createdAt: string}
     *
     * @link https://docs.bsky.app/docs/api/com-atproto-moderation-create-report
     */
    abstract public function createReport(Request $request): array;

    /**
     * @return array{cursor: string, labels: array{ver?: int, src: string, uri: string, cid?: string, val: string, neg?: bool, cts: string, exp?: string, sig?: mixed}}
     *
     * @link https://docs.bsky.app/docs/api/com-atproto-label-query-labels
     */
    abstract public function queryLabels(Request $request): array;
}
