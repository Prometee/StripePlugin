<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\SecretKey\LegacyKeyDetectorInterface;
use FluxSE\SyliusStripePlugin\Twig\Extension\LegacyStripeKeyExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.twig.extension.legacy_stripe_key', LegacyStripeKeyExtension::class)
        ->args([
            service(LegacyKeyDetectorInterface::class),
        ])
        ->tag('twig.extension');
};
