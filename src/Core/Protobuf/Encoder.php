<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Core\Protobuf;

use InvalidArgumentException;
use Revolution\Bluesky\Core\CID;

/**
 * @internal
 *
 * @link https://github.com/ipld/js-dag-pb/blob/master/src/pb-encode.js
 */
final class Encoder
{
    private const MAX_INT32 = 2147483647; // 2**31 - 1

    private const MAX_UINT32 = 4294967295; // 2**32 - 1

    private const LEN8TAB = [
        0, 1, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4,
        5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5, 5,
        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6,
        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6,
        7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7,
        7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7,
        7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7,
        7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
    ];

    private const NODE = ['Data', 'Links'];

    private const LINK = ['Hash', 'Name', 'Tsize'];

    public function encodeNode(array|string $node): string
    {
        $node = $this->prepare($node);
        $this->validate($node);

        $size = $this->sizeNode($node);
        $bytes = array_fill(0, $size, 0);
        $i = $size;

        if (! empty($node['Data']) && is_array($node['Data'])) {
            $data = $node['Data'];
            $len = count($data);
            $i -= $len;
            for ($j = 0; $j < $len; $j++) {
                $bytes[$i + $j] = $data[$j];
            }
            $i = $this->encodeVarint($bytes, $i, $len);
            $i--;
            $bytes[$i] = 0x0A;
        }

        if (! empty($node['Links']) && is_array($node['Links'])) {
            for ($index = count($node['Links']) - 1; $index >= 0; $index--) {
                $link = $node['Links'][$index];
                $written = $this->encodeLink($link, $bytes, $i);
                $i -= $written;
                $i = $this->encodeVarint($bytes, $i, $written);
                $i--;
                $bytes[$i] = 0x12;
            }
        }

        return pack('C*', ...$bytes);
    }

    private function encodeLink(array $link, array &$bytes, int $i): int
    {
        $start = $i;

        if (isset($link['Tsize']) && is_int($link['Tsize'])) {
            if ($link['Tsize'] < 0) {
                throw new InvalidArgumentException('Tsize cannot be negative');
            }
            if ($link['Tsize'] > PHP_INT_MAX) {
                throw new InvalidArgumentException('Tsize too large for encoding');
            }
            $i = $this->encodeVarint($bytes, $i, $link['Tsize']);
            $i--;
            $bytes[$i] = 0x18;
        }

        if (isset($link['Name']) && is_string($link['Name'])) {
            $name = $link['Name'];
            $nameLen = strlen($name);
            $i -= $nameLen;
            for ($j = 0; $j < $nameLen; $j++) {
                $bytes[$i + $j] = ord($name[$j]);
            }
            $i = $this->encodeVarint($bytes, $i, $nameLen);
            $i--;
            $bytes[$i] = 0x12;
        }

        if (isset($link['Hash']) && is_array($link['Hash'])) {
            $hash = $link['Hash'];
            $len = count($hash);
            $i -= $len;
            for ($j = 0; $j < $len; $j++) {
                $bytes[$i + $j] = $hash[$j];
            }
            $i = $this->encodeVarint($bytes, $i, $len);
            $i--;
            $bytes[$i] = 0x0A;
        }

        return $start - $i;
    }

    private function asLink(array $link): array
    {
        $pbl = [];

        if (isset($link['Hash'])) {
            $hash = $link['Hash'];
            if (is_array($hash) && array_key_exists('/', $hash)) {
                $hash = $hash['/'];
            }
            $hash_bytes = CID::decodeBytes($hash);

            if (! empty($hash_bytes)) {
                $pbl['Hash'] = $this->encodeText($hash_bytes);
            }
        }

        if (! isset($pbl['Hash'])) {
            throw new InvalidArgumentException('Invalid DAG-PB: link must have a Hash');
        }

        if (isset($link['Name']) && is_string($link['Name'])) {
            $pbl['Name'] = $link['Name'];
        }

        if (isset($link['Tsize']) && is_int($link['Tsize'])) {
            $pbl['Tsize'] = $link['Tsize'];
        }

        return $pbl;
    }

    private function prepare(array|string $node): array
    {
        if (is_string($node)) {
            $node = ['Data' => $node];
        }

        if (! is_array($node)) {
            throw new InvalidArgumentException('Invalid DAG-PB: node must be an array');
        }

        $pbn = [];

        if (array_key_exists('Data', $node)) {
            if (is_string($node['Data'])) {
                $pbn['Data'] = $this->encodeText($node['Data']);
            } elseif (is_array($node['Data'])) {
                $pbn['Data'] = $node['Data'];
            } else {
                throw new InvalidArgumentException('Invalid DAG-PB: Data must be a string or array');
            }
        }

        if (array_key_exists('Links', $node)) {
            if (is_array($node['Links'])) {
                $pbn['Links'] = [];
                foreach ($node['Links'] as $l) {
                    $pbn['Links'][] = $this->asLink($l);
                }

                usort($pbn['Links'], [$this, 'linkComparator']);
            } else {
                throw new InvalidArgumentException('Invalid DAG-PB: Links must be an array');
            }
        } else {
            $pbn['Links'] = [];
        }

        return $pbn;
    }

    private function validate(array $node): void
    {
        if (isset($node['/']) && $node['/'] === $node['bytes']) {
            throw new InvalidArgumentException('Invalid DAG-PB form');
        }

        if (! $this->hasOnlyProperties($node, self::NODE)) {
            throw new InvalidArgumentException('Invalid DAG-PB form: extraneous properties');
        }

        if (isset($node['Data']) && ! is_array($node['Data'])) {
            throw new InvalidArgumentException('Invalid DAG-PB form: Data must be bytes');
        }

        if (! isset($node['Links']) || ! is_array($node['Links'])) {
            throw new InvalidArgumentException('Invalid DAG-PB form: Links must be a list');
        }

        for ($i = 0; $i < count($node['Links']); $i++) {
            $link = $node['Links'][$i];

            $this->validateLink($link);

            if ($i > 0 && $this->linkComparator($link, $node['Links'][$i - 1]) === -1) {
                throw new InvalidArgumentException('Invalid DAG-PB form: links must be sorted by Name bytes');
            }
        }
    }

    private function validateLink(array $link): void
    {
        if (isset($link['/']) && $link['/'] === $link['bytes']) {
            throw new InvalidArgumentException('Invalid DAG-PB form: bad link');
        }

        if (! $this->hasOnlyProperties($link, self::LINK)) {
            throw new InvalidArgumentException('Invalid DAG-PB form: extraneous properties on link');
        }

        if (! isset($link['Hash'])) {
            throw new InvalidArgumentException('Invalid DAG-PB form: link must have a Hash');
        }

        if (isset($link['Name']) && ! is_string($link['Name'])) {
            throw new InvalidArgumentException('Invalid DAG-PB form: link Name must be a string');
        }

        if (isset($link['Tsize'])) {
            if (! is_int($link['Tsize'])) {
                throw new InvalidArgumentException('Invalid DAG-PB form: link Tsize must be an integer');
            }
            if ($link['Tsize'] < 0) {
                throw new InvalidArgumentException('Invalid DAG-PB form: link Tsize cannot be negative');
            }
        }
    }

    private function sizeNode(array $node): int
    {
        $n = 0;
        if (! empty($node['Data']) && is_array($node['Data'])) {
            $l = count($node['Data']);
            $n += 1 + $l + $this->sov($l);
        }

        if (! empty($node['Links']) && is_array($node['Links'])) {
            foreach ($node['Links'] as $link) {
                $l = $this->sizeLink($link);
                $n += 1 + $l + $this->sov($l);
            }
        }

        return $n;
    }

    private function sizeLink(array $link): int
    {
        $n = 0;

        if (isset($link['Hash']) && is_array($link['Hash'])) {
            $l = count($link['Hash']);
            $n += 1 + $l + $this->sov($l);
        }

        if (isset($link['Name']) && is_string($link['Name'])) {
            $l = strlen($link['Name']);
            $n += 1 + $l + $this->sov($l);
        }

        if (isset($link['Tsize']) && is_int($link['Tsize'])) {
            $n += 1 + $this->sov($link['Tsize']);
        }

        return $n;
    }

    private function encodeVarint(array &$bytes, int $offset, int $v): int
    {
        $size = $this->sov($v);
        $offset -= $size;
        $base = $offset;
        $val = $v;

        while ($val >= self::MAX_INT32) {
            $bytes[$offset++] = ($val & 0x7F) | 0x80;
            $val = intdiv($val, 128);
        }

        while ($val >= 128) {
            $bytes[$offset++] = ($val & 0x7F) | 0x80;
            $val >>= 7;
        }

        $bytes[$offset] = $val & 0xFF;

        return $base;
    }

    private function sov(int $x): int
    {
        if ($x % 2 === 0) {
            $x++;
        }

        return (int) floor(($this->len64($x) + 6) / 7);
    }

    private function len64(int $x): int
    {
        $n = 0;
        $val = $x;
        if ($val >= self::MAX_UINT32) {
            $val = (int) ($val / self::MAX_UINT32);
            $n = 32;
        }
        if ($val >= (1 << 16)) {
            $val >>= 16;
            $n += 16;
        }
        if ($val >= (1 << 8)) {
            $val >>= 8;
            $n += 8;
        }
        $n += self::LEN8TAB[$val];

        return $n;
    }

    private function encodeText(string $str): array
    {
        return array_map('ord', str_split($str));
    }

    private function linkComparator(array $a, array $b): int
    {
        if ($a === $b) {
            return 0;
        }

        $abuf = isset($a['Name']) && is_string($a['Name']) ? $this->encodeText($a['Name']) : [];
        $bbuf = isset($b['Name']) && is_string($b['Name']) ? $this->encodeText($b['Name']) : [];

        $x = count($abuf);
        $y = count($bbuf);

        $len = min($x, $y);
        for ($i = 0; $i < $len; $i++) {
            if ($abuf[$i] !== $bbuf[$i]) {
                $x = $abuf[$i];
                $y = $bbuf[$i];
                break;
            }
        }

        return $x < $y ? -1 : ($y < $x ? 1 : 0);
    }

    private function hasOnlyProperties(array $node, array $properties): bool
    {
        foreach (array_keys($node) as $p) {
            if (! in_array($p, $properties, true)) {
                return false;
            }
        }

        return true;
    }
}
