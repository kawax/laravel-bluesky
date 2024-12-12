<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use CBOR\CBORObject;
use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Illuminate\Support\Arr;
use InvalidArgumentException;

final class CBOR
{
    public static function fromArray(array $data): CBORObject
    {
        if (Arr::isAssoc($data)) {
            $cbor = MapObject::create();
        } elseif (Arr::isList($data)) {
            $cbor = ListObject::create();
        } else {
            throw new InvalidArgumentException();
        }

        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $name = TextStringObject::create($key);
            } else {
                unset($name);
            }

            if (is_string($value)) {
                $val = TextStringObject::create($value);
            } elseif (is_int($value)) {
                $val = UnsignedIntegerObject::create($value);
            } elseif (is_array($value)) {
                $val = self::fromArray($value);
            }

            if (isset($val)) {
                if (isset($name)) {
                    $cbor->add($name, $val);
                } else {
                    $cbor->add($val);
                }
            }
        }

        return $cbor;
    }

    public static function decode(string $data): CBORObject
    {
        $decoder = Decoder::create();

        return $decoder->decode(StringStream::create($data));
    }
}
