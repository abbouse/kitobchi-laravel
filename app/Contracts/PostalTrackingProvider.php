<?php

namespace App\Contracts;

interface PostalTrackingProvider
{
    public function code(): string;

    public function name(): string;

    /**
     * @return array{
     *     status_code: string,
     *     labels: array{uz: string, ru: string, en: string, ja: string},
     *     status_at: ?string,
     *     location: ?string,
     *     step: string,
     *     terminal: bool,
     *     recipient_address: ?string,
     *     recipient_postcode: ?string
     * }
     */
    public function track(string $trackingNumber): array;
}
