<?php

namespace Revolution\Bluesky\Record;

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
        return collect($this->toArray())
            ->put('$type', self::NSID)
            ->put('createdAt', now()->toISOString())
            ->reject(fn ($item) => blank($item))
            ->toArray();
    }
}
