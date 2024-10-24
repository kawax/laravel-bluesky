<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Revolution\Bluesky\Socalite\Key\BlueskyKey;

class NewPrivateKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:new-private-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new private key for Bluesky OAuth';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $private = BlueskyKey::create()->privateB64();

        $this->comment('Please set this private key in .env');
        $this->newLine();
        $this->info('BLUESKY_OAUTH_PRIVATE_KEY="'.$private.'"');

        return 0;
    }
}
