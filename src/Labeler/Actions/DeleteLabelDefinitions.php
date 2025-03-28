<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Actions;

use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Facades\Bluesky;

/**
 * @internal
 *
 * @link https://github.com/skyware-js/labeler/blob/main/src/scripts/declareLabeler.ts
 */
class DeleteLabelDefinitions
{
    public function __invoke(): array
    {
        return Bluesky::login(Config::string('bluesky.labeler.identifier'), Config::string('bluesky.labeler.password'))
            ->deleteLabelDefinitions()
            ->throw()
            ->json();
    }
}
