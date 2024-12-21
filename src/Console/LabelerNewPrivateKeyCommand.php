<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Revolution\Bluesky\Crypto\K256;

class LabelerNewPrivateKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:labeler:new-private-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new private key for Labeler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $private = K256::create()->privateB64();

        $this->comment('Please set this private key in .env');
        $this->newLine();
        $this->info('BLUESKY_LABELER_PRIVATE_KEY="'.$private.'"');

        return 0;
    }
}
