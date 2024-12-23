<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Crypto\K256;
use Revolution\Bluesky\FeedGenerator\ValidateAuth;
use RuntimeException;

final class Labeler
{
    protected const VERSION = 1;

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

        return collect($labeler->labels())->toArray();
    }

    /**
     * @return iterable<null|LabelMessage>
     * @throws LabelerException
     */
    public static function subscribeLabels(?int $cursor): iterable
    {
        /** @var ?AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        if (! $labeler instanceof AbstractLabeler) {
            throw new LabelerException('Labeler class not found');
        }

        yield from $labeler->subscribeLabels($cursor);
    }

    /**
     * @throws LabelerException
     */
    public static function emitEvent(Request $request): ?EmitEventResponse
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        $user = self::verifyJWT($request);

        if (empty($user)) {
            throw new LabelerException('Invalid JWT');
        }

        return $labeler->emitEvent($request, $user);
    }

    private static function verifyJWT(Request $request): ?string
    {
        return app()->call(ValidateAuth::class, ['jwt' => $request->bearerToken(), 'request' => $request]);
    }

    public static function queryLabels(Request $request): array
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        return $labeler->queryLabels($request);
    }

    public static function createReport(Request $request): array
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        return $labeler->createReport($request);
    }

    public static function signLabel(array $label): array
    {
        if (filled(Arr::get($label, 'sig'))) {
            return $label;
        }

        $label = Arr::add($label, 'ver', self::VERSION);

        $label['neg'] = Arr::get($label, 'neg');

        $bytes = CBOR::encode($label);

        $key = Config::string('bluesky.labeler.private_key');

        if (empty($key)) {
            throw new RuntimeException('Private key for Labeler is required.');
        }

        $sign = K256::load($key)->privateKey()->sign($bytes);

        return Arr::add($label, 'sig', $sign);
    }
}
