<?php

namespace Workbench\App\Labeler;

use Illuminate\Http\Request;
use Revolution\Bluesky\Labeler\AbstractLabeler;
use Revolution\Bluesky\Labeler\EmitEventResponse;
use Revolution\Bluesky\Labeler\LabelDefinition;
use Revolution\Bluesky\Labeler\LabelLocale;
use Revolution\Bluesky\Labeler\LabelMessage;

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

    /**
     * @return iterable<null|LabelMessage>
     *
     * @throw LabelerException
     */
    public function subscribeLabels(?int $cursor): iterable
    {
        yield null;
    }

    public function emitEvent(Request $request, ?string $user): ?EmitEventResponse
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
