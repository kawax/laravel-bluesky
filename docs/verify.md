Verify
====

Verifiability is a key concept of Bluesky/AtProtocol.

## Post

When you have post data retrieved
from [com.atproto.repo.getRecord](https://docs.bsky.app/docs/api/com-atproto-repo-get-record),
you can verify if the CID matches the record.

```php
$block = [
    'uri' => 'at://did:plc:***/app.bsky.feed.post/***',
    'cid' => 'bafyreih5y47li4zuvvzevmq4xl7woqxchfc2pnfclv3kfz3zefb2qd3bzm',
    'value' => [
        'text' => 'Hello, Bluesky!',
        '$type' => 'app.bsky.feed.post',
        'createdAt' => '2025-01-01T00:00:00.000Z',
    ],
];
```

```php
use Revolution\Bluesky\Core\CBOR;
use Revolution\Bluesky\Core\CID;

$cid = data_get($block, 'cid');
$record = data_get($block, 'value');

// Encode to DAG-CBOR
$cbor = CBOR::encode($record);

$bool = CID::verify($cbor, $cid);
```

[DownloadRecordCommand](../src/Console/DownloadRecordCommand.php) is a sample.

## Image file

For post with images, `$link` is the CID of the image.

```php
$block = [
    'uri' => 'at://did:plc:***/app.bsky.feed.post/***',
    'cid' => 'b+++',
    'value' => [
        'text' => 'Post with image embed',
        '$type' => 'app.bsky.feed.post',
        'embed' => [
            '$type' => 'app.bsky.embed.images',
            'images' => [
                [
                    'alt' => '',
                    'image' => [
                    'ref' => [
                        '$link' => 'b***image'
                    ],
                    'size' => 100000,
                    '$type' => 'blob',
                    'mimeType' => 'image/jpeg'
                ],
                    'aspectRatio' => [
                    'width' => 1000,
                    'height' => 1000
                ]
                ]
            ]
        ],
        'createdAt' => '2025-01-01T00:00:00.000Z'
    ]
];
```

You can verify the raw data of the image downloaded
with [com.atproto.sync.getBlob](https://docs.bsky.app/docs/api/com-atproto-sync-get-blob).

```php
use Revolution\Bluesky\Core\CID;

$cid = data_get($block, 'cid');
$file = file_get_contents('path/to/b***image.jpg');

$bool = CID::verify($file, $cid, codec: CID::RAW);
```

[DownloadBlobsCommand](../src/Console/DownloadBlobsCommand.php) is a sample.

## CAR file

A CAR file contains all the records of a user.

You can verify if the CAR file downloaded
with [com.atproto.sync.getRepo](https://docs.bsky.app/docs/api/com-atproto-sync-get-repo) belongs to the user.

```php
use Revolution\Bluesky\Core\CAR;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;

$did = 'did:plc:***';

// The public key in the didDoc is needed for verification
$didDoc = DidDocument::make(Bluesky::identity()->resolveDID($did)->json());

$didKey = DidKey::parse($didDoc->publicKey());

$car = 'Raw data of the CAR file or a stream to the file';

$signed = CAR::signedCommit($car);

$bool = CAR::verifySignedCommit($signed, $didKey);
```

[DownloadRepoCommand](../src/Console/DownloadRepoCommand.php) is a sample.

## Unpack CAR

Verifying records obtained by unpacking a CAR file is the same as the first Post example.

[UnpackRepoCommand](../src/Console/UnpackRepoCommand.php) is a sample.
