<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console\Labeler;

use Illuminate\Console\Command;
use Revolution\Bluesky\Labeler\Actions\DeclareLabelDefinitions;

/**
 * @link https://github.com/skyware-js/labeler/blob/main/src/scripts/declareLabeler.ts
 */
class LabelerDeclareLabelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:labeler:declare-labels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Declare label definitions for Labeler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DeclareLabelDefinitions $declare): int
    {
        $declare();

        $this->info('If successful, Labeler account page will be updated with new label definitions.');

        return 0;
    }
}
