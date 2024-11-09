<?php

namespace Revolution\Bluesky\Contracts;

interface Recordable
{
    public function toRecord(): array;
}
