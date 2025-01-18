<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Feed\AbstractGenerator;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\Types\BlobRef;
use Revolution\Bluesky\Types\SelfLabels;

final class Generator extends AbstractGenerator implements Arrayable, Recordable
{
    use HasRecord;
    use Macroable;
    use Conditionable;
    use Tappable;

    public function __construct(string $did, string $displayName)
    {
        $this->did = $did;
        $this->displayName = $displayName;
    }

    public static function create(string $did, string $displayName): self
    {
        return new self($did, $displayName);
    }

    public function did(string $did): self
    {
        $this->did = $did;

        return $this;
    }

    public function displayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function description(?string $description = null): self
    {
        $this->description = $description;

        return $this;
    }

    public function descriptionFacets(?array $descriptionFacets = null): self
    {
        $this->descriptionFacets = $descriptionFacets;

        return $this;
    }

    public function avatar(null|BlobRef|array|callable $avatar = null): self
    {
        if (is_null($avatar)) {
            $this->avatar = $avatar;

            return $this;
        }

        if (is_callable($avatar)) {
            $avatar = call_user_func($avatar);
        }

        if ($avatar instanceof BlobRef) {
            $avatar = $avatar->toArray();
        }

        $this->avatar = $avatar;

        return $this;
    }

    public function labels(?SelfLabels $labels = null): self
    {
        $this->labels = $labels?->toArray();

        return $this;
    }

    public function contentMode(?string $contentMode = null): self
    {
        $this->contentMode = $contentMode;

        return $this;
    }
}
