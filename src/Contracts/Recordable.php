<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts;

interface Recordable
{
    public function toRecord(): array;
}
