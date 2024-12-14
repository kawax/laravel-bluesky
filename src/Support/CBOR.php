<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Support;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\Decoder;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\IndefiniteLengthListObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\IndefiniteLengthTextStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\TrueObject;
use CBOR\StringStream;
use CBOR\Tag\GenericTag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Illuminate\Support\Str;
use InvalidArgumentException;
use YOCLIB\Multiformats\Multibase\Multibase;

final class CBOR
{
    //public const FLOAT_FORMAT_SINGLE_PRECISION = 0b0000001;

    public const INDEFINITE_BYTE_STRING_LENGTH = 0b00000010;

    public const INDEFINITE_TEXT_STRING_LENGTH = 0b00000100;

    public const INDEFINITE_LIST_LENGTH = 0b00001000;

    public const INDEFINITE_MAP_LENGTH = 0b00010000;

    public static function fromArray(array $data): string
    {
        return (new self())->encode($data);
    }

    public static function decode(string $data): CBORObject
    {
        $decoder = Decoder::create();

        return $decoder->decode(StringStream::create($data));
    }

    /**
     * @todo
     */
    public static function normalize(mixed $data): mixed
    {
        if (is_array($data)) {
            return collect($data)->map(function ($item) {
                return self::normalize($item);
            })->toArray();
        }

        if ($data instanceof GenericTag) {
            $cid = $data->getValue();
            if ($cid instanceof ByteStringObject) {
                $cid = $cid->normalize();
                $cid = Str::ltrim($cid, "\x00");

                return Multibase::encode(Multibase::BASE32, $cid);
            }

            dump($data);
        }

        return $data;
    }

    /**
     * Temporarily copied from cbor-php.
     * Replace after 4.0 release.
     *
     * @link https://github.com/Spomky-Labs/cbor-php/blob/4.0.x/src/Encoder.php
     */
    protected function encode(mixed $data, int $options = 0): string
    {
        return (string) $this->processData($data, $options);
    }

    private function processData(mixed $data, int $option): CBORObject
    {
        return match (true) {
            $data instanceof CBORObject => $data,
            is_string($data) => preg_match('//u', $data) === 1 ? $this->processTextString(
                $data,
                $option
            ) : $this->processByteString($data, $option),
            is_array($data) => array_is_list($data) ? $this->processList($data, $option) : $this->processMap(
                $data,
                $option
            ),
            is_int($data) => $data < 0 ? NegativeIntegerObject::create($data) : UnsignedIntegerObject::create($data),
            //is_float($data) => $this->processFloat($data, $option),
            $data === null => NullObject::create(),
            $data === false => FalseObject::create(),
            $data === true => TrueObject::create(),
            default => throw new InvalidArgumentException('Unsupported data type'),
        };
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function processList(array $data, int $option): ListObject|IndefiniteLengthListObject
    {
        $isIndefinite = 0 !== ($option & self::INDEFINITE_LIST_LENGTH);
        $list = $isIndefinite ? IndefiniteLengthListObject::create() : ListObject::create();
        foreach ($data as $item) {
            $list->add($this->processData($item, $option));
        }

        return $list;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function processMap(array $data, int $option): MapObject|IndefiniteLengthMapObject
    {
        $isIndefinite = 0 !== ($option & self::INDEFINITE_MAP_LENGTH);
        $map = $isIndefinite ? IndefiniteLengthMapObject::create() : MapObject::create();
        foreach ($data as $key => $value) {
            $map->add($this->processData($key, $option), $this->processData($value, $option));
        }

        return $map;
    }

    private function processTextString(string $data, int $option): TextStringObject|IndefiniteLengthTextStringObject
    {
        $isIndefinite = 0 !== ($option & self::INDEFINITE_TEXT_STRING_LENGTH);
        $cbor = TextStringObject::create($data);

        if (! $isIndefinite) {
            return $cbor;
        }

        return IndefiniteLengthTextStringObject::create()->add($cbor);
    }

    private function processByteString(string $data, int $option): ByteStringObject|IndefiniteLengthByteStringObject
    {
        $isIndefinite = 0 !== ($option & self::INDEFINITE_BYTE_STRING_LENGTH);
        $cbor = ByteStringObject::create($data);

        if (! $isIndefinite) {
            return $cbor;
        }

        return IndefiniteLengthByteStringObject::create()->add($cbor);
    }
}
