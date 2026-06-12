<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\Configurator\StripeConfigurator;
use FluxSE\SyliusStripePlugin\Stripe\Configurator\StripeConfiguratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.stripe.configurator', StripeConfigurator::class)
        ->args([
            service('logger'),
            service('flux_se.sylius_stripe.stripe.http_client'),
            service('flux_se.sylius_stripe.stripe.streaming_http_client'),
        ]);

    $services->alias(StripeConfiguratorInterface::class, 'flux_se.sylius_stripe.stripe.configurator');
};
