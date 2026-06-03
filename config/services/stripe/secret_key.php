<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetector;
use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.stripe.secret_key.legacy_key_detector', LegacyKeyDetector::class);

    $services->alias(LegacyKeyDetectorInterface::class, 'flux_se.sylius_stripe.stripe.secret_key.legacy_key_detector');
};
