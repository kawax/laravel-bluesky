Labeler
====

It is essential to understand the concept of Labeler beforehand.

- https://atproto.com/specs/label
- https://docs.bsky.app/blog/blueskys-moderation-architecture

There are few official tutorials, so refer to starter kits for other languages.

- https://skyware.js.org/guides/labeler/introduction/getting-started/
- https://github.com/aliceisjustplaying/labeler-starter-kit-bsky

Here is sample Labeler.

- https://bsky.app/profile/laralabeler.bsky.social
- https://github.com/kawax/laralabeler

## Caution

Labeler is quite difficult to run, so it is not recommended for Laravel beginners. Please only try it if you can understand the sample code. No support.

The easiest way to get Labeler up and running is with Laravel Forge, so this doc will only cover how to run it with Forge.

## Preparation
- A new Bluesky account just for Labeler. Do not use your regular account.
- A new Laravel project just for Labeler. It is better to separate the projects.
- (Sub)domain
- A production Linux server such as VPS or AWS EC2. Laravel Vapor or Vercel will not work.

If you can only use a shared server, go back here.

## Install additional packages

```shell
composer require workerman/workerman revolt/event-loop
```

## Configuration

First, generate a private key.

```shell
php artisan bluesky:labeler:new-private-key
```

Add the private key and other items to your `.env` file.

```
BLUESKY_LABELER_DID=did:plc:***
BLUESKY_LABELER_IDENTIFIER=***.bsky.social
BLUESKY_LABELER_APP_PASSWORD=

BLUESKY_LABELER_PRIVATE_KEY=""
```

## Create Labeler class

Define your own Labeler implementation here. You can create the file anywhere you like. Be sure to inherit `AbstractLabeler`.

```php
namespace App\Labeler;

use Revolution\Bluesky\Labeler\AbstractLabeler;

readonly class ArtisanLabeler extends AbstractLabeler
{

}
```

Please refer to the sample for details.

[Sample](https://github.com/kawax/laralabeler/blob/main/app/Labeler/ArtisanLabeler.php)

The processing required for Labeler is done within the package, so only the parts that require customization are implemented within your own Labeler class.

### labels()

Label definitions.

Found in the starter kit here.
https://github.com/aliceisjustplaying/labeler-starter-kit-bsky/blob/main/src/constants.ts

### subscribeLabels()

It is called immediately after connecting via WebSocket.

In skyware's code, it is here.
https://github.com/skyware-js/labeler/blob/717ea8d0928ac35b1955855450bbd58d5c1c559c/src/LabelerServer.ts#L437

Returns `SubscribeLabelResponse` as an iterator.

### emitEvent()

Called when a label is added or removed.

The data sent in the request is here.
https://docs.bsky.app/docs/api/tools-ozone-moderation-emit-event

In skyware's code, it is here.
https://github.com/skyware-js/labeler/blob/717ea8d0928ac35b1955855450bbd58d5c1c559c/src/LabelerServer.ts#L505

Returns `UnsignedLabel`.

### saveLabel()

This is where you save it to a database etc.

Returns `SavedLabel`.

### createReport()

This is called when an appeal or something is sent by a user.

https://docs.bsky.app/docs/api/com-atproto-moderation-create-report

### queryLabels()

`queryLabels` has the same purpose as `subscribeLabels`, but is called via the HTTP API instead of WebSocket. It is not used officially by Bluesky. Third parties use it.

https://docs.bsky.app/docs/api/com-atproto-label-query-labels

If you don't need it, just return an empty array.

```php
public function queryLabels(): array
{
    return [];
}
```

## Register Labeler class in AppServiceProvider

```php
use Revolution\Bluesky\Labeler\Labeler;
use App\Labeler\ArtisanLabeler;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Labeler::register(ArtisanLabeler::class);
    }
}
```

## Setup

You will need to set up your account to be initialized as a Labeler.

Here, enter your real account password, not the app password.

You will receive an "PLC Update Operation Requested" email during the process, so enter it here.

```shell
php artisan bluesky:labeler:setup
```

This command can also be run locally if the endpoint URL is entered correctly.

## Declare Label definitions

Register label definitions in your Labeler account.

```shell
php artisan bluesky:labeler:declare-labels
```

This command can also be run locally.

## Database

Prepared with reference to migration and Eloquent.

- [migration](../workbench/database/migrations/2024_12_31_000000_create_labels_table.php)
- [Eloquent](../workbench/app/Models/Label.php)

## Running on Laravel Forge

Once you have enabled SSL, run the Labeler server using the following instructions.

- [nginx](../workbench/laravel-forge/labeler-nginx.conf)
- [deploy script](../workbench/laravel-forge/labeler-deploy-script.sh)
- [daemon](../workbench/laravel-forge/labeler-daemon.md)

In the sample, the labeler is run with this command:

```
php artisan bluesky:labeler:server start --jetstream -C app.bsky.graph.follow
```

## Add Label

How you actually label is up to your labeler.

In the sample, we're using Laravel's event functionality to label "when followed"

https://github.com/kawax/laralabeler/blob/main/app/Listeners/FollowListener.php

The follower is also labeled in the task schedule in case an event is missed.

https://github.com/kawax/laralabeler/blob/main/app/Console/Commands/LabelFollowerCommand.php

