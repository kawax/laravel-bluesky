Routes defined in the package
====

Some routes are defined in the package. You can disable a route by setting it in config or `.env`.

| path                                        | name                            |
|---------------------------------------------|---------------------------------|
| `/bluesky/oauth/client-metadata.json`       | `bluesky.oauth.client-metadata` |
| `/bluesky/oauth/jwks.json`                  | `bluesky.oauth.jwks`            |
| `/xrpc/app.bsky.feed.getFeedSkeleton`       | `bluesky.feed.skeleton`         |
| `/xrpc/app.bsky.feed.describeFeedGenerator` | `bluesky.feed.describe`         |
| `/.well-known/did.json`                     | `bluesky.well-known.did`        |
| `/.well-known/atproto-did`                  | `bluesky.well-known.atproto`    |

To change the response, use [OAuthConfig](../src/Socalite/OAuthConfig.php) or [WellKnownConfig](../src/WellKnown/WellKnownConfig.php).
