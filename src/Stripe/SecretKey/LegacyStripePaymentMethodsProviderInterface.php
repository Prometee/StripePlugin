<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Stripe\SecretKey;

use Sylius\Component\Payment\Model\PaymentMethodInterface;

interface LegacyStripePaymentMethodsProviderInterface
{
    /** @return list<PaymentMethodInterface> */
    public function provide(): array;
}
