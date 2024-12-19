<?php

declare(strict_types=1);

namespace Revolution\Bluesky\RichText;

use Illuminate\Support\Str;
use Revolution\AtProto\Lexicon\Enum\Facet;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\Identity;

/**
 * @internal
 *
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/api/src/rich-text/detection.ts
 */
final class DetectFacets
{
    protected const MENTION_REGEX = '/(^|\s|\()(@)([a-zA-Z0-9.-]+)(\b)/';

    protected const URL_REGEX = '/(^|\s|\()((https?:\/\/[\S]+)|((?<domain>[a-z][a-z0-9]*(\.[a-z0-9]+)+)[\S]*))/i';

    protected const TRAILING_PUNCTUATION_REGEX = '/\p{P}+$/u';

    protected const TAG_REGEX = '/(^|\s)([#＃])((?!\x{fe0f})[^\s\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]*[^\d\s\p{P}\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]+[^\s\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]*)?/u';

    protected string $text;

    protected array $facets = [];

    public function __invoke(string $text): array
    {
        $this->text = $text;

        $this->mention();
        $this->link();
        $this->tag();

        return $this->facets;
    }

    protected function mention(): void
    {
        preg_match_all(self::MENTION_REGEX, $this->text, $matches, flags: PREG_OFFSET_CAPTURE);

        collect((array) data_get($matches, 3))
            ->each(function ($match) {
                /** @var string $handle */
                $handle = data_get($match, 0);
                /** @var int $start */
                $start = data_get($match, 1);

                if (empty($handle) || ! Identity::isHandle($handle)) {
                    return;
                }

                $did = Bluesky::resolveHandle($handle)->json('did');

                if (! Identity::isDID($did)) {
                    return;
                }

                $this->facets[] = [
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

        collect((array) data_get($matches, 2))
            ->each(function ($match) {
                /** @var string $uri */
                $uri = data_get($match, 0);
                /** @var int $start */
                $start = data_get($match, 1);
                $end = $start + strlen($uri);

                if (! Str::startsWith($uri, 'http') && Str::isUrl('https://'.$uri, ['https'])) {
                    $uri = 'https://'.$uri;
                }

                if (Str::of($uri)->test('/[.,;:!?]$/')) {
                    $uri = Str::rtrim($uri, '.,;:!?');
                    $end = $start + strlen($uri);
                }

                if (Str::of($uri)->test('/[)]$/') && Str::doesntContain($uri, '(')) {
                    $uri = Str::rtrim($uri, ')');
                    $end = $start + strlen($uri);
                }

                $this->facets[] = [
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

        collect((array) data_get($matches, 3))
            ->each(function ($match, $index) use ($matches) {
                // # or ＃
                $leading = data_get($matches, "2.$index.0");

                $tag = data_get($match, 0);
                /** @var int $start */
                $start = data_get($match, 1);

                $tag = Str::of($tag)
                    ->trim()
                    ->replaceMatches(self::TRAILING_PUNCTUATION_REGEX, '')
                    ->toString();

                if (empty($tag) || strlen($tag) > 64) {
                    return;
                }

                $this->facets[] = [
                    'index' => [
                        'byteStart' => $start - strlen($leading),
                        'byteEnd' => $start + strlen($tag),
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
