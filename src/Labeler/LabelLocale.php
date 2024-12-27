<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Strings which describe the label in the UI, localized into a specific language.
 */
final readonly class LabelLocale implements Arrayable
{
    public function __construct(
        private string $lang,
        private string $name,
        private string $description,
    ) {
    }

    public static function make(
        string $lang,
        string $name,
        string $description,
    ): self {
        return new self(...func_get_args());
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
