<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CBOR\AtBytes;
use Revolution\Bluesky\Crypto\K256;
use Revolution\Bluesky\Crypto\Signature;
use Revolution\Bluesky\FeedGenerator\ValidateAuth;
use RuntimeException;

final class Labeler
{
    public const VERSION = 1;

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
     * @return iterable<null|SubscribeLabelMessage>
     *
     * @throws LabelerException
     */
    public static function subscribeLabels(?int $cursor): iterable
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        if (! $labeler instanceof AbstractLabeler) {
            throw new LabelerException('Labeler class not found');
        }

        yield from $labeler->subscribeLabels($cursor);
    }

    /**
     * @return iterable<UnsignedLabel>
     *
     * @throws LabelerException
     */
    public static function emitEvent(Request $request, ?string $token): iterable
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        $user = self::verifyJWT($request, $token);

        if (empty($user) || $user !== Config::string('bluesky.labeler.did')) {
            throw new LabelerException('Invalid JWT');
        }

        yield from $labeler->emitEvent($request);
    }

    private static function verifyJWT(Request $request, ?string $token): ?string
    {
        return app()->call(ValidateAuth::class, ['jwt' => $token, 'request' => $request]);
    }

    public static function saveLabel(SignedLabel $label, string $sign): SavedLabel
    {
        /** @var AbstractLabeler $labeler */
        $labeler = app(self::$labeler);

        return $labeler->saveLabel($label, $sign);
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

    /**
     * ```
     * [$signed, $sign] = Labeler::signLabel($unsigned);
     *
     * $signed
     * SignedLabel
     *
     * $sign
     * raw bytes
     * ```
     *
     * @return array{0: SignedLabel, 1: string}
     */
    public static function signLabel(UnsignedLabel $unsigned): array
    {
        if (isset(self::$labeler)) {
            /** @var AbstractLabeler $labeler */
            $labeler = app(self::$labeler);
            if (method_exists($labeler, 'signLabel')) {
                return $labeler->signLabel($unsigned);
            }
        }

        $label = $unsigned->toArray();

        $label = Arr::add($label, 'ver', self::VERSION);

        if (Arr::get($label, 'neg') === true) {
            $label['neg'] = true;
        } else {
            Arr::forget($label, 'neg');
        }

        $bytes = CBOR::encode($label);

        $key = Config::string('bluesky.labeler.private_key');

        if (empty($key)) {
            throw new RuntimeException('Private key for Labeler is required.');
        }

        $sign = K256::load($key)->privateKey()->sign($bytes);
        $sign = Signature::toCompact($sign);

        $label = Arr::add($label, 'sig', new AtBytes($sign));
        $label = SignedLabel::fromArray($label);

        return [$label, $sign];
    }
}
