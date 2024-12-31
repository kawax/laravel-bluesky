cd /home/forge/labeler.example.com
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

if [ -f artisan ]; then
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan event:cache
    $FORGE_PHP artisan view:cache

    $FORGE_PHP artisan migrate --force

    npm ci
    npm run build

    # After being stopped during deployment, the Labeler Server is automatically restarted by Supervisor.
    $FORGE_PHP artisan bluesky:labeler:server status
    $FORGE_PHP artisan bluesky:labeler:server stop
fi
