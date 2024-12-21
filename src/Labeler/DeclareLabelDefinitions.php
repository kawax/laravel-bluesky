<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\LabelerService;

/**
 * @link https://github.com/skyware-js/labeler/blob/main/src/scripts/declareLabeler.ts
 */
class DeclareLabelDefinitions
{
    public function __invoke(): array
    {
        $labelValueDefinitions = collect(Labeler::getLabelDefinitions());
        if ($labelValueDefinitions->count() === 0) {
            return [];
        }

        $labelValues = $labelValueDefinitions->pluck('identifier');

        $policies = [
            'labelValues' => $labelValues->toArray(),
            'labelValueDefinitions' => $labelValueDefinitions->toArray(),
        ];

        return Bluesky::login(Config::string('bluesky.labeler.identifier'), Config::string('bluesky.labeler.password'))
            ->upsertLabelDefinitions(fn (LabelerService $service) => $service->policies($policies))
            ->json();
    }
}
