<?php

namespace Revolution\Bluesky\RichText;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Revolution\AtProto\Lexicon\Enum\Facet;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\Identity;

/**
 * @see https://github.com/bluesky-social/atproto/blob/main/packages/api/src/rich-text/detection.ts
 */
class DetectFacets
{
    protected const MENTION_REGEX = '/(^|\s|\()(@)([a-zA-Z0-9.-]+)(\b)/';

    protected const URL_REGEX = '/(^|\s|\()((https?:\/\/[\S]+)|((?<domain>[a-z][a-z0-9]*(\.[a-z0-9]+)+)[\S]*))/i';

    protected const TRAILING_PUNCTUATION_REGEX = '/\p{P}+$/u';

    protected const TAG_REGEX = '/(^|\s)[#＃]((?!\x{fe0f})[^\s\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]*[^\d\s\p{P}\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]+[^\s\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]*)?/u';
    /**
     * `\ufe0f` emoji modifier
     * `\u00AD\u2060\u200A\u200B\u200C\u200D\u20e2` zero-width spaces (likely incomplete)
     */
    //protected const TAG_REGEX = '/(^|\s)[#＃]((?!\ufe0f)[^\s\u00AD\u2060\u200A\u200B\u200C\u200D\u20e2]*[^\d\s\p{P}\u00AD\u2060\u200A\u200B\u200C\u200D\u20e2]+[^\s\u00AD\u2060\u200A\u200B\u200C\u200D\u20e2]*)?/gu';

    protected const TYPE = 'app.bsky.richtext.facet';

    protected readonly string $text;

    public array $facets = [];

    public function detect(string $text): static
    {
        $this->text = $text;

        $this->mention();
        $this->link();
        $this->tag();

        return $this;
    }

    protected function mention(): void
    {
        preg_match_all(self::MENTION_REGEX, $this->text, $matches, flags: PREG_OFFSET_CAPTURE);

        collect(data_get($matches, 3))
            ->each(function ($match) {
                $handle = data_get($match, 0);
                $start = data_get($match, 1);

                if (empty($handle) || ! Identity::isHandle($handle)) {
                    return;
                }

                $did = Bluesky::resolveHandle($handle)->json('did');

                if (! Identity::isDID($did)) {
                    return;
                }

                $this->facets[] = [
                    '$type' => self::TYPE,
                    'index' => [
                        'byteStart' => $start - 1,
                        'byteEnd' => $start + strlen($handle),
                    ],
                    'features' => [
                        [
                            '$type' => Facet::Mention->value,
                            'did' => $did,
                        ],
                    ],
                ];
            });
    }

    protected function link(): void
    {
        preg_match_all(self::URL_REGEX, $this->text, $matches, flags: PREG_OFFSET_CAPTURE);

        collect(data_get($matches, 2))
            ->each(function ($match) {
                $uri = data_get($match, 0);
                $start = data_get($match, 1);
                $end = $start + strlen($uri);

                if (! Str::startsWith($uri, 'http')) {
                    return;
                }

                $uri = Str::of($uri)->whenTest('/[.,;:!?]$/', function (Stringable $string) use (&$end) {
                    $end--;
                    return $string->rtrim('.,;:!?');
                })->toString();

                $uri = Str::of($uri)->whenTest('/[)]$/', function (Stringable $string) use (&$end) {
                    $end--;
                    return $string->rtrim(')');
                })->toString();

                $this->facets[] = [
                    '$type' => self::TYPE,
                    'index' => [
                        'byteStart' => $start,
                        'byteEnd' => $end,
                    ],
                    'features' => [
                        [
                            '$type' => Facet::Link->value,
                            'uri' => $uri,
                        ],
                    ],
                ];
            });
    }

    protected function tag(): void
    {
        preg_match_all(self::TAG_REGEX, $this->text, $matches, flags: PREG_OFFSET_CAPTURE);

        collect(data_get($matches, 2))
            ->each(function ($match) {
                $tag = data_get($match, 0);
                $start = data_get($match, 1) - 1;

                $tag = Str::of($tag)->replaceMatches(self::TRAILING_PUNCTUATION_REGEX, '')->toString();

                if (empty($tag) || strlen($tag) > 64) {
                    return;
                }

                $this->facets[] = [
                    '$type' => self::TYPE,
                    'index' => [
                        'byteStart' => $start,
                        'byteEnd' => $start + 1 + strlen($tag),
                    ],
                    'features' => [
                        [
                            '$type' => Facet::Tag->value,
                            'tag' => $tag,
                        ],
                    ],
                ];
            });
    }
}
