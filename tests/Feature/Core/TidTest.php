<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use InvalidArgumentException;
use Revolution\Bluesky\Core\TID;
use Tests\TestCase;

class TidTest extends TestCase
{
    public function test_tid_encode(): void
    {
        $time = (int) now()->getPreciseTimestamp();
        $encode = TID::s32encode($time);
        $decode = TID::s32decode($encode);

        $this->assertSame($time, $decode);
    }

    public function test_tid_next(): void
    {
        $this->freezeTime(function () {
            $tid = TID::next();
            $tid2 = TID::next();

            $this->assertMatchesRegularExpression(TID::FORMAT, $tid->toString());
            $this->assertTrue($tid->olderThen($tid2));
            $this->assertTrue($tid2->newerThen($tid));
        });
    }

    public function test_tid_next_str(): void
    {
        $tid = TID::nextStr();

        $this->assertSame(13, strlen($tid));
        $this->assertMatchesRegularExpression(TID::FORMAT, $tid);
    }

    public function test_tid_from_str(): void
    {
        $tid_str = TID::nextStr();
        $tid = TID::fromStr($tid_str);

        $this->assertMatchesRegularExpression(TID::FORMAT, $tid->toString());
        $this->assertSame($tid_str, $tid->toString());
        $this->assertSame($tid_str, (string) $tid);
    }

    public function test_tid_from_time(): void
    {
        $time = (int) now()->getPreciseTimestamp();
        $clockId = 31;
        $tid = TID::fromTime($time, $clockId);

        $this->assertSame($time, $tid->timestamp());
        $this->assertSame($clockId, $tid->clockId());
    }

    public function test_tid_equals(): void
    {
        $tid = TID::next();
        $tid2 = clone $tid;

        $this->assertTrue($tid->equals($tid2));
    }

    public function test_tid_date(): void
    {
        $time = now();
        $tid = TID::fromTime($time->getPreciseTimestamp(), 1);

        $decode = TID::fromStr($tid->toString())->toDate();

        $this->assertTrue($time->eq($decode));
    }

    public function test_tid_prev(): void
    {
        $this->travel(1)->hour();
        $prev = TID::next();

        $this->travelBack();
        $tid = TID::next($prev);

        $this->assertTrue($tid->newerThen($prev));
        $this->assertSame($tid->timestamp(), $prev->timestamp() + 1);
    }

    public function test_tid_prev_str(): void
    {
        $this->freezeTime(function () {
            $prev = TID::nextStr();
            $tid = TID::nextStr($prev);

            $this->assertTrue($tid > $prev);
        });
    }

    public function test_tid_invalid_len(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $tid = TID::fromStr('invalid');
    }

    public function test_tid_invalid_match(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $tid = TID::fromStr('0000000000000');
    }

    public function test_tid_is(): void
    {
        $this->assertTrue(TID::is('3jzfcijpj2z2a'));
        $this->assertTrue(TID::is('7777777777777'));
        $this->assertTrue(TID::is('3zzzzzzzzzzzz'));

        $this->assertFalse(TID::is('3jzfcijpj2z21'));
        $this->assertFalse(TID::is('0000000000000'));
        $this->assertFalse(TID::is('3jzfcijpj2z2aa'));
        $this->assertFalse(TID::is('3jzfcijpj2z2'));
        $this->assertFalse(TID::is('3jzf-cij-pj2z-2a'));
        $this->assertFalse(TID::is('zzzzzzzzzzzzz'));
        $this->assertFalse(TID::is('kjzfcijpj2z2a'));
    }

    public function test_tid_parse(): void
    {
        $tid = TID::fromStr('3jt6walwmos2y');

        $this->assertSame(1681321002683032, $tid->timestamp());
        $this->assertSame(30, $tid->clockId());
        $this->assertSame('3jt6walwmos2y', TID::fromTime($tid->timestamp(), $tid->clockId())->toString());
    }
}
