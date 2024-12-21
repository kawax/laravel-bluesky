<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Labeler\SetupLabeler;
use Revolution\Bluesky\Support\Identity;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * @link https://github.com/skyware-js/labeler/blob/main/src/bin.ts
 */
class LabelerSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bluesky:labeler:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize an account as a labeler.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SetupLabeler $setup): int
    {
        $confirmed = confirm(
            label: 'Before setting up, have you created an account dedicated to Labeler?',
            hint: 'Make sure you understand what this command does before proceeding.',
        );

        if (! $confirmed) {
            $this->error('Please create an account first.');

            return 1;
        }

        $did = text(
            label: 'Enter DID or Handle',
            placeholder: 'alice.bsky.social',
            default: Config::string('bluesky.labeler.identifier'),
            validate: fn (?string $did) => match (true) {
                Identity::isDID($did) => null,
                default => throw new InvalidArgumentException("Could not resolve '$did' to a valid account. Please try again."),
            },
            hint: 'DID or handle of the account to use',
            transform: function ($did) {
                if (! Identity::isDID($did)) {
                    return Bluesky::resolveHandle($did)->json('did', $did);
                } else {
                    return $did;
                }
            },
        );

        $password = password(
            label: 'Enter Account Password',
            required: true,
            hint: 'Account password (cannot be an app password)',
        );

        $service = text(
            label: 'Enter PDS URL',
            default: Bluesky::entryway(),
            required: true,
            hint: 'URL of the PDS where the account is located',
        );

        $res = Bluesky::login($did, $password, $service)
            ->client()
            ->requestPlcOperationSignature();

        if ($res->failed()) {
            dump($res->json());

            return 1;
        }

        $plcToken = password(
            label: 'Enter PLC Token',
            required: true,
            hint: 'You will receive a confirmation code via email.',
        );

        $endpoint = text(
            label: 'Enter Endpoint URL',
            default: Str::rtrim(url('/'), '/'),
            required: true,
            hint: 'URL where the labeler will be hosted:',
        );

        $setup($did, $password, $service, $plcToken, $endpoint);

        $this->info('If successful, PLC will be updated: https://plc.directory/'.$did);

        return 0;
    }
}
