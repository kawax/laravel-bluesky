# Socialite

Bluesky's OAuth is more difficult than other providers. Please read the documentation carefully.

https://docs.bsky.app/docs/advanced-guides/oauth-client

## Notice

Bluesky OAuth probably won't work on localhost, please deploy it to a public server and test it.

## Configuration

### Create new private key

You must first create a private key.

```bash
php artisan bluesky:new-private-key
```

```bash
Please set this private key in .env

BLUESKY_OAUTH_PRIVATE_KEY="...url-safe base64 encoded key..."
```

### .env

Copy and paste into .env

```
BLUESKY_OAUTH_PRIVATE_KEY="..."
```

## Create callback route

The recommended route name is `bluesky.oauth.redirect`.

```php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialiteController;

Route::get('bluesky/callback', [SocialiteController::class, 'callback'])
     ->name('bluesky.oauth.redirect');
```

### Customize route name

If you want to use a different route name, you will need to customize it using `OAuthConfig`.

```php
// AppServiceProvider

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Revolution\Bluesky\Socalite\OAuthConfig;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        OAuthConfig::clientMetadataUsing(function () {
            return collect(config('bluesky.oauth.metadata'))
                ->merge(
                    [
                        'client_id' => route('bluesky.oauth.client-metadata'),
                        'jwks_uri' => route('bluesky.oauth.jwks'),
                        'redirect_uris' => [url('bluesky/callback')],
                    ],
                )->reject(fn ($item) => is_null($item))->toArray();
        });
    }
}
```

The `bluesky.oauth.client-metadata` and `bluesky.oauth.jwks` routes are defined within the package, so you are free to
modify them here as well. However, they usually do not need to be changed.

You can also see these routes on localhost.

- http://localhost/bluesky/oauth/client-metadata.json
- http://localhost/bluesky/oauth/jwks.json

## Usage

### routes/web.php

```php
use App\Http\Controllers\SocialiteController;

Route::get('login', [SocialiteController::class, 'login'])->name('login');
Route::post('redirect', [SocialiteController::class, 'redirect']);
Route::get('callback', [SocialiteController::class, 'callback'])->name('bluesky.oauth.redirect');
```

### Controller

```php
<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Revolution\Bluesky\Session\OAuthSession;

class SocialiteController extends Controller
{
    public function login(Request $request)
    {
        return view('login');
    }

    public function redirect(Request $request)
    {
        // You can specify the handle, DID, or PDS URL as a login hint. It can be empty.
        return Socialite::driver('bluesky')
                        ->hint($request->input('username'))
                        ->redirect();

        // If you don’t need a hint you can skip this step and just redirect straight away like with other providers.
    }

    public function callback(Request $request)
    {
        if ($request->missing('code')) {
            dd($request);
        }

        /**
        * @var \Laravel\Socialite\Two\User
        */
        $user = Socialite::driver('bluesky')->user();

        /** @var OAuthSession $session */
        $session = $user->session;

        // Since $user has an OAuthSession, it is saved in Laravel's session.
        $request->session()->put('bluesky_session', $session);

        $loginUser = User::updateOrCreate([
            'bluesky_did' => $user->id, // Bluesky DID (did:plc:...)
        ], [
            'iss' => $session->issuer(), // Bluesky iss (https://bsky.social)
            'handle' => $user->nickname, // Bluesky handle (alice.test)
            'name' => $user->name, // Bluesky displayName (Alice)
            'avatar' => $user->avatar,
            'access_token' => $user->token,
            'refresh_token' => $user->refreshToken,
        ]);

        auth()->login($loginUser, true);

        return to_route('bluesky.dashboard');
    }
}
```

## Call the API using the token

When calling an API using a token, be sure to specify the OAuthSession using `withToken()`.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

/** @var OAuthSession $session */
$session = session('bluesky_session');

$profile = Bluesky::withToken($session)
                  ->profile()
                  ->json();

dump($profile);
```

## Minimal OAuthSession
If you have an account created with `bsky.social`, you can refresh the OAuthSession with just the `refresh_token`. `iss` will automatically be set to `bsky.social`.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

$session = OAuthSession::create([
    'refresh_token' => $user->refresh_token,
]);

$profile = Bluesky::withToken($session)
                  ->refreshToken()
                  ->profile()
                  ->json();

dump($profile);
```

If you created your account outside of `bsky.social`, please also specify `iss`.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

$session = OAuthSession::create([
    'refresh_token' => $user->refresh_token,
    'iss' => $user->iss,
]);

$profile = Bluesky::withToken($session)
                  ->refreshToken()
                  ->profile()
                  ->json();

dump($profile);
```

Even if you want to use it in a Console or Job where Laravel sessions cannot be used, you can create an OAuthSession and call the API in this way.

## OAuthSessionUpdated Event

To receive the updated OAuthSession, use the `OAuthSessionUpdated` event.

```bash
php artisan make:listener OAuthSessionUpdatedListener
```

```php
namespace App\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Revolution\Bluesky\Events\OAuthSessionUpdated;

class OAuthSessionListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OAuthSessionUpdated $event): void
    {
        session()->put('bluesky_session', $event->session);

        $user = User::updateOrCreate([
            'bluesky_did' => $event->session->did(), // Bluesky DID (did:plc:...)
        ], [
            'iss' => $event->session->issuer(), // Bluesky iss (https://bsky.social)
            'handle' => $event->session->handle(), // Bluesky handle (alice.test)
            'name' => $event->session->get('displayName'), // Bluesky displayName (Alice)
            'avatar' => $event->session->get('avatar'),
            'access_token' => $event->session->token(),
            'refresh_token' => $event->session->refresh(),
        ]);
    }
}
```

OAuthSession value may be null or empty.

## Unauthenticated

If the `OAuthSession` is null or does not contain a refresh_token, an `Unauthenticated` exception will be raised and you will be redirected to the `login` route, just like in normal Laravel behavior.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

/** @var OAuthSession $session */
$session = session('bluesky_session');

Bluesky::withToken(null);

// redirect to "login" route
```

You can change this behavior in `bootstrap/app.php`.

```php
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->redirectGuestsTo('/bluesky/login');
})
```
