<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Types;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\NSID;
use Revolution\AtProto\Lexicon\Types\AbstractUnion;

#[NSID('com.atproto.admin.defs#repoRef')]
final class RepoRef extends AbstractUnion implements Arrayable
{
    public function __construct(protected string $did)
    {
    }

    /**
     * ```
     * RepoRef::to(did: 'did:***');
     * ```
     */
    public static function to(#[Format('did')] string $did): self
    {
        return new self($did);
    }

    /**
     * @return array{did: string}
     */
    public function toArray(): array
    {
        return [
            'did' => $this->did,
        ];
    }
}
