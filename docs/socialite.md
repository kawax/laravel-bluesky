# Socialite

Bluesky's OAuth is more difficult than other providers. Please read the documentation carefully.

https://docs.bsky.app/docs/advanced-guides/oauth-client

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

## client_id and redirect

Bluesky uses `client-metadata.json` url as client_id.

When developing locally, you can check the operation on the real Bluesky server by setting client_id to `http://localhost` and redirect to `http://127.0.0.1:8000/`. Scope is only `atproto`, so what you can do is limited. Only basic login functions are available, and APIs that require authentication cannot be used.

### local development with real Bluesky server
By default, `http://localhost` and `http://127.0.0.1:8000/` are set, so there is no need to configure it in .env.

```
BLUESKY_CLIENT_ID=
BLUESKY_REDIRECT=
```

If you are using a different port, configure it.
```
BLUESKY_CLIENT_ID=
BLUESKY_REDIRECT=http://127.0.0.1:8080/
```

### local development with atproto dev-env

https://github.com/bluesky-social/atproto

If you are running a local server using this repository, you can enable OAuth authentication offline by setting .env as follows:

```
BLUESKY_PLC=http://localhost:2582
BLUESKY_SERVICE=http://localhost:2583
BLUESKY_PUBLIC_ENDPOINT=http://localhost:2584
```

Several fake users have been created so you can authenticate with this handle and password.

- handle : `alice.test`
- password : `hunter2`

https://github.com/bluesky-social/atproto/blob/main/packages/dev-env/src/mock/index.ts

### Callback route in local

During development, the returned URL is fixed to `http://127.0.0.1:8000/`, so it is a good idea to check the request and redirect it to the original URL.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if(app()->isLocal() && $request->has('iss')) {
        return to_route('bluesky.oauth.redirect', $request->query());
    }

    //...
});
```

### production

If the route name `bluesky.oauth.redirect` exists, there is no need to configure it in .env.

If you have changed the default route `bluesky.oauth.redirect`, set it in .env along with the client-metadata below.

```
BLUESKY_REDIRECT=/bluesky/callback
```

## Customize client-metadata

`bluesky.oauth.client-metadata` and `bluesky.oauth.jwks` routes are defined within the package, 
so you are free to modify them here as well. However, they usually do not need to be changed.

You can also see these routes on localhost.

- http://127.0.0.1:8000/bluesky/oauth/client-metadata.json
- http://127.0.0.1:8000/bluesky/oauth/jwks.json

`client-metadata` can be customized using `OAuthConfig`.

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
                )->reject(fn ($item) => is_null($item))
                ->toArray();
        });
    }
}
```

## Usage

### routes/web.php

```php
use App\Http\Controllers\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('login', [SocialiteController::class, 'login'])->name('login');
Route::match(['get', 'post'], 'redirect', [SocialiteController::class, 'redirect']);
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
        // A form page to submit a login hint.
        return view('login');
    }

    public function redirect(Request $request)
    {
        // You can specify the handle, DID, or PDS URL as a login hint. It can be empty.
        $hint = $request->input('login_hint');
        $request->session()->put('hint', $hint);

        return Socialite::driver('bluesky')
                        ->hint($hint)
                        ->redirect();

        // If you don’t need a hint you can skip this step and just redirect straight away like with other providers.
    }

    public function callback(Request $request)
    {
        if ($request->missing('code')) {
            dd($request);
        }

        $hint = $request->session()->pull('hint');

        /**
        * @var \Laravel\Socialite\Two\User
        */
        $user = Socialite::driver('bluesky')
                         ->hint($hint)
                         ->user();

        /** @var OAuthSession $session */
        $session = $user->session;

        // Since $user has an OAuthSession, it is saved in Laravel's session.
        $request->session()->put('bluesky_session', $session->toArray());

        $loginUser = User::updateOrCreate([
            'did' => $session->did(), // Bluesky DID (did:plc:...)
        ], [
            'iss' => $session->issuer(), // Bluesky iss (https://bsky.social)
            'handle' => $session->handle(), // Bluesky handle (alice.test)
            'name' => $session->displayName(), // Bluesky displayName (Alice)
            'avatar' => $session->avatar(),
            'access_token' => $session->token(),
            'refresh_token' => $session->refresh(),
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

$session = OAuthSession::create(session('bluesky_session'));

$timeline = Bluesky::withToken($session)->getTimeline();

dump($timeline->json());
```

## OAuthSession values

To see all the values in an OAuthSession, convert it to an array.
You don't need to use all of them, as some values are not required for authentication.

```php
use Revolution\Bluesky\Session\OAuthSession;

/** @var OAuthSession $session */
dump($session->toArray());
```

## Minimal OAuthSession

If you have an account created with `bsky.social`, you can refresh the OAuthSession with just the `refresh_token`. It would be better to include `did` as well. `iss` will automatically be set to `https://bsky.social`.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

$session = OAuthSession::create([
    'did' => $user->did,
    'refresh_token' => $user->refresh_token,
]);

$timeline = Bluesky::withToken($session)
                   ->refreshSession()
                   ->getTimeline();

dump($timeline->json());
```

If you created your account outside of `bsky.social`, please also specify `iss`.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

$session = OAuthSession::create([
    'did' => $user->did,
    'refresh_token' => $user->refresh_token,
    'iss' => $user->iss,
]);

$timeline = Bluesky::withToken($session)
                   ->refreshSession()
                   ->getTimeline();

dump($timeline->json());
```

Even if you want to use it in a Console or Job where Laravel sessions cannot be used, you can create an OAuthSession and call the API in this way.

## OAuthSessionUpdated Event

To receive the updated OAuthSession, use the `OAuthSessionUpdated` event.

The refresh_token can only be used once, so it is important to keep updating it here.

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
        if(empty($event->session->did())) {
            return;
        }

        session()->put('bluesky_session', $event->session->toArray());

        $user = User::firstWhere('did', $event->session->did());
        $user->fill([
            'iss' => $event->session->issuer(),
            'handle' => $event->session->handle(),
            'name' => $event->session->displayName(),
            'avatar' => $event->session->avatar(),
            'access_token' => $event->session->token(),
            'refresh_token' => $event->session->refresh(),
        ])->save();
    }
}
```

OAuthSession value may be null or empty.

## OAuthSessionRefreshing Event
Similarly, when an OAuthSession refresh starts, the `OAuthSessionRefreshing` event is fired. During this event, `$event->session->refresh()` is empty. Since the refresh_token can only be used once, delete the refresh_token from the database here.

```php
    public function handle(OAuthSessionRefreshing $event): void
    {
        if(empty($event->session->did())) {
            return;
        }

        User::firstWhere('did', $event->session->did())
            ->fill(['refresh_token' => ''])
            ->save();
    }
```

## Unauthenticated

If the `OAuthSession` is null or does not contain a refresh_token, an `Unauthenticated` exception will be raised, and you will be redirected to the `login` route, just like in normal Laravel behavior.

```php
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\OAuthSession;

$session = OAuthSession::create(['refresh_token' => null]);

$timeline = Bluesky::withToken($session)->getTimeline();

// redirect to "login" route
```

You can change this behavior in `bootstrap/app.php`.

```php
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->redirectGuestsTo('/bluesky/login');
})
```

## WithBluesky trait

Add `WithBluesky` to User, and implement the `tokenForBluesky()` method, you will get an authenticated Bluesky client via `$user->bluesky()`.

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Traits\WithBluesky;

class User extends Authenticatable
{
    use WithBluesky;

    protected function tokenForBluesky(): OAuthSession
    {
        return OAuthSession::create([
            'did' => $this->bluesky_did,
            'refresh_token' => $this->bluesky_refresh_token,
            'iss' => $this->bluesky_iss,
        ]);
    }
}
```

```php
$profile = auth()->user()
                 ->bluesky()
                 ->refreshSession()
                 ->getProfile();

dump($profile->json());
```
