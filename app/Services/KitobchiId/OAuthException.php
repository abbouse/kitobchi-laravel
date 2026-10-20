<?php

namespace App\Services\KitobchiId;

use RuntimeException;

/** RFC 6749 §5.2 formatidagi xato. */
class OAuthException extends RuntimeException
{
    public function __construct(
        public readonly string $error,
        public readonly string $description = '',
        public readonly int $status = 400,
    ) {
        parent::__construct($error.($description !== '' ? ': '.$description : ''));
    }

    public function toArray(): array
    {
        return array_filter([
            'error' => $this->error,
            'error_description' => $this->description,
        ], fn ($v) => $v !== '');
    }
}
