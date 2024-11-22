<?php

return [
    // service / PDS url
    'service' => env('BLUESKY_SERVICE', 'https://bsky.social'),

    // PLC
    'plc' => env('BLUESKY_PLC', 'https://plc.directory'),

    // Public endpoint
    'public_endpoint' => env('BLUESKY_PUBLIC_ENDPOINT', 'https://public.api.bsky.app'),

    // App password
    'identifier' => env('BLUESKY_IDENTIFIER'),
    'password' => env('BLUESKY_APP_PASSWORD'),

    // Notification
    'notification' => [
        // Use a specific sender and receiver in a PrivateChannel.
        'private' => [
            'sender' => [
                'identifier' => env('BLUESKY_SENDER_IDENTIFIER'),
                'password' => env('BLUESKY_SENDER_APP_PASSWORD'),
            ],
            'receiver' => env('BLUESKY_RECEIVER'),
        ],
    ],

    // OAuth
    'oauth' => [
        // Disable all OAuth features
        'disabled' => env('BLUESKY_OAUTH_DISABLED', false),

        // Client Metadata
        'metadata' => [
            'scope' => env('BLUESKY_OAUTH_SCOPE', 'atproto transition:generic transition:chat.bsky'),

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
        'client_id' => env('BLUESKY_CLIENT_ID'),
        'redirect' => env('BLUESKY_REDIRECT'),

        // Private key(base64 encoded)
        'private_key' => env('BLUESKY_OAUTH_PRIVATE_KEY'),

        // Route
        'prefix' => env('BLUESKY_OAUTH_PREFIX', '/bluesky/oauth/'),
    ],

    // Feed Generator
    'generator' => [
        // did:web:example.com
        'service' => env('BLUESKY_GENERATOR_SERVICE'),
        // did:plc:***
        'publisher' => env('BLUESKY_GENERATOR_PUBLISHER'),
    ],
];
