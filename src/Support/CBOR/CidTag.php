<?php

namespace Revolution\Bluesky\Support\CBOR;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\Tag;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Revolution\Bluesky\Support\CID;
use YOCLIB\Multiformats\Multibase\Multibase;

class CidTag extends Tag
{
    protected const CID_TAG = 42;

    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        if (! $object instanceof ByteStringObject && ! $object instanceof IndefiniteLengthByteStringObject) {
            throw new InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::CID_TAG;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Tag
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Tag
    {
        [$ai, $data] = self::determineComponents(self::CID_TAG);

        return new self($ai, $data, $object);
    }

    public function normalize(): string
    {
        /** @var ByteStringObject|IndefiniteLengthByteStringObject $object */
        $object = $this->object;

        $cid = $object->normalize();
        $cid = Str::ltrim($cid, "\x00");

        if (str_starts_with($cid, CID::V0_LEADING)) {
            return Multibase::encode(Multibase::BASE58BTC, $cid, false);
        } else {
            return Multibase::encode(Multibase::BASE32, $cid);
        }
    }

    public function link(): array
    {
        return ['$link' => $this->normalize()];
    }

    public function mst(): array
    {
        return ['/' => $this->normalize()];
    }
}
