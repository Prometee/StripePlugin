<?php

declare(strict_types=1);

use FluxSE\SyliusStripePlugin\Provider\CompositeMetadataParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\CompositeParamsProvider;
use FluxSE\SyliusStripePlugin\Provider\Refund\Create\AmountProvider;
use FluxSE\SyliusStripePlugin\Provider\Refund\Create\Metadata\RefundTokenHashProvider;
use FluxSE\SyliusStripePlugin\Provider\Refund\Create\PaymentIntentProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('flux_se.sylius_stripe.provider.refund.create.params', CompositeParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.refund.create.inner_params'),
        ]);

    $services->set('flux_se.sylius_stripe.provider.refund.create.amount', AmountProvider::class)
        ->tag('flux_se.sylius_stripe.provider.refund.create.inner_params', ['priority' => -100]);

    $services->set('flux_se.sylius_stripe.provider.refund.create.payment_intent', PaymentIntentProvider::class)
        ->tag('flux_se.sylius_stripe.provider.refund.create.inner_params', ['priority' => -200]);

    $services->set('flux_se.sylius_stripe.provider.refund.create.metadata', CompositeMetadataParamsProvider::class)
        ->args([
            tagged_iterator('flux_se.sylius_stripe.provider.refund.create.metadata'),
        ])
        ->tag('flux_se.sylius_stripe.provider.refund.create.inner_params', ['priority' => -300]);

    $services->set('flux_se.sylius_stripe.provider.refund.create.metadata.refund_token_hash', RefundTokenHashProvider::class)
        ->tag('flux_se.sylius_stripe.provider.refund.create.metadata', ['priority' => -100]);
};
