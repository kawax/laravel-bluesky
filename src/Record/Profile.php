<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Actor\AbstractProfile;
use Revolution\Bluesky\Contracts\Recordable;
use Revolution\Bluesky\Types\BlobRef;
use Revolution\Bluesky\Types\SelfLabels;
use Revolution\Bluesky\Types\StrongRef;

final class Profile extends AbstractProfile implements Arrayable, Recordable
{
    use HasRecord;
    use Macroable;
    use Conditionable;
    use Tappable;

    public static function fromArray(Collection|array $profile): self
    {
        $profile = Collection::make($profile);

        $self = new self();

        $profile->each(function ($value, $name) use ($self) {
            if (property_exists($self, $name)) {
                $self->$name = $value;
            }
        });

        return $self;
    }

    public function displayName(?string $displayName = null): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Free-form profile description text.
     */
    public function description(?string $description = null): self
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
     * @param  BlobRef|array|callable|null  $banner
     */
    public function banner(null|BlobRef|array|callable $banner = null): self
    {
        if (is_null($banner)) {
            $this->banner = $banner;

            return $this;
        }

        if (is_callable($banner)) {
            $banner = call_user_func($banner);
        }

        if ($banner instanceof BlobRef) {
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
    public function labels(?SelfLabels $labels = null): self
    {
        $this->labels = $labels?->toArray();

        return $this;
    }

    public function joinedViaStarterPack(?StrongRef $joinedViaStarterPack = null): self
    {
        $this->joinedViaStarterPack = $joinedViaStarterPack?->toArray();

        return $this;
    }

    /**
     * pinnedPost.
     *
     * ```
     * use Revolution\Bluesky\Types\StrongRef;
     *
     * $profile->pinnedPost(StrongRef::to(uri: 'at://', cid: ''));
     * ```
     */
    public function pinnedPost(?StrongRef $pinnedPost = null): self
    {
        $this->pinnedPost = $pinnedPost?->toArray();

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
