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

    protected const URL_REGEX =
        '/(^|\s|\()((https?:\/\/[\S]+)|((?<domain>[a-z][a-z0-9]*(\.[a-z0-9]+)+)[\S]*))/i';

    protected const TRAILING_PUNCTUATION_REGEX = '/\p{P}+$/u';

    // Since the same regular expressions as in JS cannot be used, the behavior may be different.
    protected const TAG_REGEX = '/(^|\s)#(\S*[^\d\s\p{P}]+\S*)?/i';
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
        $start = 0;

        while (preg_match(self::MENTION_REGEX, $this->text, $matches, offset: $start) !== 0) {
            $handle = data_get($matches, 3);

            if (empty($handle)) {
                break;
            }

            $start = strpos($this->text, $handle, offset: $start + 1) - 1;

            if (! Identity::isHandle($handle)) {
                $start++;
                continue;
            }

            $did = Bluesky::resolveHandle($handle)->json('did');

            if (! Identity::isDID($did)) {
                $start++;
                continue;
            }

            $this->facets[] = [
                '$type' => self::TYPE,
                'index' => [
                    'byteStart' => $start,
                    'byteEnd' => $start + strlen($handle) + 1,
                ],
                'features' => [
                    [
                        '$type' => Facet::Mention->value,
                        'did' => $did,
                    ],
                ],
            ];

            $start++;
        }
    }

    protected function link(): void
    {
        $start = 0;

        while (preg_match(self::URL_REGEX, $this->text, $matches, offset: $start) !== 0) {
            $uri = data_get($matches, 2);

            if (empty($uri)) {
                break;
            }

            $start = strpos($this->text, $uri, offset: $start);
            $end = $start + strlen($uri);

            if (! Str::startsWith($uri, 'http')) {
                $start++;
                continue;
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

            $start++;
        }
    }

    protected function tag(): void
    {
        $start = 0;

        while (preg_match(self::TAG_REGEX, $this->text, $matches, offset: $start) !== 0) {
            $tag = data_get($matches, 2);

            $tag = Str::of($tag)->replaceMatches(self::TRAILING_PUNCTUATION_REGEX, '')->toString();

            if (empty($tag) || strlen($tag) > 64) {
                $start++;
                continue;
            }

            $start = strpos($this->text, '#'.$tag, offset: $start);

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

            $start++;
        }
    }
}
