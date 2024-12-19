<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

/**
 * Timestamp ID.
 *
 * @link https://atproto.com/specs/tid
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/common-web/src/tid.ts
 * @link https://github.com/bluesky-social/atproto/blob/main/packages/common-web/src/util.ts
 */
final class TID implements Stringable
{
    protected const TID_LEN = 13;

    protected const S32_CHAR = '234567abcdefghijklmnopqrstuvwxyz';

    public const FORMAT = '/^[234567abcdefghij][234567abcdefghijklmnopqrstuvwxyz]{12}$/';

    protected static int $lastTimestamp = 0;

    protected static ?int $clockId = null;

    public function __construct(protected string $str)
    {
        if (! self::is($this->str)) {
            throw new InvalidArgumentException('Invalid TID format');
        }
    }

    /**
     * Create new TID.
     */
    public static function next(?self $prev = null): self
    {
        // microsecond precision
        // 1234567899123456
        $timestamp = (int) Carbon::now()->getPreciseTimestamp();

        if ($timestamp === self::$lastTimestamp) {
            $timestamp++;
        }

        self::$lastTimestamp = $timestamp;

        if (is_null(self::$clockId)) {
            self::$clockId = random_int(0, 31);
        }

        $tid = self::fromTime($timestamp, self::$clockId);

        if (is_null($prev) || $tid->newerThen($prev)) {
            return $tid;
        }

        return self::fromTime($prev->timestamp() + 1, self::$clockId);
    }

    /**
     * Create new TID as string.
     *
     * ```
     * $rkey = TID::nextStr();
     * ```
     *
     * As seen in the Statusphere tutorial, we use this method to create a new time-based record key.
     *
     * @link https://atproto.com/guides/applications
     */
    public static function nextStr(?string $prev = null): string
    {
        return self::next(self::is($prev) ? new self($prev ?? '') : null)->toString();
    }

    /**
     * @param  int|float  $timestamp  microsecond timestamp
     * @param  int  $clockId  random clock id
     */
    public static function fromTime(int|float $timestamp, int $clockId): self
    {
        $str = self::s32encode($timestamp).Str::padLeft(self::s32encode($clockId), length: 2, pad: '2');

        return new self($str);
    }

    public static function fromStr(string $str): self
    {
        return new self($str);
    }

    public static function is(?string $str): bool
    {
        if (is_null($str)) {
            return false;
        }

        return (Str::length($str) === self::TID_LEN) && Str::of($str)->test(self::FORMAT);
    }

    /**
     * microsecond timestamp.
     *
     * ```
     * 1234567899123456
     * ```
     */
    public function timestamp(): int
    {
        return self::s32decode(Str::substr($this->str, start: 0, length: 11));
    }

    public function clockId(): int
    {
        return self::s32decode(Str::substr($this->str, start: 11, length: 13));
    }

    /**
     * Get timestamp as Carbon.
     */
    public function toDate(): Carbon
    {
        // Pass the timestamp divided by 1000000.
        // 1234567899.123456
        return Carbon::createFromTimestamp($this->timestamp() / 1_000_000);
    }

    public function compareTo(TID $other): int
    {
        return match (true) {
            $this->str > $other->str => 1,
            $this->str < $other->str => -1,
            default => 0,
        };
    }

    public function equals(TID $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function newerThen(TID $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function olderThen(TID $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * encode base32-sortable.
     */
    public static function s32encode(int|float $i): string
    {
        $s = '';

        while ($i) {
            $c = $i % 32;
            $i = floor($i / 32);
            $s = Str::charAt(self::S32_CHAR, $c).$s;
        }

        return $s;
    }

    /**
     * decode base32-sortable.
     */
    public static function s32decode(string $s): int
    {
        $i = 0;

        foreach (str_split($s) as $c) {
            $i = $i * 32 + strpos(self::S32_CHAR, $c);
        }

        return $i;
    }

    public function toString(): string
    {
        return $this->str;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
