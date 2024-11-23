<?php

namespace Revolution\Bluesky\FeedGenerator\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;
use Revolution\Bluesky\Socalite\Key\JsonWebToken;
use Revolution\Bluesky\Support\AtUri;

class FeedSkeletonController
{
    public function __invoke(Request $request): mixed
    {
        $at = AtUri::parse($request->input('feed', ''));

        if ($at->collection() !== Feed::Generator->value || FeedGenerator::missing($at->rkey())) {
            abort(404);
        }

        return FeedGenerator::getFeedSkeleton(
            name: $at->rkey(),
            limit: $request->input('limit'),
            cursor: $request->input('cursor'),
            user: $this->userDid($request),
            request: $request,
        );
    }

    /**
     * Requesting user's DID.
     *
     * Skip verify.
     */
    protected function userDid(Request $request): ?string
    {
        $jwt = Str::of($request->header('Authorization'))->chopStart('Bearer')->trim()->toString();

        if (empty($jwt)) {
            return null;
        }

        $payload = data_get(JsonWebToken::decode($jwt), 'payload');

        return data_get($payload, 'iss');
    }
}
