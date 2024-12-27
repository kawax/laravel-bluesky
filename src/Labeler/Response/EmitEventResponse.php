<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler\Response;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

/**
 * @internal
 */
final readonly class EmitEventResponse implements Arrayable, Jsonable
{
    public function __construct(
        public int $id,
        public array $event,
        public array $subject,
        protected string $createdBy,
        protected array $subjectBlobCids = [],
        protected ?string $createdAt = null,
        protected ?string $creatorHandle = null,
        protected ?string $subjectHandle = null,
    ) {
    }

    /**
     * @return array{id: int, event: array, subject: array, subjectBlobCids: array, createdBy: string, createdAt: string, creatorHandle?: string, subjectHandle?: string}
     */
    public function toArray(): array
    {
        $arr = collect(get_object_vars($this))
            ->reject(fn ($value) => is_null($value))
            ->toArray();

        return Arr::add($arr, 'createdAt', now()->toISOString());
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
