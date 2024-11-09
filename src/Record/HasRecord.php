<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Support\Arr;

trait HasRecord
{
    public function toArray(): array
    {
        return collect(get_object_vars($this))
            ->reject(fn ($item) => blank($item))
            ->toArray();
    }

    public function toRecord(): array
    {
        $record = Arr::add($this->toArray(), '$type', self::NSID);

        return Arr::add($record, 'createdAt', now()->toISOString());
    }
}
