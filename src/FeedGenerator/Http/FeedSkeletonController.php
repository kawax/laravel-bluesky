<?php

namespace Revolution\Bluesky\FeedGenerator\Http;

use Illuminate\Http\Request;
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
        return data_get(JsonWebToken::decode($request->bearerToken()), 'payload.iss');
    }
}
