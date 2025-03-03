<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Actions;

use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Labeler\Labeler;
use Revolution\Bluesky\Record\LabelerService;
use RuntimeException;

/**
 * @internal
 *
 * @link https://github.com/skyware-js/labeler/blob/main/src/scripts/declareLabeler.ts
 */
class DeclareLabelDefinitions
{
    public function __invoke(): array
    {
        $labelValueDefinitions = collect(Labeler::getLabelDefinitions());
        if ($labelValueDefinitions->count() === 0) {
            throw new RuntimeException('No label definitions found.');
        }

        $labelValues = $labelValueDefinitions->pluck('identifier');

        $policies = [
            'labelValues' => $labelValues->toArray(),
            'labelValueDefinitions' => $labelValueDefinitions->toArray(),
        ];

        return Bluesky::login(Config::string('bluesky.labeler.identifier'), Config::string('bluesky.labeler.password'))
            ->upsertLabelDefinitions(fn (LabelerService $service) => $service->policies($policies))
            ->throw()
            ->json();
    }
}
