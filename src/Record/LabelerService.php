<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Revolution\AtProto\Lexicon\Record\App\Bsky\Labeler\AbstractService;
use Revolution\Bluesky\Contracts\Recordable;

final class LabelerService extends AbstractService implements Arrayable, Recordable
{
    use HasRecord;
    use Macroable;
    use Conditionable;
    use Tappable;

    public static function fromArray(Collection|array $service): self
    {
        $profile = Collection::make($service);

        $self = new self();

        $profile->each(function ($value, $name) use ($self) {
            if (property_exists($self, $name)) {
                $self->$name = $value;
            }
        });

        return $self;
    }

    public function policies(array $policies): self
    {
        $this->policies = $policies;

        return $this;
    }

    public function labels(?array $labels = null): self
    {
        $this->labels = $labels;

        return $this;
    }
}
