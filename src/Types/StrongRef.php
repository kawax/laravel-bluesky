<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Types;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\NSID;
use Revolution\AtProto\Lexicon\Union\Com\Atproto\Repo\AbstractStrongRef;

/**
 * A URI with a content-hash fingerprint.
 */
#[NSID('com.atproto.repo.strongRef')]
final class StrongRef extends AbstractStrongRef implements Arrayable
{
    public function __construct(string $uri, string $cid)
    {
        $this->uri = $uri;
        $this->cid = $cid;
    }

    /**
     * to.
     *
     * ```
     * StrongRef::to(uri: 'at://', cid: '...');
     * ```
     */
    public static function to(#[Format('at-uri')] string $uri, #[Format('cid')] string $cid): self
    {
        return new self($uri, $cid);
    }

    /**
     * @return array{uri: string, cid: string}
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'cid' => $this->cid,
        ];
    }
}
