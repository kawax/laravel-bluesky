<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Labeler;

use Exception;
use Revolution\Bluesky\Core\CBOR;

class LabelerException extends Exception
{
    /**
     * @phpstan-ignore missingType.parameter
     */
    public function __construct(
        public string $error,
        public $message = '',
    ) {
        parent::__construct($message, code: -1);
    }

    public function toBytes(): string
    {
        $header = ['op' => -1];

        $body = [
            'error' => $this->error,
            'message' => $this->message,
        ];

        return CBOR::encode($header).CBOR::encode($body);
    }
}
