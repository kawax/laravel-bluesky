<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Http\Request;
use InvalidArgumentException;

final class Labeler
{
    protected static string $labeler;

    /**
     * Only one Labeler can be registered.
     *
     * @param  class-string|AbstractLabeler  $labeler
     */
    public static function register(string|AbstractLabeler $labeler): void
    {
        if (is_string($labeler) && class_exists($labeler)) {
            $labeler = app($labeler);
        }

        if (! $labeler instanceof AbstractLabeler) {
            throw new InvalidArgumentException('Labeler must be an instance of AbstractLabeler');
        }

        self::$labeler = $labeler::class;
    }

    public static function getLabelDefinitions(): array
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        return collect($labeler->definitions())->toArray();
    }

    public static function queryLabels(Request $request): array
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        $request->mergeIfMissing([
            'uriPatterns' => [],
        ]);

        return $labeler->queryLabels(...$request->all());
    }

    public static function createReport(Request $request): array
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        return $labeler->createReport(...$request->all());
    }
}
