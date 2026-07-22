<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\ExpressCheckout;

interface ExpressCheckoutAvailabilityCheckerInterface
{
    public function isAvailable(): bool;
}
