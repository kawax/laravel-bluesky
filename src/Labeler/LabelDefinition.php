<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\AtProto\Lexicon\Attributes\KnownValues;

/**
 * Declares a label value and its expected interpretations and behaviors.
 */
final readonly class LabelDefinition implements Arrayable
{
    /**
     * @param  array<LabelLocale>  $locales
     */
    public function __construct(
        private string  $identifier,
        #[KnownValues(['inform', 'alert', 'none'])]
        private string  $severity,
        #[KnownValues(['content', 'media', 'none'])]
        private string  $blurs,
        private array   $locales,
        #[KnownValues(['ignore', 'warn', 'hide'])]
        private ?string $defaultSetting = 'warn',
        private ?bool   $adultOnly = null,
    ) {
    }

    public static function make(
        string  $identifier,
        string  $severity,
        string  $blurs,
        array   $locales,
        ?string $defaultSetting = null,
        ?bool   $adultOnly = null,
    ): self {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        return collect(get_object_vars($this))
            ->reject(fn ($item) => is_null($item))
            ->toArray();
    }
}
