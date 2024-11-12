<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Feed\AbstractThreadgate;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\AtProto\Lexicon\Enum\ThreadGateRule;
use Revolution\Bluesky\Types\BlankUnion;

class ThreadGate extends AbstractThreadgate implements Arrayable, Recordable
{
    use HasRecord;

    public function __construct(string $post, ?array $allow = null)
    {
        $this->post = $post;
        $this->allow = $allow;
    }

    /**
     * ```
     * use Revolution\Bluesky\Record\ThreadGate;
     *
     * $gate = ThreadGate::create(post: 'at://', allow: [ThreadGate::mention(), ThreadGate::following(), ThreadGate::list('at://')]);
     * ```
     */
    public static function create(#[Format('at-uri')] string $post, ?array $allow = null): static
    {
        return new static($post, $allow);
    }

    /**
     * Allow replies from actors mentioned in your post.
     */
    public static function mention(): array
    {
        return BlankUnion::make(type: ThreadGateRule::MentionRule)->toArray();
    }

    /**
     * Allow replies from actors you follow.
     */
    public static function following(): array
    {
        return BlankUnion::make(type: ThreadGateRule::FollowingRule)->toArray();
    }

    /**
     * Allow replies from actors on a list.
     */
    public static function list(#[Format('at-uri')] string $list): array
    {
        return [
            '$type' => ThreadGateRule::ListRule->value,
            'list' => $list,
        ];
    }

    public function hiddenReplies(?array $hiddenReplies): static
    {
        $this->hiddenReplies = $hiddenReplies;

        return $this;
    }
}
