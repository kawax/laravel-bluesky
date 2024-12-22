<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Http;

use Illuminate\Http\Request;
use Revolution\Bluesky\Labeler\Labeler;

final class LabelerController
{
    public function emitEvent(Request $request): array
    {
        return Labeler::emitEvent($request);
    }

    public function queryLabels(Request $request): array
    {
        return Labeler::queryLabels($request);
    }

    public function createReport(Request $request): array
    {
        return Labeler::createReport($request);
    }
}
