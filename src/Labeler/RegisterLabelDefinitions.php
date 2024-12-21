<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\LabelerService;

final class RegisterLabelDefinitions
{
    public function __invoke(): void
    {
        $labels = Labeler::getLabelDefinitions();
        if (count($labels) === 0) {
            return;
        }

        $policies = collect($labels)->pluck('identifier');

        Bluesky::login(Config::string('bluesky.labeler.identifier'), Config::string('bluesky.labeler.password'))
            ->upsertLabelDefinitions(fn (LabelerService $service) => $service->policies($policies->toArray())
                ->labels($labels));
    }
}
