<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Provider;

use Stripe\StripeObject;
use Sylius\Component\Payment\Model\PaymentMethodInterface;

interface RefundEventTokenHashResolverInterface
{
    /**
     * Resolves the token hash for a Stripe event object that does not carry it in its own metadata
     * (e.g. a Charge from a "charge.refunded" event) by reading it from the related PaymentIntent.
     */
    public function resolve(StripeObject $object, PaymentMethodInterface $paymentMethod): ?string;
}
