<?php

namespace App\Exceptions;

use RuntimeException;

class PaylovApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $apiCode,
        public readonly int $httpStatus,
        public readonly array $errorData = [],
    ) {
        parent::__construct($message);
    }

    public function isRetryable(): bool
    {
        if ($this->httpStatus === 429 || $this->httpStatus >= 500) {
            return true;
        }

        return in_array($this->apiCode, [
            'ofd_error',
            'fiscal_receipt_not_generated',
            'server_error',
            'temporarily_unavailable',
        ], true);
    }
}
