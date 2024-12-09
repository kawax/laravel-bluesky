<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use BackedEnum;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Enum\Feed;
use Revolution\AtProto\Lexicon\Enum\Graph;
use Revolution\Bluesky\Record\Block;
use Revolution\Bluesky\Support\AtUri;

use function Illuminate\Support\enum_value;

/**
 * Separate the excessive shorthands. Will delete them in the future.
 * @deprecated
 */
trait HasShortHandStash
{
    /**
     * @param  array<string>  $actors
     */
    public function getProfiles(array $actors): Response
    {
        return $this->client(auth: true)
            ->getProfiles(
                actors: $actors,
            );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function muteModList(#[Format('at-uri')] string $list): Response
    {
        return $this->client(auth: true)
            ->muteActorList(
                list: $list,
            );
    }

    public function searchActorsTypeahead(?string $q = null, ?int $limit = 10): Response
    {
        return $this->client(auth: true)
            ->searchActorsTypeahead(
                q: $q,
                limit: $limit,
            );
    }

    /**
     * @param  string  $list  AT-URI
     */
    public function unmuteModList(#[Format('at-uri')] string $list): Response
    {
        return $this->client(auth: true)
            ->unmuteActorList(
                list: $list,
            );
    }

    public function getPostThread(#[Format('at-uri')] string $uri, ?int $depth = 6, ?int $parentHeight = 80): Response
    {
        return $this->client(auth: true)
            ->getPostThread(
                uri: $uri,
                depth: $depth,
                parentHeight: $parentHeight,
            );
    }

    public function unmute(#[Format('at-identifier')] string $actor): Response
    {
        return $this->client(auth: true)
            ->unmuteActor(
                actor: $actor,
            );
    }

    /**
     * Remove a user from a list.
     */
    public function deleteListItem(#[Format('at-uri')] string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Graph::Listitem->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $at->repo(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    /**
     * @param  array<string>  $uris  AT-URI
     */
    public function getPosts(array $uris): Response
    {
        return $this->client(auth: true)
            ->getPosts(
                uris: $uris,
            );
    }

    public function mute(#[Format('at-identifier')] string $actor): Response
    {
        return $this->client(auth: true)
            ->muteActor(
                actor: $actor,
            );
    }

    /**
     * @param  string  $uri  at://did:plc:.../app.bsky.feed.post/{rkey}
     */
    public function getRepostedBy(#[Format('at-uri')] string $uri, ?string $cid = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getRepostedBy(
                uri: $uri,
                cid: $cid,
                limit: $limit,
                cursor: $cursor,
            );
    }

    public function unpublishFeedGenerator(BackedEnum|string $name): Response
    {
        return $this->deleteRecord(
            repo: $this->assertDid(),
            collection: Feed::Generator->value,
            rkey: enum_value($name),
        );
    }

    /**
     * @throws AuthenticationException
     */
    public function block(Block|string $did): Response
    {
        $block = $did instanceof Block ? $did : Block::create(did: $did);

        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Graph::Block->value,
            record: $block,
        );
    }

    /**
     * @param  string  $uri  at://did:plc:.../app.bsky.graph.block/{rkey}
     *
     * @throws AuthenticationException
     */
    public function unblock(#[Format('at-uri')] string $uri): Response
    {
        $at = AtUri::parse($uri);

        if ($at->collection() !== Graph::Block->value) {
            throw new InvalidArgumentException();
        }

        return $this->deleteRecord(
            repo: $this->assertDid(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    public function getSuggestions(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getSuggestions(
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string  $list  AT-URI
     *
     * @throws AuthenticationException
     */
    public function unblockModList(#[Format('at-uri')] string $list): Response
    {
        $blocked = $this->getList(
            list: $list,
            limit: 1,
        )->json('list.viewer.blocked');

        if (empty($blocked)) {
            return new Response(Http::response([], 404)->wait());
        }

        $at = AtUri::parse($blocked);

        return $this->deleteRecord(
            repo: $this->assertDid(),
            collection: $at->collection(),
            rkey: $at->rkey(),
        );
    }

    public function getListMutes(?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->getListMutes(
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string  $list  AT-URI
     *
     * @throws AuthenticationException
     */
    public function blockModList(#[Format('at-uri')] string $list): Response
    {
        return $this->createRecord(
            repo: $this->assertDid(),
            collection: Graph::Listblock->value,
            record: [
                '$type' => Graph::Listblock->value,
                'subject' => $list,
                'createdAt' => now()->toISOString(),
            ],
        );
    }

    public function searchActors(?string $q = null, ?int $limit = 25, ?string $cursor = null): Response
    {
        return $this->client(auth: true)
            ->searchActors(
                q: $q,
                limit: $limit,
                cursor: $cursor,
            );
    }

    /**
     * @param  string  $handle  `***.bsky.social` `alice.test`
     */
    public function updateHandle(#[Format('handle')] string $handle): Response
    {
        return $this->client(auth: true)
            ->updateHandle(
                handle: $handle,
            );
    }
}
