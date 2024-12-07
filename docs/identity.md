Resolve Identity
====

In Bluesky, mutual resolution of Handle, DID, etc. is often required.

- https://docs.bsky.app/docs/advanced-guides/resolving-identities
- https://atproto.com/guides/identity

## Handle to DID

Using API.

```php
use Revolution\Bluesky\Facades\Bluesky;

$did = Bluesky::resolveHandle('***.bsky.social')->json('did');
```

Check DNS TXT or `/.well-known/atproto-did`.

```php
use Revolution\Bluesky\Facades\Bluesky;

$did = Bluesky::identity()->resolveHandle('alice.test');
```

They are similar but different.

## DID to DID Document

`resolveDID` returns didDoc.

https://plc.directory/did:plc:ewvi7nxzyoun6zhxrhs64oiz

```php
use Revolution\Bluesky\Facades\Bluesky;

$didDoc = Bluesky::identity()->resolveDID('did:plc:*** or did:web:***')->json();
```

## DID(Document) to Handle

`alsoKnownAs` in didDoc is handle.  

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;

$didDoc = DidDocument::make(Bluesky::identity()->resolveDID('did:plc:*** or did:web:***')->json());
$handle = $didDoc->handle();
```

## resolveIdentity

`resolveIdentity` which combines `resolveHandle` and `resolveDID`, can resolve from either did or handle and returns didDoc.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;

$didDoc = DidDocument::make(Bluesky::identity()->resolveIdentity('did or handle')->json());
$didDoc->id();
$didDoc->handle();
```

## Public profile

Public profile can be resolved from either did or handle and the response will include both did and handle.

```php
use Revolution\Bluesky\Facades\Bluesky;

$profile = Bluesky::getProfile('did or handle');
$profile->json('did');
$profile->json('handle');
```

## PDS url / serviceEndpoint

Although it is unlikely to be used directly, the `serviceEndpoint` in didDoc is the PDS URL.

`https://***.***.host.bsky.network`

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;

$didDoc = DidDocument::make(Bluesky::identity()->resolveIdentity('did or handle')->json());
$didDoc->pdsUrl();
```

## Authorization Server / Service / Issuer url

This can be solved through PDS.

If you signed up with Bluesky, this should be `https://bsky.social`

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\DidDocument;

$didDoc = DidDocument::make(Bluesky::identity()->resolveIdentity('did or handle')->json());

$pds = Bluesky::pds()->getProtectedResource($didDoc->pdsUrl());
$pds->authServer();
```
