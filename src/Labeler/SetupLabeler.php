<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Illuminate\Support\Facades\Config;
use Revolution\Bluesky\Crypto\DidKey;
use Revolution\Bluesky\Crypto\K256;
use Revolution\Bluesky\Facades\Bluesky;
use RuntimeException;

/**
 * @link https://github.com/skyware-js/labeler/blob/main/src/scripts/plc.ts#L45
 */
class SetupLabeler
{
    public function __invoke(string $did, string $password, string $service, string $plcToken, string $endpoint): array
    {
        $key = Config::string('bluesky.labeler.private_key');

        if (empty($key)) {
            throw new RuntimeException('Private key is required.');
        }

        $didkey = DidKey::format(K256::load($key)->publicPEM());

        Bluesky::login($did, $password, $service);

        $credentials = Bluesky::client()
            ->getRecommendedDidCredentials();

        //dump($credentials);

        $operation = [];

        if ($credentials->json('verificationMethods.atproto_label') !== $didkey) {
            $operation['verificationMethods'] = $credentials->collect('verificationMethods')
                ->merge([
                    'atproto_label' => $didkey,
                ]);
        }

        if ($credentials->json('services.atproto_labeler.endpoint') !== $endpoint) {
            $operation['services'] = $credentials->collect('services')
                ->merge([
                    'atproto_label' => [
                        'type' => 'AtprotoLabeler',
                        'endpoint' => $endpoint
                    ],
                ])->toArray();
        }

        //dump($operation);

        if (empty($operation)) {
            return [];
        }

        $plcOp = Bluesky::client()
            ->signPlcOperation(
                token: $plcToken,
                verificationMethods: data_get($operation, 'verificationMethods'),
                services: data_get($operation, 'services'),
            );

        //dump($plcOp->json());

        $submit = Bluesky::client()
            ->submitPlcOperation(operation: $plcOp->json('operation'));

        //dump($submit->json());

        return $operation;
    }
}
