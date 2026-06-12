<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\Api;

use Stripe\Refund;
use Stripe\Stripe;
use Tests\FluxSE\SyliusStripePlugin\Behat\Mocker\StripeClientWithExpectationsInterface;

class RefundMocker
{
    /**
     * @param StripeClientWithExpectationsInterface<Refund> $stripeClientWithExpectations
     */
    public function __construct(
        private StripeClientWithExpectationsInterface $stripeClientWithExpectations,
    ) {
    }

    public function mockCreateAction(): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getRefundBaseUrl(),
            [
                'id' => 're_1',
                'object' => Refund::OBJECT_NAME,
            ],
            true,
        );
    }

    public function mockCreateActionWithError(string $message, string $code): void
    {
        $this->stripeClientWithExpectations->addExpectation(
            'post',
            $this->getRefundBaseUrl(),
            [
                'error' => [
                    'type' => 'invalid_request_error',
                    'code' => $code,
                    'message' => $message,
                ],
            ],
            false,
            400,
        );
    }

    private function getRefundBaseUrl(): string
    {
        return Stripe::$apiBase . Refund::classUrl();
    }
}
