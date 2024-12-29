<?php

namespace Workbench\App\Labeler;

use Workbench\App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Crypto\JsonWebToken;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Labeler\AbstractLabeler;
use Revolution\Bluesky\Labeler\LabelDefinition;
use Revolution\Bluesky\Labeler\Labeler;
use Revolution\Bluesky\Labeler\LabelLocale;
use Revolution\Bluesky\Labeler\Response\SubscribeLabelResponse;
use Revolution\Bluesky\Labeler\SavedLabel;
use Revolution\Bluesky\Labeler\SignedLabel;
use Revolution\Bluesky\Labeler\UnsignedLabel;
use Revolution\Bluesky\Types\RepoRef;
use Revolution\Bluesky\Types\StrongRef;

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
     * @return iterable<SubscribeLabelResponse>
     *
     * @throw LabelerException
     */
    public function subscribeLabels(?int $cursor): iterable
    {
        if (is_null($cursor)) {
            return null;
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

    public function emitEvent(Request $request, ?string $did, ?string $token): iterable
    {
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

    public function saveLabel(SignedLabel $signed, string $sign): ?SavedLabel
    {
        $saved = Label::create($signed->toArray());

        return new SavedLabel(
            $saved->id,
            $signed,
        );
    }

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

    public function queryLabels(Request $request): array
    {
        $limit = max(min($request->input('limit', 10), 250), 1);

        $labels = Label::latest()->limit($limit)->get();

        return [
            'cursor' => (string) $labels->isNotEmpty() ? $labels->first()->id : '',
            'labels' => collect($labels->toArray())
                ->map(fn ($label) => Labeler::formatLabel($label))
                ->toArray(),
        ];
    }
}
