<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\CompositeParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\ExpandProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('flux_se.sylius_stripe.payment_intent.cancel.expand_fields', [
            'latest_charge',
            'payment_method',
        ]);

    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.payment_intent.cancel.params', CompositeParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.payment_intent.cancel.inner_params'),
        ]);

    $services->set('flux_se.sylius_stripe.provider.payment_intent.cancel.expand', ExpandProvider::class)
        ->args([
            param('flux_se.sylius_stripe.payment_intent.cancel.expand_fields'),
        ])
        ->tag('flux_se.sylius_stripe.provider.payment_intent.cancel.inner_params', ['priority' => -100]);
};
