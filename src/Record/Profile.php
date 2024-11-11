<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Actor\AbstractProfile;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\Types\Blob;
use Revolution\Bluesky\Types\SelfLabels;
use Revolution\Bluesky\Types\StrongRef;

class Profile extends AbstractProfile implements Arrayable, Recordable
{
    use HasRecord;

    public static function fromArray(Collection|array $profile): static
    {
        $profile = Collection::make($profile);

        $self = new static();

        $profile->each(function ($value, $name) use ($self) {
            if (property_exists($self, $name)) {
                $self->$name = $value;
            }
        });

        return $self;
    }

    public function displayName(?string $displayName = null): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Free-form profile description text.
     */
    public function description(?string $description = null): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Small image to be displayed next to posts from account. AKA, 'profile picture'.
     *
     * accept: ['image/png', 'image/jpeg']
     * maxSize: 1000000
     *
     * ```
     * $profile->avatar(function (): array {
     *     return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     * })
     * ```
     *
     * @param  Blob|array|callable|null  $avatar
     */
    public function avatar(null|Blob|array|callable $avatar = null): static
    {
        if (is_null($avatar)) {
            return $this;
        }

        if (is_callable($avatar)) {
            $avatar = call_user_func($avatar);
        }

        if ($avatar instanceof Blob) {
            $avatar = $avatar->toArray();
        }

        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Larger horizontal image to display behind profile view.
     *
     * accept: ['image/png', 'image/jpeg']
     * maxSize: 1000000
     *
     * ```
     * $profile->banner(function (): array {
     *     return Bluesky::uploadBlob(Storage::get('test.png'), Storage::mimeType('test.png'))->json('blob');
     * })
     * ```
     *
     * @param  Blob|array|callable|null  $banner
     */
    public function banner(null|Blob|array|callable $banner = null): static
    {
        if (is_null($banner)) {
            return $this;
        }

        if (is_callable($banner)) {
            $banner = call_user_func($banner);
        }

        if ($banner instanceof Blob) {
            $banner = $banner->toArray();
        }

        $this->banner = $banner;

        return $this;
    }

    /**
     * Self-label values, specific to the Bluesky application, on the overall account.
     *
     * ```
     * use Revolution\Bluesky\Types\SelfLabels;
     *
     * $labels = SelfLabels::make(['!no-unauthenticated']);
     * $profile->labels($labels);
     * ```
     */
    public function labels(?SelfLabels $labels = null): static
    {
        $this->labels = $labels?->toArray();

        return $this;
    }

    public function joinedViaStarterPack(?StrongRef $joinedViaStarterPack = null): static
    {
        $this->joinedViaStarterPack = $joinedViaStarterPack?->toArray();

        return $this;
    }

    /**
     * ```
     * use Revolution\Bluesky\Types\StrongRef;
     *
     * $profile->pinnedPost(StrongRef::to(uri: 'at://', cid: ''));
     * ```
     */
    public function pinnedPost(?StrongRef $pinnedPost = null): static
    {
        $this->pinnedPost = $pinnedPost?->toArray();

        return $this;
    }
}