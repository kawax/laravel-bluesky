<?php

namespace Workbench\App\Labeler;

use Illuminate\Http\Request;
use Revolution\Bluesky\Labeler\AbstractLabeler;
use Revolution\Bluesky\Labeler\LabelDefinition;
use Revolution\Bluesky\Labeler\LabelLocale;
use Revolution\Bluesky\Labeler\SavedLabel;
use Revolution\Bluesky\Labeler\SignedLabel;
use Revolution\Bluesky\Labeler\SubscribeLabelMessage;

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
                locales: [
                    new LabelLocale(lang: 'en', name: 'artisan', description: 'Web artisan'),
                ],
                severity: 'inform',
                blurs: 'none',
            ),
        ];
    }

    /**
     * @return iterable<null|SubscribeLabelMessage>
     *
     * @throw LabelerException
     */
    public function subscribeLabels(?int $cursor): iterable
    {
        yield null;
    }

    public function emitEvent(Request $request, ?string $user, ?string $token): iterable
    {
        return null;
    }

    public function saveLabel(SignedLabel $label, string $sign): ?SavedLabel
    {
        return null;
    }

    public function createReport(Request $request): array
    {
        return [];
    }

    public function queryLabels(Request $request): array
    {
        return [];
    }
}
