<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Twig\Extension;

use FluxSE\SyliusStripePlugin\ExpressCheckout\ExpressCheckoutAvailabilityCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ExpressCheckoutExtension extends AbstractExtension
{
    public function __construct(
        private readonly ExpressCheckoutAvailabilityCheckerInterface $availabilityChecker,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'sylius_stripe_is_express_checkout_available',
                $this->availabilityChecker->isAvailable(...),
            ),
        ];
    }
}
