<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\StateMachine;

use Sylius\Component\Core\Model\PaymentInterface;

interface StripeStateAppliedCheckerInterface
{
    public function isAlreadyApplied(PaymentInterface $payment, string $paymentRequestAction): bool;
}
