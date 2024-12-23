<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Http\Request;

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
     * @return iterable<null|LabelMessage>
     * @throw LabelerException
     */
    abstract public function subscribeLabels(?int $cursor): iterable;

    /**
     * @link https://docs.bsky.app/docs/api/tools-ozone-moderation-emit-event
     */
    abstract public function emitEvent(Request $request, ?string $user): ?EmitEventResponse;

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
