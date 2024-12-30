<?php

namespace Workbench\App\Labeler;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Crypto\JsonWebToken;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Labeler\AbstractLabeler;
use Revolution\Bluesky\Labeler\LabelDefinition;
use Revolution\Bluesky\Labeler\Labeler;
use Revolution\Bluesky\Labeler\LabelerException;
use Revolution\Bluesky\Labeler\LabelLocale;
use Revolution\Bluesky\Labeler\Response\SubscribeLabelResponse;
use Revolution\Bluesky\Labeler\SavedLabel;
use Revolution\Bluesky\Labeler\SignedLabel;
use Revolution\Bluesky\Labeler\UnsignedLabel;
use Revolution\Bluesky\Types\RepoRef;
use Revolution\Bluesky\Types\StrongRef;
use Workbench\App\Models\Label;

readonly class ArtisanLabeler extends AbstractLabeler
{
    /**
     * @return array<LabelDefinition>
     */
    public function labels(): array
    {
        return [
            new LabelDefinition(
                identifier: 'artisan',
                locales: [
                    new LabelLocale(lang: 'en', name: 'artisan', description: 'Web artisan'),
                ],
                severity: 'inform',
                blurs: 'none',
            ),
        ];
    }

    /**
     * Called when connected via WebSocket.
     *
     * @return iterable<SubscribeLabelResponse>
     *
     * @throws LabelerException
     */
    public function subscribeLabels(?int $cursor): iterable
    {
        if (is_null($cursor)) {
            return null;
        }

        if ($cursor > Label::max('id')) {
            throw new LabelerException('FutureCursor', 'Cursor is in the future');
        }

        foreach (Label::where('id', '>', $cursor)->lazy() as $label) {
            $arr = $label->toArray();
            $arr = Labeler::formatLabel($arr);

            yield new SubscribeLabelResponse(
                seq: $label->id,
                labels: [$arr],
            );
        }
    }

    /**
     * Called when adding or removing labels.
     *
     * @return iterable<UnsignedLabel>
     *
     * @throws LabelerException
     *
     * @link https://docs.bsky.app/docs/api/tools-ozone-moderation-emit-event
     */
    public function emitEvent(Request $request, ?string $did, ?string $token): iterable
    {
        $type = data_get($request->input('event'), '$type');
        if ($type !== 'tools.ozone.moderation.defs#modEventLabel') {
            throw new LabelerException('InvalidRequest', 'Unsupported event type');
        }

        $subject = $request->input('subject');
        $uri = data_get($subject, 'uri', data_get($subject, 'did'));
        $cid = data_get($subject, 'cid');

        $createLabelVals = (array) data_get($request->input('event'), 'createLabelVals');
        $negateLabelVals = (array) data_get($request->input('event'), 'negateLabelVals');

        foreach ($createLabelVals as $val) {
            yield $this->createUnsignedLabel($uri, $cid, $val);
        }

        foreach ($negateLabelVals as $val) {
            yield $this->createUnsignedLabel($uri, $cid, $val, true);
        }
    }

    private function createUnsignedLabel(string $uri, ?string $cid, string $val, bool $neg = false): UnsignedLabel
    {
        return new UnsignedLabel(
            uri: $uri,
            cid: $cid,
            val: $val,
            src: Config::string('bluesky.labeler.did'),
            cts: now()->micro(0)->toISOString(),
            exp: null,
            neg: $neg,
        );
    }

    /**
     * Save to database, etc.
     *
     * @param  string  $sign  raw bytes compact signature
     *
     * @throws LabelerException
     */
    public function saveLabel(SignedLabel $signed, string $sign): ?SavedLabel
    {
        $saved = Label::create($signed->toArray());

        return new SavedLabel(
            $saved->id,
            $signed,
        );
    }

    /**
     * @return array{id: int, reasonType: string, reason: string, subject: array, reportedBy: string, createdAt: string}
     *
     * @link https://docs.bsky.app/docs/api/com-atproto-moderation-create-report
     */
    public function createReport(Request $request): array
    {
        $reasonType = $request->input('reasonType');
        if ($reasonType !== 'com.atproto.moderation.defs#reasonAppeal') {
            return [];
        }

        $jwt = $request->bearerToken();
        [, $payload] = JsonWebToken::explode($jwt);
        $reportedBy = data_get($payload, 'iss');

        $reason = $request->input('reason', '');
        $subject = $request->input('subject');

        if (data_get($subject, '$type') === 'com.atproto.admin.defs#repoRef') {
            $did = data_get($subject, 'did');
            $delete = RepoRef::to($did);
        } elseif (data_get($subject, '$type') === 'com.atproto.repo.strongRef') {
            $uri = data_get($subject, 'uri');
            $cid = data_get($subject, 'cid');
            $delete = StrongRef::to($uri, $cid);
        } else {
            return [];
        }

        $res = Bluesky::login(config('bluesky.labeler.identifier'), config('bluesky.labeler.password'))
            ->deleteLabels(
                subject: $delete,
                labels: ['artisan'],
            );

        return [
            'id' => $res->json('id'),
            'reasonType' => $reasonType,
            'reason' => $reason,
            'subject' => $subject,
            'reportedBy' => $reportedBy,
            'createdAt' => now()->toISOString(),
        ];
    }

    /**
     * @return array{cursor: string, labels: array{ver?: int, src: string, uri: string, cid?: string, val: string, neg?: bool, cts: string, exp?: string, sig?: mixed}}
     *
     * @link https://docs.bsky.app/docs/api/com-atproto-label-query-labels
     */
    public function queryLabels(Request $request): array
    {
        $limit = max(min($request->input('limit', 1), 250), 1);

        $labels = Label::latest()->limit($limit)->get();

        return [
            'cursor' => (string) $labels->isNotEmpty() ? $labels->first()->id : '',
            'labels' => collect($labels->toArray())
                ->map(fn ($label) => Labeler::formatLabel($label))
                ->toArray(),
        ];
    }
}
