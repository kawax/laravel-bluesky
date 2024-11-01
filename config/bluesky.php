<?php

return [
    // App password
    'identifier' => env('BLUESKY_IDENTIFIER'),
    'password' => env('BLUESKY_APP_PASSWORD'),

    // OAuth
    'oauth' => [
        // Disable all OAuth features
        'disabled' => env('BLUESKY_OAUTH_DISABLED', false),

        // Client Metadata
        'metadata' => [
            'scope' => env('BLUESKY_OAUTH_SCOPE', 'atproto transition:generic'),

            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'application_type' => env('BLUESKY_OAUTH_APPLICATION_TYPE', 'web'),
            'token_endpoint_auth_method' => env('BLUESKY_OAUTH_TOKEN_METHOD', 'private_key_jwt'),
            'token_endpoint_auth_signing_alg' => env('BLUESKY_OAUTH_TOKEN_SIGN', 'ES256'),

            'dpop_bound_access_tokens' => env('BLUESKY_OAUTH_DPOP', true),

            // Optional fields
            'client_name' => env('BLUESKY_OAUTH_CLIENT_NAME'),
            'client_uri' => env('BLUESKY_OAUTH_CLIENT_URI'),
            'logo_uri' => env('BLUESKY_OAUTH_LOGO'),
            'tos_uri' => env('BLUESKY_OAUTH_TOS'),
            'policy_uri' => env('BLUESKY_OAUTH_PRIVACY'),
        ],

        // Socialite
        'socialite' => [
            'client_id' => env('BLUESKY_CLIENT_ID', 'http://localhost'),
            'redirect' => env('BLUESKY_REDIRECT', 'http://127.0.0.1:8000/'),
        ],

        // Private key(base64 encoded)
        'private_key' => env('BLUESKY_OAUTH_PRIVATE_KEY'),

        // Route
        'prefix' => env('BLUESKY_OAUTH_PREFIX', '/bluesky/oauth/'),
    ],
];
