<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\CompositeParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\Invoice\All\LimitProvider;
use FluxSE\SyliusStripePlugin\Provider\Invoice\All\SubscriptionProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.invoice.all.params', CompositeParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.invoice.all.inner_params'),
        ]);

    $services->set('flux_se.sylius_stripe.provider.invoice.all.subscription', SubscriptionProvider::class)
        ->tag('flux_se.sylius_stripe.provider.invoice.all.inner_params', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.invoice.all.limit', LimitProvider::class)
        ->tag('flux_se.sylius_stripe.provider.invoice.all.inner_params', ['priority' => -200]);
};
