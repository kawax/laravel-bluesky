<?php

return [
    'service' => env('BLUESKY_SERVICE', 'https://bsky.social'),

    // App password
    'identifier' => env('BLUESKY_IDENTIFIER'),
    'password' => env('BLUESKY_APP_PASSWORD'),

    // OAuth
    'oauth' => [
        // Client Metadata
        'metadata' => [
            'scope' => env('BLUESKY_OAUTH_SCOPE', 'atproto transition:generic'),

            'client_name' => config('app.name'),
            'client_uri' => config('app.url'),

            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'application_type' => env('BLUESKY_OAUTH_APPLICATION_TYPE', 'web'),
            'token_endpoint_auth_method' => env('BLUESKY_OAUTH_TOKEN_METHOD', 'private_key_jwt'),
            'token_endpoint_auth_signing_alg' => env('BLUESKY_OAUTH_TOKEN_SIGN', 'ES256'),

            'logo_uri' => env('BLUESKY_OAUTH_LOGO'),
            'tos_uri' => env('BLUESKY_OAUTH_TOS'),
            'policy_uri' => env('BLUESKY_OAUTH_PRIVACY'),

            'dpop_bound_access_tokens' => env('BLUESKY_OAUTH_DPOP', true),
        ],

        // Private key(base64 encoded)
        'private_key' => env('BLUESKY_OAUTH_PRIVATE_KEY'),

        'prefix' => env('BLUESKY_OAUTH_PREFIX', '/bluesky/oauth/'),
    ],
];
