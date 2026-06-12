<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Context\Setup;

final class StripeRefundRejection
{
    /** @return array{0: string, 1: string} message and Stripe error code */
    public static function forReason(string $reason): array
    {
        return match ($reason) {
            'already refunded' => [
                'Charge ch_test_1 has already been refunded.',
                'charge_already_refunded',
            ],
            'charged back' => [
                'Charge ch_test_1 has been charged back; cannot issue a refund.',
                'charge_disputed',
            ],
            default => throw new \InvalidArgumentException(sprintf('Unknown Stripe refund rejection reason "%s".', $reason)),
        };
    }
}
