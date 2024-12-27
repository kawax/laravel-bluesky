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
        private string $identifier,
        private array $locales,
        #[KnownValues(['inform', 'alert', 'none'])]
        private string $severity = 'inform',
        #[KnownValues(['content', 'media', 'none'])]
        private string $blurs = 'none',
        #[KnownValues(['ignore', 'warn', 'hide'])]
        private string $defaultSetting = 'warn',
        private bool $adultOnly = false,
    ) {
    }

    public static function make(
        string $identifier,
        array $locales,
        string $severity = 'inform',
        string $blurs = 'none',
        string $defaultSetting = 'warn',
        bool $adultOnly = false,
    ): self {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        $locales = collect($this->locales)->toArray();

        return collect(get_object_vars($this))
            ->put('locales', $locales)
            ->reject(fn ($item) => is_null($item))
            ->toArray();
    }
}
