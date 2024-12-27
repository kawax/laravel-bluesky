<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\Labeler;

use Illuminate\Console\Command;
use Revolution\Bluesky\Labeler\Actions\DeleteLabelDefinitions;

/**
 * @link https://github.com/skyware-js/labeler/blob/main/src/scripts/declareLabeler.ts
 */
class LabelerDeleteLabelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:labeler:delete-labels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete label definitions from Labeler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DeleteLabelDefinitions $delete): int
    {
        $delete();

        $this->info('If successful, Labeler account page will be updated with no label definitions.');

        return 0;
    }
}
