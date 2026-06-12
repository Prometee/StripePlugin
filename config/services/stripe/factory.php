<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactory;
use FluxSE\SyliusStripePlugin\Stripe\Factory\ClientFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('flux_se.sylius_stripe.stripe.client', \Stripe\StripeClient::class);

    $services = $container->services();

    $services->set('flux_se.sylius_stripe.stripe.factory.client', ClientFactory::class)
        ->args([
            param('flux_se.sylius_stripe.stripe.client'),
            service('flux_se.sylius_stripe.stripe.configurator'),
        ]);

    $services->alias(ClientFactoryInterface::class, 'flux_se.sylius_stripe.stripe.factory.client');
};
