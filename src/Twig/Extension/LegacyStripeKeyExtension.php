<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Twig\Extension;

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyStripePaymentMethodsProviderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LegacyStripeKeyExtension extends AbstractExtension
{
    public function __construct(
        private readonly LegacyKeyDetectorInterface $legacyKeyDetector,
        private readonly LegacyStripePaymentMethodsProviderInterface $legacyStripePaymentMethodsProvider,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'sylius_stripe_is_legacy_secret_key',
                $this->legacyKeyDetector->isLegacy(...),
            ),
            new TwigFunction(
                'sylius_stripe_legacy_payment_methods',
                $this->legacyStripePaymentMethodsProvider->provide(...),
            ),
        ];
    }
}
