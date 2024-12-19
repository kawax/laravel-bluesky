<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Record;

use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use ReflectionClass;
use Revolution\AtProto\Lexicon\Attributes\Required;

trait HasRecord
{
    public function toArray(): array
    {
        return collect(get_object_vars($this))
            ->reject(fn ($item) => is_null($item))
            ->toArray();
    }

    public function toRecord(): array
    {
        $record = Arr::add($this->toArray(), '$type', self::NSID);

        return Arr::add($record, 'createdAt', now()->toISOString());
    }

    public function validator(): Validator
    {
        $ref = new ReflectionClass($this);

        $attrs = $ref->getAttributes(Required::class);

        if (blank($attrs)) {
            $parent = $ref->getParentClass();
            if ($parent !== false) {
                $attrs = $parent->getAttributes(Required::class);
            }
        }

        $required = $attrs[0]->getArguments()[0] ?? [];

        $rules = collect((array) $required)
            ->mapWithKeys(fn ($item) => [$item => 'required'])
            ->toArray();

        return validator($this->toRecord(), $rules);
    }
}
