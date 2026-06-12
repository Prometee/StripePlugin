<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Stripe\Resolver\EventResolver;
use FluxSE\SyliusStripePlugin\Stripe\Resolver\EventResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.stripe.resolver.event_resolver', EventResolver::class);

    $services->alias(EventResolverInterface::class, 'flux_se.sylius_stripe.stripe.resolver.event_resolver');
};
