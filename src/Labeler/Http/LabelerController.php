<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Revolution\Bluesky\Labeler\Labeler;

final class LabelerController
{
    public function queryLabels(Request $request): Response
    {
        $header = $request->header('atproto-accept-labelers');

        return response(Labeler::queryLabels($request), 200, ['atproto-content-labelers' => $header]);
    }

    public function createReport(Request $request): array
    {
        return Labeler::createReport($request);
    }
}
