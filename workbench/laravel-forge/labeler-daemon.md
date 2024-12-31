Labeler Daemon
====

## Directory
```
/home/forge/labeler.example.com/
```

## Command
```
php artisan bluesky:labeler:server start
```

If you want to run labeler together with Jetstream or Firehose websocket server, specify it as an option.
Note that the labeler command cannot be run at the same time as the `bluesky:ws` or `bluesky:firehose` commands.

```
php artisan bluesky:labeler:server start --jetstream
php artisan bluesky:labeler:server start --jetstream -C app.bsky.graph.follow -C app.bsky.feed.like
```
```
php artisan bluesky:labeler:server start --firehose
```
