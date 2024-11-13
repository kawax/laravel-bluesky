<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Revolution\AtProto\Lexicon\Attributes\KnownValues;
use Revolution\AtProto\Lexicon\Enum\ListPurpose;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Graph\AbstractList;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\Types\BlobRef;
use Revolution\Bluesky\Types\SelfLabels;

use function Illuminate\Support\enum_value;

final class UserList extends AbstractList implements Arrayable, Recordable
{
    use HasRecord;

    public static function create(): self
    {
        return new self();
    }

    public static function fromArray(Collection|array $list): self
    {
        $list = Collection::make($list);

        $self = new self();

        $list->each(function ($value, $name) use ($self) {
            if (property_exists($self, $name)) {
                $self->$name = $value;
            }
        });

        return $self;
    }

    /**
     * Defines the purpose of the list (aka, moderation-oriented or curration-oriented).
     */
    public function purpose(#[KnownValues([ListPurpose::Modlist, ListPurpose::Curatelist, ListPurpose::Referencelist])] BackedEnum|string $purpose): self
    {
        $this->purpose = enum_value($purpose);

        return $this;
    }

    /**
     * Display name for list; can not be empty.
     */
    public function name(string $name): self
    {
        $this->name = $name;

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

    /**
     * accept: ['image/png', 'image/jpeg']
     * maxSize: 1000000
     *
     * ```
     * $list->avatar(function (): array {
     *     return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     * })
     * ```
     *
     * @param  BlobRef|array|callable|null  $avatar
     */
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

    /**
     * ```
     * use Revolution\Bluesky\Types\SelfLabels;
     *
     * $labels = SelfLabels::make(['!no-unauthenticated']);
     * $list->labels($labels);
     * ```
     */
    public function labels(?SelfLabels $labels = null): self
    {
        $this->labels = $labels?->toArray();

        return $this;
    }
}
